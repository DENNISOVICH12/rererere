import { createApp } from 'vue';
import HistorialCliente from '../pages/admin/HistorialCliente.vue';
import axios from 'axios';
createApp(HistorialCliente).mount('#historialClienteApp');
axios.defaults.withCredentials = true;