/**
 * PRUEBA DE RESISTENCIA (SOAK) — Oder Easy
 * Carga moderada durante tiempo prolongado para detectar:
 *   - Memory leaks (fugas de memoria)
 *   - Conexiones a BD que no se cierran
 *   - Degradación gradual del rendimiento
 * 
 * Duración: 30 minutos (ajustar con --env DURATION=2h para la prueba real)
 * Ejecutar:
 *   k6 run endurance_test.js
 *   k6 run --env DURATION=2h endurance_test.js
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Métricas que revelan degradación con el tiempo
const errorRate      = new Rate('error_rate');
const menuLatency    = new Trend('menu_latency_ms', true);
const orderLatency   = new Trend('order_latency_ms', true);
const totalOrders    = new Counter('total_orders');
const failedOrders   = new Counter('failed_orders');

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const DURATION = __ENV.DURATION  || '30m';

export const options = {
  stages: [
    { duration: '2m',     target: 10 },  // Calentamiento suave
    { duration: DURATION, target: 10 },  // Carga constante y moderada
    { duration: '2m',     target: 0  },  // Descenso
  ],

  thresholds: {
    // En resistencia el umbral clave es la CONSISTENCIA, no el pico
    http_req_failed:   ['rate<0.01'],
    error_rate:        ['rate<0.01'],
    // Si el p95 sube gradualmente durante la prueba, hay degradación
    http_req_duration: ['p(95)<1500', 'p(99)<3000'],
    menu_latency_ms:   ['p(95)<800'],
    order_latency_ms:  ['p(95)<2000'],
  },
};

export function setup() {
  const res = http.get(`${BASE_URL}/api/menu-items`);
  try {
    const items = JSON.parse(res.body).data || [];
    console.log(`Menú cargado: ${items.length} items`);
    return { menuItems: items };
  } catch {
    console.error('No se pudo cargar el menú en setup');
    return { menuItems: [] };
  }
}

export default function (data) {
  const menuItems = data.menuItems || [];
  const mesa = Math.ceil(Math.random() * 5);

  // ── Flujo 1: GET menú (40%) ──────────────────────────────
  if (Math.random() < 0.4) {
    group('GET menú', () => {
      const start = Date.now();
      const res = http.get(`${BASE_URL}/api/menu-items`);
      menuLatency.add(Date.now() - start);

      const ok = check(res, {
        'menú: 200':            (r) => r.status === 200,
        'menú: tiene data':     (r) => { try { return JSON.parse(r.body).data?.length > 0; } catch { return false; } },
      });
      errorRate.add(!ok);
      sleep(2);
    });

  // ── Flujo 2: Crear pedido (30%) ──────────────────────────
  } else if (Math.random() < 0.7) {
    group('POST pedido', () => {
      if (!menuItems.length) { sleep(1); return; }

      const item = menuItems[Math.floor(Math.random() * Math.min(menuItems.length, 6))];
      const start = Date.now();

      const res = http.post(
        `${BASE_URL}/api/orders`,
        JSON.stringify({
          mesa_id:       mesa,
          cliente_id:    null,
          restaurant_id: 1,
          items: [{
            menu_item_id:    item.id,
            cantidad:        1,
            precio_unitario: item.precio,
            nota:            null,
          }],
        }),
        { headers: { 'Content-Type': 'application/json' } }
      );
      orderLatency.add(Date.now() - start);

      const ok = check(res, {
        'pedido: 200/201': (r) => r.status === 200 || r.status === 201,
      });

      ok ? totalOrders.add(1) : failedOrders.add(1);
      errorRate.add(!ok);
      sleep(3);
    });

  // ── Flujo 3: GET mesas (20%) ─────────────────────────────
  } else if (Math.random() < 0.67) {
    group('GET mesas', () => {
      const res = http.get(`${BASE_URL}/api/mesas`);
      const ok = check(res, {
        'mesas: 200': (r) => r.status === 200,
      });
      errorRate.add(!ok);
      sleep(2);
    });

  // ── Flujo 4: GET cocina (10%) ────────────────────────────
  } else {
    group('GET cocina', () => {
      const res = http.get(`${BASE_URL}/api/kitchen/orders`);
      const ok = check(res, {
        'cocina: 200': (r) => r.status === 200,
      });
      errorRate.add(!ok);
      sleep(2);
    });
  }
}

export function handleSummary(data) {
  const m   = data.metrics;
  const dur  = m.http_req_duration?.values;
  const err  = m.http_req_failed?.values;
  const ok   = m.total_orders?.values?.count  ?? 0;
  const fail = m.failed_orders?.values?.count ?? 0;

  // Señal de degradación: comparar p50 vs p95
  const p50 = dur?.['p(50)'] ?? 0;
  const p95 = dur?.['p(95)'] ?? 0;
  const ratio = p50 > 0 ? (p95 / p50).toFixed(1) : '—';
  const degradacion = parseFloat(ratio) > 4
    ? '⚠️  POSIBLE DEGRADACIÓN (p95/p50 > 4x)'
    : '✅ Sin degradación detectada';

  const resultado = (err?.rate ?? 0) < 0.01 ? '✅ PASÓ' : '❌ FALLÓ';

  console.log(`
╔══════════════════════════════════════════════════╗
║       RESULTADO — PRUEBA DE RESISTENCIA          ║
╠══════════════════════════════════════════════════╣
║  Resultado        : ${String(resultado).padEnd(26)}║
║  Duración         : ${String(DURATION).padEnd(26)}║
║  Total requests   : ${String(m.http_reqs?.values?.count ?? '—').padEnd(26)}║
║  Tasa de error    : ${String(((err?.rate ?? 0)*100).toFixed(3) + '%').padEnd(26)}║
║  Pedidos OK       : ${String(ok).padEnd(26)}║
║  Pedidos fallidos : ${String(fail).padEnd(26)}║
╠══════════════════════════════════════════════════╣
║  p50              : ${String(p50.toFixed(0) + 'ms').padEnd(26)}║
║  p95              : ${String(p95.toFixed(0) + 'ms').padEnd(26)}║
║  p99              : ${String((dur?.['p(99)'] ?? 0).toFixed(0) + 'ms').padEnd(26)}║
║  Ratio p95/p50    : ${String(ratio + 'x').padEnd(26)}║
╠══════════════════════════════════════════════════╣
║  ${degradacion.padEnd(48)}║
╚══════════════════════════════════════════════════╝
  `);

  return {
    'resultados/endurance_summary.json': JSON.stringify(data, null, 2),
  };
}