/**
 * PRUEBA DE ESTRÉS — Oder Easy
 * Lleva el sistema más allá de su límite para encontrar el punto de quiebre
 * y verificar que se recupera solo.
 * 
 * Ejecutar:
 *   k6 run stress_test.js
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate    = new Rate('error_rate');
const menuLatency  = new Trend('menu_latency_ms', true);
const orderLatency = new Trend('order_latency_ms', true);

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export const options = {
  stages: [
    { duration: '30s', target: 10  },  // Línea base — sistema estable
    { duration: '30s', target: 30  },  // Carga moderada
    { duration: '30s', target: 60  },  // Carga alta — restaurante lleno
    { duration: '30s', target: 100 },  // Estrés — por encima del límite
    { duration: '30s', target: 150 },  // Sobre-estrés — punto de quiebre
    { duration: '1m',  target: 150 },  // Mantener presión
    { duration: '1m',  target: 0   },  // Recuperación — ¿vuelve solo?
  ],

  thresholds: {
    // En estrés los umbrales son más permisivos — lo importante es no caerse
    http_req_failed:   ['rate<0.10'],   // Máximo 10% de error bajo estrés extremo
    error_rate:        ['rate<0.10'],
    http_req_duration: ['p(99)<5000'],  // El 99% debe responder en < 5s
  },
};

// Cache local del menú para no recargarlo en cada iteración
let cachedMenuItems = null;

export function setup() {
  // Obtener el menú una vez al inicio para usar en los tests
  const res = http.get(`${BASE_URL}/api/menu-items`);
  try {
    const data = JSON.parse(res.body);
    return { menuItems: data.data || [] };
  } catch {
    return { menuItems: [] };
  }
}

export default function (data) {
  const menuItems = data.menuItems || [];
  const mesa = Math.ceil(Math.random() * 5);

  // Distribuir el tráfico entre los endpoints más críticos
  const scenario = Math.random();

  if (scenario < 0.35) {
    // Carga del menú — más frecuente (clientes escaneando QR)
    group('GET /api/menu-items', () => {
      const start = Date.now();
      const res = http.get(`${BASE_URL}/api/menu-items`);
      menuLatency.add(Date.now() - start);

      const ok = check(res, {
        'menú OK':          (r) => r.status === 200,
        'menú < 1s':        (r) => r.timings.duration < 1000,
        'menú tiene data':  (r) => {
          try { return JSON.parse(r.body).data?.length > 0; } catch { return false; }
        },
      });
      errorRate.add(!ok);
      sleep(0.5);
    });

  } else if (scenario < 0.55) {
    // Crear pedido — operación de escritura más crítica
    group('POST /api/orders', () => {
      if (!menuItems.length) { sleep(1); return; }

      const item = menuItems[Math.floor(Math.random() * Math.min(menuItems.length, 8))];
      const start = Date.now();

      const res = http.post(
        `${BASE_URL}/api/orders`,
        JSON.stringify({
          mesa_id:       mesa,
          cliente_id:    null,
          restaurant_id: 1,
          items: [
            {
              menu_item_id:    item.id,
              cantidad:        1,
              precio_unitario: item.precio,
              nota:            null,
            },
          ],
        }),
        { headers: { 'Content-Type': 'application/json' } }
      );
      orderLatency.add(Date.now() - start);

      const ok = check(res, {
        'pedido creado':    (r) => r.status === 200 || r.status === 201,
        'pedido < 3s':      (r) => r.timings.duration < 3000,
      });
      errorRate.add(!ok);
      sleep(0.3);
    });

  } else if (scenario < 0.75) {
    // Consulta de mesas — mesero revisando
    group('GET /api/mesas', () => {
      const res = http.get(`${BASE_URL}/api/mesas`);
      const ok = check(res, {
        'mesas OK':     (r) => r.status === 200,
        'mesas < 1.5s': (r) => r.timings.duration < 1500,
      });
      errorRate.add(!ok);
      sleep(0.5);
    });

  } else if (scenario < 0.88) {
    // Pedidos de cocina — KDS consultando
    group('GET /api/kitchen/orders', () => {
      const res = http.get(`${BASE_URL}/api/kitchen/orders`);
      const ok = check(res, {
        'cocina OK':     (r) => r.status === 200,
        'cocina < 1.5s': (r) => r.timings.duration < 1500,
      });
      errorRate.add(!ok);
      sleep(0.5);
    });

  } else {
    // Health check — ping básico
    group('GET /api/ping', () => {
      const res = http.get(`${BASE_URL}/api/ping`);
      check(res, { 'ping OK': (r) => r.status === 200 });
      sleep(0.2);
    });
  }
}

export function handleSummary(data) {
  const m  = data.metrics;
  const dur = m.http_req_duration?.values;
  const err = m.http_req_failed?.values;

  const resultado = (err?.rate ?? 0) < 0.10 ? '✅ PASÓ' : '❌ FALLÓ';

  console.log(`
╔══════════════════════════════════════════════════╗
║         RESULTADO — PRUEBA DE ESTRÉS             ║
╠══════════════════════════════════════════════════╣
║  Resultado        : ${String(resultado).padEnd(26)}║
║  Total requests   : ${String(m.http_reqs?.values?.count ?? '—').padEnd(26)}║
║  Tasa de error    : ${String(((err?.rate ?? 0) * 100).toFixed(2) + '%').padEnd(26)}║
╠══════════════════════════════════════════════════╣
║  p50  : ${String((dur?.['p(50)']  ?? 0).toFixed(0) + 'ms').padEnd(38)}║
║  p95  : ${String((dur?.['p(95)']  ?? 0).toFixed(0) + 'ms').padEnd(38)}║
║  p99  : ${String((dur?.['p(99)']  ?? 0).toFixed(0) + 'ms').padEnd(38)}║
║  max  : ${String((dur?.max        ?? 0).toFixed(0) + 'ms').padEnd(38)}║
╚══════════════════════════════════════════════════╝
  `);

  return {
    'resultados/stress_summary.json': JSON.stringify(data, null, 2),
  };
}