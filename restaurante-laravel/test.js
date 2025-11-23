import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  vus: 30,              // 30 usuarios simultáneos
  duration: '1m',       // durante 1 minuto
};

export default function () {
  // ✅ Si estás corriendo k6 desde fuera de Docker
  

  // ✅ Si corres k6 dentro de la misma red de Docker Compose (recomendado)
  // const url = 'http://laravel.test/api/menu-items';

  const payload = JSON.stringify({
    nombre: `Limonada_${Math.random()}`,
    categoria: 'bebida',
    precio: 7000,
  });

  const params = {
    headers: { 'Content-Type': 'application/json' },
  };

  const res = http.post(url, payload, params);
  check(res, {
    'status 201': (r) => r.status === 201,
  });

  sleep(0.5);
}
