import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 20,
  duration: '30s',
};

export function setup() {
  const res = http.get('http://localhost:8000/api/menu-items');
  const data = JSON.parse(res.body);
  return { items: data.data || [] };
}

export default function (data) {
  const items = data.items;
  if (!items.length) { sleep(1); return; }

  const item = items[Math.floor(Math.random() * items.length)];

  const r = http.post(
    'http://localhost:8000/api/orders',
    JSON.stringify({
      mesa_id: Math.ceil(Math.random() * 5),
      restaurant_id: 1,
      items: [{ menu_item_id: item.id, cantidad: 1, precio_unitario: item.precio }],
    }),
    { headers: { 'Content-Type': 'application/json' } }
  );

  check(r, {
    'order ok':   (r) => r.status === 200 || r.status === 201,
    'status':     (r) => { console.log(`Status: ${r.status}`); return true; },
  });
  sleep(0.5);
}