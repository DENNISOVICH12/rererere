/**
 * PRUEBA DE PICO — Oder Easy
 * Simula un aumento repentino de tráfico (apertura del restaurante, hora pico,
 * grupo grande llegando de golpe) y verifica que el sistema aguanta y se recupera.
 * 
 * Ejecutar:
 *   k6 run spike_test.js
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

const errorRate     = new Rate('error_rate');
const recoveryTime  = new Trend('recovery_time_ms', true);
const ordersOk      = new Counter('orders_ok');
const ordersFailed  = new Counter('orders_failed');

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export const options = {
  stages: [
    // Situación normal antes del pico
    { duration: '30s', target: 5   },

    // EL PICO: restaurante lleno de golpe (turno de almuerzo)
    { duration: '10s', target: 80  },

    // Pico sostenido — todos pidiendo al mismo tiempo
    { duration: '30s', target: 80  },

    // Caída — el pico pasa
    { duration: '10s', target: 5   },

    // Recuperación — ¿el sistema vuelve a responder bien?
    { duration: '30s', target: 5   },

    // Descenso final
    { duration: '10s', target: 0   },
  ],

  thresholds: {
    http_req_failed:   ['rate<0.05'],   // Máximo 5% durante el pico
    error_rate:        ['rate<0.05'],
    http_req_duration: ['p(95)<3000'],  // Durante el pico se permite más latencia
    orders_ok:         ['count>0'],     // Al menos un pedido debe completarse
  },
};

export function setup() {
  const res = http.get(`${BASE_URL}/api/menu-items`);
  try {
    return { menuItems: JSON.parse(res.body).data || [] };
  } catch {
    return { menuItems: [] };
  }
}

export default function (data) {
  const menuItems = data.menuItems || [];
  const mesa = Math.ceil(Math.random() * 10); // Más mesas para el pico

  // En un pico todos hacen lo mismo: llegan, ven el menú, piden
  group('Flujo completo durante pico', () => {

    // 1. Cargar el menú
    const menuRes = http.get(`${BASE_URL}/api/menu-items`, {
      tags: { phase: 'spike' },
    });

    const menuOk = check(menuRes, {
      'menú accesible durante pico': (r) => r.status === 200,
      'menú responde < 2s':          (r) => r.timings.duration < 2000,
    });

    if (!menuOk) {
      errorRate.add(1);
      sleep(0.5);
      return;
    }

    errorRate.add(0);
    sleep(0.8); // El cliente mira el menú brevemente

    // 2. Crear pedido (la operación más costosa durante el pico)
    if (menuItems.length > 0) {
      const item = menuItems[Math.floor(Math.random() * Math.min(menuItems.length, 10))];
      const cantidad = Math.ceil(Math.random() * 3);

      const orderStart = Date.now();
      const orderRes = http.post(
        `${BASE_URL}/api/orders`,
        JSON.stringify({
          mesa_id:       mesa,
          cliente_id:    null,
          restaurant_id: 1,
          items: [
            {
              menu_item_id:    item.id,
              cantidad:        cantidad,
              precio_unitario: item.precio,
              nota:            null,
            },
          ],
        }),
        {
          headers: { 'Content-Type': 'application/json' },
          tags:    { phase: 'spike', operation: 'create_order' },
          timeout: '10s', // Timeout generoso durante el pico
        }
      );
      recoveryTime.add(Date.now() - orderStart);

      const orderOk = check(orderRes, {
        'pedido creado bajo pico':   (r) => r.status === 200 || r.status === 201,
        'no timeout':                (r) => r.status !== 0,
        'no 503':                    (r) => r.status !== 503,
        'no 429 (rate limit)':       (r) => r.status !== 429,
      });

      if (orderOk) {
        ordersOk.add(1);
      } else {
        ordersFailed.add(1);
        errorRate.add(1);
      }
    }

    sleep(0.3);
  });
}

export function handleSummary(data) {
  const m   = data.metrics;
  const dur  = m.http_req_duration?.values;
  const err  = m.http_req_failed?.values;
  const ok   = m.orders_ok?.values?.count ?? 0;
  const fail = m.orders_failed?.values?.count ?? 0;
  const total = ok + fail;
  const tasaExito = total > 0 ? ((ok / total) * 100).toFixed(1) : '—';

  const resultado = (err?.rate ?? 0) < 0.05 ? '✅ RESISTIÓ EL PICO' : '❌ FALLÓ EN EL PICO';

  console.log(`
╔══════════════════════════════════════════════════╗
║         RESULTADO — PRUEBA DE PICO               ║
╠══════════════════════════════════════════════════╣
║  Resultado        : ${String(resultado).padEnd(26)}║
║  Pedidos exitosos : ${String(ok).padEnd(26)}║
║  Pedidos fallidos : ${String(fail).padEnd(26)}║
║  Tasa de éxito    : ${String(tasaExito + '%').padEnd(26)}║
╠══════════════════════════════════════════════════╣
║  Latencia p50     : ${String((dur?.['p(50)'] ?? 0).toFixed(0) + 'ms').padEnd(26)}║
║  Latencia p95     : ${String((dur?.['p(95)'] ?? 0).toFixed(0) + 'ms').padEnd(26)}║
║  Latencia máx     : ${String((dur?.max       ?? 0).toFixed(0) + 'ms').padEnd(26)}║
╚══════════════════════════════════════════════════╝
  `);

  return {
    'resultados/spike_summary.json': JSON.stringify(data, null, 2),
  };
}