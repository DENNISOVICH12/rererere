/**
 * PRUEBA DE CARGA — Oder Easy
 * Simula el flujo real de un cliente escaneando el QR y haciendo un pedido.
 * 
 * Ejecutar:
 *   k6 run load_test.js
 * 
 * Con reporte HTML (requiere k6-reporter):
 *   k6 run --out json=resultados/load.json load_test.js
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// ── Métricas personalizadas ───────────────────────────────────
const errorRate       = new Rate('error_rate');
const menuDuration    = new Trend('menu_load_ms', true);
const orderDuration   = new Trend('order_create_ms', true);
const mesasDuration   = new Trend('mesas_load_ms', true);
const ordersTotal     = new Counter('orders_created');

// ── Configuración ─────────────────────────────────────────────
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export const options = {
  // Rampa progresiva: diagnóstico base con carga moderada real
  stages: [
    { duration: '30s', target: 5  },  // Arranque suave
    { duration: '1m',  target: 20 },  // Carga normal (hora pico moderada)
    { duration: '2m',  target: 20 },  // Mantener carga estable
    { duration: '30s', target: 0  },  // Descenso
  ],

  thresholds: {
    // El sistema pasa la prueba si:
    http_req_duration:        ['p(95)<1500'],  // 95% de requests < 1.5s
    http_req_failed:          ['rate<0.01'],   // Menos del 1% de errores
    error_rate:               ['rate<0.01'],
    menu_load_ms:             ['p(95)<800'],   // Menú carga en < 800ms (tiene caché)
    order_create_ms:          ['p(95)<2000'],  // Crear pedido < 2s
    mesas_load_ms:            ['p(95)<1000'],  // Mapa de mesas < 1s
  },
};

// ── Datos de prueba ───────────────────────────────────────────
const MESAS = [1, 2, 3, 4, 5];
const CATEGORIAS = ['plato', 'bebida'];

// ── Escenarios ────────────────────────────────────────────────
export default function () {
  const mesa = MESAS[Math.floor(Math.random() * MESAS.length)];

  // ── Escenario 1: Cliente escanea QR y carga el menú (60% del tráfico) ──
  if (Math.random() < 0.6) {
    group('Cliente — carga carta digital', () => {

      // Paso 1: cargar items del menú
      const menuStart = Date.now();
      const menuRes = http.get(`${BASE_URL}/api/menu-items`, {
        tags: { endpoint: 'menu_items' },
      });
      menuDuration.add(Date.now() - menuStart);

      const menuOk = check(menuRes, {
        'menú: status 200':         (r) => r.status === 200,
        'menú: tiene items':        (r) => {
          try { return JSON.parse(r.body).data?.length > 0; } catch { return false; }
        },
        'menú: responde < 1s':      (r) => r.timings.duration < 1000,
      });
      errorRate.add(!menuOk);

      sleep(1.5); // El cliente mira el menú

      // Paso 2: hacer un pedido como invitado
      if (menuOk) {
        let items = [];
        try { items = JSON.parse(menuRes.body).data || []; } catch {}

        if (items.length > 0) {
          const item = items[Math.floor(Math.random() * Math.min(items.length, 5))];

          const orderStart = Date.now();
          const orderRes = http.post(
            `${BASE_URL}/api/orders`,
            JSON.stringify({
              mesa_id:       mesa,
              cliente_id:    null,
              restaurant_id: 1,
              items: [{
                menu_item_id:    item.id,
                cantidad:        Math.ceil(Math.random() * 2),
                precio_unitario: item.precio,
                nota:            null,
              }],
            }),
            { headers: { 'Content-Type': 'application/json' } }
          );
          orderDuration.add(Date.now() - orderStart);

          const orderOk = check(orderRes, {
            'pedido: creado (200/201)':  (r) => r.status === 200 || r.status === 201,
            'pedido: responde < 2s':     (r) => r.timings.duration < 2000,
          });
          if (orderOk) ordersTotal.add(1);
          errorRate.add(!orderOk);
        }
      }
    });

  // ── Escenario 2: Mesero revisa mesas (40% del tráfico) ──
  } else {
    group('Mesero — consulta mapa de mesas', () => {

      const mesasStart = Date.now();
      const mesasRes = http.get(`${BASE_URL}/api/mesas`, {
        tags: { endpoint: 'mesas' },
      });
      mesasDuration.add(Date.now() - mesasStart);

      check(mesasRes, {
        'mesas: status 200':        (r) => r.status === 200,
        'mesas: responde < 1s':     (r) => r.timings.duration < 1000,
      });

      sleep(0.5);

      // El mesero abre el detalle de una mesa específica
      const detailRes = http.get(`${BASE_URL}/api/mesas/${mesa}`, {
        tags: { endpoint: 'mesa_detalle' },
      });

      check(detailRes, {
        'mesa detalle: status 200': (r) => r.status === 200,
      });

      sleep(1);
    });
  }
}

export function handleSummary(data) {
  return {
    'resultados/load_summary.json': JSON.stringify(data, null, 2),
    stdout: resumenLegible(data),
  };
}

function resumenLegible(data) {
  const m = data.metrics;
  const dur   = m.http_req_duration?.values;
  const fails = m.http_req_failed?.values;
  const orders = m.orders_created?.values;

  return `
╔══════════════════════════════════════════════════╗
║         RESULTADO — PRUEBA DE CARGA              ║
╠══════════════════════════════════════════════════╣
║  Requests totales : ${String(m.http_reqs?.values?.count ?? '—').padEnd(26)}║
║  Tasa de error    : ${String(((fails?.rate ?? 0) * 100).toFixed(2) + '%').padEnd(26)}║
║  Pedidos creados  : ${String(orders?.count ?? '—').padEnd(26)}║
╠══════════════════════════════════════════════════╣
║  Tiempo respuesta (p50) : ${String((dur?.['p(50)'] ?? 0).toFixed(0) + 'ms').padEnd(19)}║
║  Tiempo respuesta (p95) : ${String((dur?.['p(95)'] ?? 0).toFixed(0) + 'ms').padEnd(19)}║
║  Tiempo respuesta (p99) : ${String((dur?.['p(99)'] ?? 0).toFixed(0) + 'ms').padEnd(19)}║
╚══════════════════════════════════════════════════╝
`;
}