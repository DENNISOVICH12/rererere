import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '30s', target: 10 },   // Calentamiento
    { duration: '30s', target: 30 },   // Carga moderada
    { duration: '30s', target: 60 },   // Carga alta
    { duration: '30s', target: 100 },  // Sobrecarga
    { duration: '30s', target: 0 },    // Descenso gradual
  ],
};

export default function () {
  const url = 'http://host.docker.internal:8000/api/menu-items';
  const payload = JSON.stringify({
    nombre: `Test_${Math.random()}`,
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
