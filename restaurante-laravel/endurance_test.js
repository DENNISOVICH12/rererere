import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  vus: 10,            // 10 usuarios constantes
  duration: '10m',    // durante 10 minutos
};

export default function () {
  const url = 'http://host.docker.internal:8000/api/menu-items';
  const payload = JSON.stringify({
    nombre: `Endurance_${Math.random()}`,
    categoria: 'bebida',
    precio: 7000,
  });

  const params = {
    headers: { 'Content-Type': 'application/json' },
  };

  const res = http.post(url, payload, params);
  check(res, { 'status 201': (r) => r.status === 201 });
  sleep(1);
}

