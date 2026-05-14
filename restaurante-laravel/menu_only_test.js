import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 20,
  duration: '30s',
};

export default function () {
  const r = http.get('http://localhost:8000/api/menu-items');
  check(r, { 'menu ok': (r) => r.status === 200 });
  sleep(0.5);
}