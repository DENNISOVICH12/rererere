<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import CartaDigital from './components/CartaDigital.vue'
import { cart, removeFromCart, clearCart, openLoginModal } from './cart.js'
import axios from 'axios'
import { API_BASE } from './api.js'
import { loadCliente, getCliente, cliente, logoutCliente } from "./cliente.js"
loadCliente()

const showCart = ref(false)
const showLogin = ref(false)
const showRegister = ref(false)

const email = ref("")
const password = ref("")
const cartButton = ref(null)
const pedidosCliente = ref([])
const loadingPedidos = ref(false)
const errorPedidos = ref("")
let pedidosInterval = null
const API = "http://192.168.80.14:8000/api"

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

function openLogin() {
window.dispatchEvent(new CustomEvent("open-login"))
}
function handleLogout() {
  logoutCliente()
  alert("👋 Sesión cerrada correctamente")
}
async function handleLogin() {
  try {
    await login(email.value, password.value)
    showLogin.value = false
    alert("✅ Sesión iniciada, tus pedidos ahora estarán asociados.")
  } catch {
    alert("❌ Usuario o contraseña incorrectos")
  }
}

const registerForm = ref({
  usuario: "",
  correo: "",
  password: "",
  nombres: "",
  apellidos: "",
  dni: "",
  edad: ""
})

async function handleRegister() {
  try {

    await axios.post(`${API_BASE}/register-cliente`, registerForm.value)
alert("✅ Registro exitoso, ahora inicia sesión ✅")

    showRegister.value = false
    showLogin.value = true
  } catch {
    alert("❌ Error al registrarse")
  }
}

const totalPrice = computed(() =>
  cart.value.reduce((sum, item) => sum + item.precio * item.quantity, 0)
)

async function sendOrder() {
  try {
    const cliente = getCliente(); // ✅ obtiene { id, usuario, nombre }


    await axios.post('http://192.168.80.14:8000/api/orders', {

      mesa: null,
      cliente_id: cliente ? cliente.id : null, // ✅ ahora SIEMPRE manda el ID correcto
      restaurant_id: 1,
      items: cart.value.map(i => ({
        menu_item_id: i.id,
        cantidad: i.quantity,
        precio_unitario: i.precio
      }))
    });

    alert(cliente
      ? "✅ Pedido enviado (Cliente identificado)."
      : "✅ Pedido enviado como invitado.");

    window.dispatchEvent(new CustomEvent("pedido-creado"));

    clearCart(); // mejor que hacer cart.value = []

  } catch (error) {
    console.log(error.response?.data);
    alert("❌ Error enviando pedido");
  }
}

async function loadPedidosCliente() {
  const clienteActual = cliente.value
  if (!clienteActual) return
  loadingPedidos.value = true
  errorPedidos.value = ""

  try {
    const res = await axios.get(`${API}/clientes/${clienteActual.id}/pedidos`)
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


/* 🔥 Rebote cuando se añade producto */
onMounted(() => {

  // 🔔 Animación cuando se agrega al carrito
  const cartBounceHandler = () => {
    if (!cartButton.value) return

    cartButton.value.classList.remove("cart-bounce")
    void cartButton.value.offsetWidth // fuerza reflow para reiniciar animación
    cartButton.value.classList.add("cart-bounce")
  }

  // 📌 Escuchar evento global del carrito
  window.addEventListener("cart-updated", cartBounceHandler)


  // 🔓 Escuchar la solicitud global para abrir login
  const openLoginHandler = () => {
    showLogin.value = true
  }

  window.addEventListener("open-login", openLoginHandler)


  // 🧹 Quitar eventos al destruir el componente (buena práctica)
  onUnmounted(() => {
    window.removeEventListener("cart-updated", cartBounceHandler)
    window.removeEventListener("open-login", openLoginHandler)
  })
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

const pedidoActual = computed(() => pedidosCliente.value[0] ?? null)
const currentStepIndex = computed(() => {
  if (!pedidoActual.value) return -1
  const estado = (pedidoActual.value.estado || '').toLowerCase()
  const index = timelineSteps.indexOf(estado)
  return index >= 0 ? index : 0
})

</script>

<template>
  <div>

    <button ref="cartButton" class="cart-floating" @click="showCart = !showCart">
      🛒 <span>{{ cart.length }}</span>
    </button>

    <CartaDigital />

    <div v-if="showCart" class="cart-panel">

      <button class="close-cart" @click="showCart = false">✦</button>

      <h2 class="cart-title">Tu Pedido</h2>

      <section v-if="cliente" class="order-status-cart">
        <div class="order-header">
          <div>
            <h3>📦 Estado de tu pedido</h3>
            <p class="order-subtitle">Seguimiento en tiempo real</p>
          </div>
          <button class="refresh-btn" @click="loadPedidosCliente" :disabled="loadingPedidos">
            {{ loadingPedidos ? 'Actualizando...' : 'Actualizar' }}
          </button>
        </div>

        <div v-if="!pedidoActual && !loadingPedidos" class="order-empty">
          Aún no tienes pedidos activos.
        </div>

        <div v-else class="order-card compact-card" :class="{ 'is-loading': loadingPedidos }">
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

      <div v-if="!cliente" class="login-hint">
        ¿Quieres guardar historial y obtener puntos?<br>
        <button class="login-link" @click="openLoginModal">Iniciar sesión</button>

        </div>
      <div v-if="cart.length === 0" class="empty-cart">🛍 Carrito vacío</div>

      <div v-for="item in cart" :key="item.id" class="cart-item">
        <div>
          <strong>{{ item.nombre }}</strong>
          <p class="qty">Cantidad: {{ item.quantity }}</p>
        </div>
        <button class="remove-item" @click="removeFromCart(item.id)">🗑</button>
      </div>

      <div v-if="cart.length > 0" class="cart-footer">
        <p class="total">Total: <strong>${{ totalPrice.toLocaleString() }}</strong></p>
        <button @click="clearCart" class="clear-btn">Vaciar Carrito</button>
        <button @click="sendOrder" class="send-btn">Enviar a cocina</button>
      </div>

    </div>

    <!-- modals se quedan IGUAL -->
  </div>
</template>

<style>

/* --- Rebote estilo iPhone al agregar productos --- */
.cart-bounce {
  animation: cartBounceAnim 0.45s cubic-bezier(.22,.61,.36,1);
}

@keyframes cartBounceAnim {
  0%   { transform: translateY(0) scale(1); }
  30%  { transform: translateY(-6px) scale(1.07); }
  60%  { transform: translateY(2px) scale(0.95); }
  100% { transform: translateY(0) scale(1); }
}

/* === BOTÓN CARRITO FLOTANTE === */
.cart-floating {
  position: fixed;
  bottom: 26px;
  right: 26px;
  background: rgba(255,255,255,0.18);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.35);
  padding: 12px 18px;
  border-radius: 14px;
  font-size: 18px;
  cursor: pointer;
  color: #F8ECE4;
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.45);
  transition: .3s;
  z-index: 3000;
}
.cart-floating:hover { transform: translateY(-3px) scale(1.03); }

/* === PANEL CARRITO === */
.cart-panel {
  position: fixed;
  top: 0;
  right: 0;
  width: 380px;
  height: 100vh;
  background: rgba(0,0,0,0.65);
  backdrop-filter: blur(18px);
  border-left: 1px solid rgba(255,255,255,0.18);
  padding: 32px;
  color: #fff;
  overflow-y: auto;
  z-index: 4000;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

/* === ESTADO PEDIDO EN CARRITO === */
.order-status-cart {
  background: rgba(0,0,0,0.35);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 16px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}
.order-header h3 { margin: 0; font-size: 16px; }
.order-subtitle { margin: 4px 0 0; font-size: 12px; opacity: .65; }
.refresh-btn {
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.25);
  color: #fff;
  padding: 6px 10px;
  border-radius: 10px;
  cursor: pointer;
  transition: .3s;
  font-size: 11px;
  white-space: nowrap;
}
.refresh-btn:hover { background: #9c2030; }
.refresh-btn:disabled { opacity: .55; cursor: not-allowed; }
.order-empty { opacity: .75; font-size: 13px; }
.order-card {
  background: rgba(0,0,0,0.35);
  border-radius: 14px;
  padding: 12px;
  border: 1px solid rgba(255,255,255,0.12);
  transition: opacity .25s ease;
}
.order-card.is-loading { opacity: .55; }

.timeline { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
.timeline.horizontal { flex-direction: row; gap: 0; justify-content: space-between; align-items: flex-start; }
.timeline.horizontal.professional {
  background: rgba(255,255,255,0.04);
  border-radius: 12px;
  padding: 14px 8px 10px;
  border: 1px solid rgba(255,255,255,0.08);
}
.timeline.horizontal li {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  position: relative;
  padding: 0 4px;
}
.timeline.horizontal li::before {
  content: "";
  position: absolute;
  top: 12px;
  left: 50%;
  width: 100%;
  height: 2px;
  background: rgba(255,255,255,0.16);
  z-index: 0;
}
.timeline.horizontal li:first-child::before { left: 50%; width: 50%; }
.timeline.horizontal li:last-child::before { width: 50%; }
.dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: rgba(255,255,255,0.12);
  border: 2px solid rgba(255,255,255,0.35);
  margin-top: 4px;
  flex-shrink: 0;
  z-index: 1;
  position: relative;
}
.dot::after {
  content: "";
  position: absolute;
  inset: 3px;
  border-radius: 50%;
  background: rgba(255,255,255,0.35);
  opacity: .3;
}
.timeline li.completed .dot { background: #2ecc71; border-color: #2ecc71; box-shadow: 0 0 8px rgba(46,204,113,.45); }
.timeline li.completed .dot::after { background: #1e7f44; opacity: .45; }
.timeline li.active .dot { background: #ffd7aa; border-color: #ffd7aa; box-shadow: 0 0 10px rgba(255,215,170,.6); }
.timeline li.active .dot::after { background: #9c6b34; opacity: .5; }
.step-title { margin: 6px 0 0; font-weight: 700; font-size: 11.5px; letter-spacing: .2px; }
.step-desc { margin: 4px 0 0; font-size: 10px; opacity: .75; }
.timeline.horizontal .step-desc { max-width: 92px; }
.timeline-current { display: flex; justify-content: center; gap: 8px; margin-top: 8px; font-size: 11px; }
.timeline-label { opacity: .65; }
.timeline-value { font-weight: 700; color: #ffd7aa; }
.order-error { color: #ff9c9c; font-weight: 600; font-size: 12px; }

/* Cerrar */
.close-cart {
  align-self: flex-end;
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.25);
  padding: 8px 12px;
  border-radius: 10px;
  cursor: pointer;
  color: #fff;
  transition: .25s;
}
.close-cart:hover { background: #ff4b4b; transform: rotate(90deg) scale(1.18); }

/* Texto sugerencia login */
.login-hint { text-align:center; opacity:.9; font-size:15px; }
.login-link { color:#ffd7aa; background:none; border:none; cursor:pointer; font-weight:600; }
.login-link:hover { text-shadow:0 0 6px #ffd7aa; }

/* ITEMS */
.cart-item {
  display: flex;
  justify-content: space-between;
  padding: 14px 0;
  border-bottom: 1px solid rgba(255,255,255,0.15);
}
.qty { opacity: 0.8; font-size: 14px; }

/* Botón eliminar */
.remove-item {
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.25);
  padding: 6px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: .25s;
  color: #ff9c9c;
}
.remove-item:hover { background: #ff4b4b; color: white; transform: scale(1.1); }

/* BOTONES CARRITO */
.clear-btn,
.send-btn {
  padding: 14px;
  width: 100%;
  border-radius: 14px;
  border: none;
  font-weight: 600;
  font-size: 15px;
  cursor: pointer;
  margin-top: 14px;
  transition: transform .24s cubic-bezier(.22,.61,.36,1);
}

.clear-btn:hover,
.send-btn:hover {
  transform: translateY(-4px) scale(1.03);
  box-shadow: 0 8px 20px rgba(0,0,0,.35);
}

.clear-btn:active,
.send-btn:active {
  transform: translateY(2px) scale(.97);
}

/* Vaciar */
.clear-btn {
  background: rgba(255,255,255,0.12);
  color: #ffbaba;
}
.clear-btn:hover { background: rgba(255,119,119,0.30); }

/* Enviar */
.send-btn {
  background: #9c2030;
  color: #fff;
}
.send-btn:hover { background: #b5263d; }

/* MODALES */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.65);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 6000;
}

.modal {
  background: rgba(0,0,0,0.55);
  border-radius: 20px;
  padding: 32px;
  width: 340px;
  border: 1px solid rgba(255,255,255,0.18);
  text-align: center;
  animation: fadeZoom .35s ease;
  box-shadow: 0 12px 40px rgba(0,0,0,0.55);
}
@keyframes fadeZoom {
  from { transform: scale(.90); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.input {
  width: 100%;
  padding: 12px;
  border-radius: 12px;
  border: none;
  margin-top: 10px;
  background: rgba(255,255,255,0.14);
  color: white;
}

.modal-confirm {
  margin-top: 18px;
  padding: 12px;
  border-radius: 12px;
  width: 100%;
  background:#9c2030;
  color:#fff;
  border:none;
}
.modal-confirm:hover { background:#b5263d; }

.modal-close {
  margin-top: 10px;
  padding: 12px;
  width: 100%;
  border-radius: 12px;
  background: rgba(255,255,255,0.12);
  color: white;
  border: none;
}
.modal-close:hover { background: rgba(255,255,255,0.25); }
</style>
