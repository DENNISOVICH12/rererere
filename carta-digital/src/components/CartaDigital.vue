<template>       
  <div class="menu-container">

    <!-- HEADER -->
    <header class="header">
      <h1 class="title">🍽 ODER EASY</h1>

      <!-- ✅ Mostrar botón según sesión -->
      <div v-if="!cliente">
        <button class="login-btn" @click="showLogin = true">
          Iniciar sesión
        </button>
      </div>

      <div v-else class="user-info">
        <span class="user-name">👤 {{ cliente.nombre }}</span>
        <button class="logout-btn" @click="logoutCliente">
          Cerrar sesión
        </button>
      </div>
    </header>

    <!-- ESTADO DE PEDIDO (SOLO CON SESIÓN) -->
    <section v-if="cliente" class="order-status compact">
      <div class="order-header">
        <div>
          <h2>📦 Estado de tu pedido</h2>
        </div>
        <button class="refresh-btn" @click="loadPedidosCliente" :disabled="loadingPedidos">
          {{ loadingPedidos ? 'Actualizando...' : 'Actualizar' }}
        </button>
      </div>

      <div v-if="loadingPedidos" class="order-loading">Cargando estado...</div>
      <div v-else-if="!pedidoActual" class="order-empty">
        Aún no tienes pedidos activos.
      </div>
      <div v-else class="order-card compact-card">
        <ol class="timeline horizontal professional">
          <li
            v-for="(step, index) in timelineSteps"
            :key="step"
            :class="{
              completed: index < currentStepIndex,
              active: index === currentStepIndex
            }"
          >
            <div class="dot"></div>
            <div class="step-content">
              <p class="step-title">{{ stepLabels[step] }}</p>
              <p class="step-desc">{{ stepDescriptions[step] }}</p>
            </div>
          </li>
        </ol>
        <div class="timeline-current">
          <span class="timeline-label">Estado actual:</span>
          <span class="timeline-value">
            {{ currentStepIndex >= 0 ? stepLabels[timelineSteps[currentStepIndex]] : 'Sin estado' }}
          </span>
        </div>
      </div>

      <p v-if="errorPedidos" class="order-error">{{ errorPedidos }}</p>
    </section>

    <!-- FILTROS -->
    <div class="filters">
      <button 
        v-for="cat in categories" 
        :key="cat" 
        @click="selectedCategory = cat"
        class="filter-btn"
        :class="{ active: selectedCategory === cat }"
      >
        {{ cat.toUpperCase() }}
      </button>
    </div>

    <!-- LISTA DE PRODUCTOS -->
    <div class="grid">
      <div v-for="item in filteredItems" :key="item.id" class="card">
        <img 
          v-if="item.imagen"
          :src="item.imagen"
          class="card-img"
          loading="lazy"
        />
        <div class="card-body">
          <h2>{{ item.nombre }}</h2>
          <p class="desc">{{ item.descripcion }}</p>
          <p class="price">${{ Number(item.precio).toLocaleString() }}</p>
          <button class="add-btn" @click="addToCart(item)">
            Agregar 🛒
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL LOGIN / REGISTRO -->
    <div v-if="showLogin" class="modal-backdrop">
      <div class="modal-panel">

        <div class="tabs">
          <button :class="{active: modalTab==='login'}" @click="modalTab='login'">
            Iniciar sesión
          </button>
          <button :class="{active: modalTab==='register'}" @click="modalTab='register'">
            Registrarse
          </button>
        </div>

        <!-- LOGIN -->
        <div v-if="modalTab==='login'">

          <input class="input" :class="{ error: showErrors && !form.usuario }" placeholder="Usuario" v-model="form.usuario">
          <input class="input" :class="{ error: showErrors && !form.password }" type="password" placeholder="Contraseña" v-model="form.password">

          <!-- ✅ Mensaje interno -->
          <p v-if="loginMessage" class="status-msg">{{ loginMessage }}</p>

          <button class="btn-primary" @click="login" :disabled="loadingLogin">
            <span v-if="!loadingLogin">Entrar</span>
            <span v-else>Cargando...</span>
          </button>
        </div>

        <!-- REGISTRO -->
        <div v-if="modalTab==='register'">

          <input class="input" placeholder="Usuario" v-model="register.usuario">
          <input class="input" placeholder="Nombres" v-model="register.nombres">
          <input class="input" placeholder="Apellidos" v-model="register.apellidos">
          <input class="input" type="email" placeholder="Correo" v-model="register.correo">
          <input class="input" type="password" placeholder="Contraseña" v-model="register.password">

          <!-- ✅ Mensaje interno -->
          <p v-if="registerMessage" class="status-msg">{{ registerMessage }}</p>

          <button class="btn-primary" @click="registerUser">
            Crear cuenta
          </button>
        </div>

        <button class="btn-secondary" @click="cerrarModal">Cerrar</button>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'
import { addToCart } from '../cart.js'
import { cliente, setCliente, logoutCliente } from '../cliente.js'

const API = "http://192.168.1.160:8000/api"

const items = ref([])
const selectedCategory = ref('todos')
const categories = ['todos', 'plato', 'bebida']

const showLogin = ref(false)
const modalTab = ref('login')

// Forms
const form = ref({ usuario: "", password: "" })
const register = ref({ usuario: "", nombres: "", apellidos: "", correo: "", password: "" })

// UI States
const loadingLogin = ref(false)
const showErrors = ref(false)
const loginMessage = ref("")
const registerMessage = ref("")
const pedidosCliente = ref([])
const loadingPedidos = ref(false)
const errorPedidos = ref("")
let pedidosInterval = null

const timelineSteps = ['pendiente', 'preparando', 'listo', 'entregado']
const stepLabels = {
  pendiente: 'Pendiente',
  preparando: 'En cocina',
  listo: 'Listo',
  entregado: 'Entregado'
}
const stepDescriptions = {
  pendiente: 'Cocina recibió tu pedido',
  preparando: 'Se está preparando',
  listo: 'Listo para entregar',
  entregado: 'Pedido entregado'
}

// Cargar menú
onMounted(async () => {
  const res = await fetch(`${API}/menu-items`)
  items.value = (await res.json()).data
})

async function loadPedidosCliente() {
  if (!cliente.value) return
  loadingPedidos.value = true
  errorPedidos.value = ""

  try {
    const res = await axios.get(`${API}/clientes/${cliente.value.id}/pedidos`)
    pedidosCliente.value = res.data.data ?? res.data
  } catch (error) {
    console.error(error)
    errorPedidos.value = "No pudimos cargar el estado del pedido."
  } finally {
    loadingPedidos.value = false
  }
}

function clearPedidosPolling() {
  if (pedidosInterval) {
    clearInterval(pedidosInterval)
    pedidosInterval = null
  }
}

// Abrir modal desde carrito
onMounted(() => {
  const openLogin = () => {
    showLogin.value = true
    modalTab.value = 'login'
  }
  window.addEventListener("open-login", openLogin)
  onUnmounted(() => window.removeEventListener("open-login", openLogin))
})

watch(
  cliente,
  (nuevo) => {
    clearPedidosPolling()
    pedidosCliente.value = []
    errorPedidos.value = ""

    if (nuevo) {
      loadPedidosCliente()
      pedidosInterval = setInterval(loadPedidosCliente, 6000)
    }
  },
  { immediate: true }
)

window.addEventListener("pedido-creado", loadPedidosCliente)
onUnmounted(() => {
  window.removeEventListener("pedido-creado", loadPedidosCliente)
  clearPedidosPolling()
})

// ✅ LOGIN
async function login() {
  showErrors.value = true
  loginMessage.value = ""

  if (!form.value.usuario || !form.value.password) {
    loginMessage.value = "⚠️ Completa todos los campos."
    return
  }

  loadingLogin.value = true

  try {
    const res = await axios.post(`${API}/login-cliente`, form.value)
    setCliente(res.data.cliente)
    loginMessage.value = "✅ Sesión iniciada"
    setTimeout(() => showLogin.value = false, 800)

  } catch {
    loginMessage.value = "❌ Usuario o contraseña incorrectos"
  } finally {
    loadingLogin.value = false
  }
}

// ✅ REGISTRO
async function registerUser() {
  registerMessage.value = ""

  try {
    await axios.post(`${API}/register-cliente`, register.value)

    const loginRes = await axios.post(`${API}/login-cliente`, {
      usuario: register.value.usuario,
      password: register.value.password
    })

    setCliente(loginRes.data.cliente)
    registerMessage.value = "✅ Cuenta creada"
    setTimeout(() => showLogin.value = false, 800)

  } catch {
    registerMessage.value = "❌ Error registrando usuario"
  }
}

function cerrarModal() {
  showLogin.value = false
}

const filteredItems = computed(() =>
  selectedCategory.value === 'todos'
    ? items.value
    : items.value.filter(i => i.categoria === selectedCategory.value)
)

const pedidoActual = computed(() => pedidosCliente.value[0] ?? null)
const currentStepIndex = computed(() => {
  if (!pedidoActual.value) return -1
  const estado = (pedidoActual.value.estado || '').toLowerCase()
  const index = timelineSteps.indexOf(estado)
  return index >= 0 ? index : 0
})

</script>

<style scoped>
.input.error {
  border: 2px solid #ff4d4d !important;
  background: #ffeaea !important;
}

/* 🟡 Mensajes de feedback */
.status-msg {
  text-align:center;
  font-weight:600;
  margin-bottom:8px;
  color:#ffd7aa;
}

/* CONTENEDOR */
.menu-container { max-width: 1200px; margin: auto; padding: 40px 20px; color: #fff; text-shadow: 0px 2px 4px rgba(0,0,0,0.65); }

/* HEADER */
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.title { color: #F8ECE4 !important; font-family: 'Playfair Display', serif; }

/* LOGIN BTN */
.login-btn { padding: 10px 24px; border-radius: 30px; font-weight: 600; color: #fff; border: 1px solid rgba(255,215,170,0.8); background: #7a1522; box-shadow: 0 4px 14px rgba(0,0,0,0.45); transition: 0.35s; }
.login-btn:hover { background: #9c2030; border-color: #ffd7aa; }

/* CLIENTE */
.user-info { display: flex; align-items:center; gap:14px; }
.user-name { font-weight:700; color:#ffd7aa; }
.logout-btn { background:#5a101a; padding:8px 18px; border-radius:20px; color:#fff; border:none; cursor:pointer; transition:.3s; }
.logout-btn:hover { background:#9c2030; }

/* FILTROS */
.filters { display:flex; gap:12px; justify-content:center; margin-bottom:30px; }
.filter-btn { padding:8px 18px; border-radius:20px; background:rgba(255,255,255,0.08); color:#F8ECE4; border:1.5px solid rgba(255,255,255,0.35); transition:.3s; }
.filter-btn.active, .filter-btn:hover { background:#8a1c2b; border-color:#F8ECE4; }

/* ESTADO PEDIDO */
.order-status { background:rgba(0,0,0,0.45); border:1px solid rgba(255,255,255,0.18); border-radius:16px; padding:14px 18px; margin-bottom:20px; display:flex; flex-direction:column; gap:12px; }
.order-status.compact { max-width: 100%; }
.order-header { display:flex; justify-content:space-between; align-items:center; gap:12px; }
.order-header h2 { margin:0; font-size:18px; }
.refresh-btn { background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.25); color:#fff; padding:6px 12px; border-radius:10px; cursor:pointer; transition:.3s; font-size:12px; }
.refresh-btn:hover { background:#9c2030; }
.refresh-btn:disabled { opacity:.5; cursor:not-allowed; }
.order-loading, .order-empty { opacity:.75; font-size:13px; }
.order-card { background:rgba(0,0,0,0.35); border-radius:16px; padding:16px; border:1px solid rgba(255,255,255,0.12); }
.order-card.compact-card { padding:16px 14px; }

.timeline { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:14px; }
.timeline.horizontal { flex-direction:row; gap:0; justify-content:space-between; align-items:flex-start; }
.timeline.horizontal.professional { background:rgba(255,255,255,0.04); border-radius:14px; padding:16px 10px 12px; border:1px solid rgba(255,255,255,0.08); }
.timeline.horizontal li { flex:1; display:flex; flex-direction:column; align-items:center; text-align:center; position:relative; padding:0 6px; }
.timeline.horizontal li::before { content:""; position:absolute; top:14px; left:50%; width:100%; height:2px; background:rgba(255,255,255,0.16); z-index:0; }
.timeline.horizontal li:first-child::before { left:50%; width:50%; }
.timeline.horizontal li:last-child::before { width:50%; }
.dot { width:14px; height:14px; border-radius:50%; background:rgba(255,255,255,0.12); border:2px solid rgba(255,255,255,0.35); margin-top:6px; flex-shrink:0; z-index:1; position:relative; }
.dot::after { content:""; position:absolute; inset:3px; border-radius:50%; background:rgba(255,255,255,0.35); opacity:.3; }
.timeline li.completed .dot { background:#2ecc71; border-color:#2ecc71; box-shadow:0 0 10px rgba(46,204,113,.5); }
.timeline li.completed .dot::after { background:#1e7f44; opacity:.45; }
.timeline li.active .dot { background:#ffd7aa; border-color:#ffd7aa; box-shadow:0 0 12px rgba(255,215,170,.7); }
.timeline li.active .dot::after { background:#9c6b34; opacity:.5; }
.step-title { margin:8px 0 0; font-weight:700; font-size:12.5px; letter-spacing:.2px; }
.step-desc { margin:4px 0 0; font-size:11px; opacity:.75; }
.timeline.horizontal .step-desc { max-width:120px; }
.timeline-current { display:flex; justify-content:center; gap:8px; margin-top:10px; font-size:12px; }
.timeline-label { opacity:.65; }
.timeline-value { font-weight:700; color:#ffd7aa; }
.order-error { color:#ff9c9c; font-weight:600; font-size:12px; }

/* GRID */
.grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px,1fr)); gap:28px; }

/* PRODUCT CARD */
.card { background:rgba(0,0,0,0.55); backdrop-filter:blur(14px); border-radius:18px; padding:18px; border:1px solid rgba(255,255,255,0.20); transition:.35s; }
.card:hover { transform:translateY(-6px); }
.card-img { width:100%; height:180px; object-fit:cover; border-radius:14px; margin-bottom:14px; }

.add-btn { background:#7a1522; color:#fff; border-radius:10px; padding:10px 18px; width:100%; border:none; transition:.3s; }
.add-btn:hover { background:#9c2030; transform:translateY(-2px); }

/* MODAL */
.modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); display:flex; justify-content:center; align-items:center; animation:fadein .25s; z-index:5000; }
.modal-panel { background:rgba(0,0,0,0.65); padding:28px; width:350px; border-radius:18px; border:1px solid rgba(255,255,255,0.18); animation:pop .28s; }

.tabs { display:flex; margin-bottom:18px; border-bottom:1px solid rgba(255,255,255,0.25); }
.tabs button { flex:1; padding:10px; background:none; border:none; color:#fff; opacity:.6; font-weight:600; cursor:pointer; transition:.25s; }
.tabs button.active { opacity:1; border-bottom:2px solid #ffd7aa; }

.input { width:100%; padding:12px; border-radius:10px; background:rgba(255,255,255,0.95); margin-bottom:14px; border:none; text-align:center; font-size:15px; color:#2a2a2a; font-weight:600; }

.btn-primary { width:100%; padding:12px; background:#8a1c2b; color:#fff; border-radius:10px; border:none; margin-top:8px; }
.btn-secondary { width:100%; padding:11px; background:#2a2a2a; color:white; border-radius:10px; border:none; margin-top:10px; }

@keyframes pop { from { transform:scale(.85); opacity:0; } to { transform:scale(1); opacity:1); } }
@keyframes fadein { from { opacity:0; } to { opacity:1); } }
</style>
