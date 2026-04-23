import '../css/app.css';
import './bootstrap';
import axios from 'axios';

axios.defaults.withCredentials = true;

import { createApp } from 'vue'

// 👇 importa tu login staff
import StaffLogin from './components/StaffLogin.vue'

// 👇 DETECTOR DE RUTA
if (window.location.pathname === '/staff') {
    const app = createApp({
        template: `<h1 style="color:white">FUNCIONA 🔥</h1>`
    })
    app.mount('#app')
}
const token = document.querySelector('meta[name="csrf-token"]');

if (token) {  
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
}