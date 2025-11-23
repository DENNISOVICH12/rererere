import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '10s', target: 10 },   // Inicio tranquilo
    { duration: '5s', target: 100 },   // Pico repentino
    { duration: '10s', target: 10 },   // Vuelve a la normalidad
  ],
};

export default function () {
  const url = 'http://host.docker.internal:8000/api/menu-items';
  const payload = JSON.stringify({
    nombre: `Spike_${Math.random()}`,
    categoria: 'bebida',
    precio: 7000,
  });

  const params = {
    headers: { 'Content-Type': 'application/json' },
  };

  const res = http.post(url, payload, params);
  check(res, { 'status 201': (r) => r.status === 201 });
  sleep(0.5);
}
