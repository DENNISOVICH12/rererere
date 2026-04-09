import '../css/app.css';
import './bootstrap';
import axios from 'axios';

axios.defaults.withCredentials = true;

const token = document.querySelector('meta[name="csrf-token"]');

if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
}