import { createApp } from 'vue'

// ❌ ELIMINADO: animate.css — 72 KB, no se usa en ningún componente
// ❌ ELIMINADO: @fortawesome/fontawesome-free — 40 MB instalado, 76 KB CSS + 8 fuentes woon2, CERO usos en el código

import './style.css'
import App from './App.vue'

// Las rutas de la carta digital son públicas en el backend (sin auth:sanctum).
// NO usamos withCredentials para evitar que cookies de sesión del staff
// interfieran con los pedidos de clientes invitados (causaban 401/403).
createApp(App).mount('#app')