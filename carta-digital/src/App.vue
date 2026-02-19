<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import CartaDigital from './components/CartaDigital.vue'
import { cart, removeFromCart, clearCart, openLoginModal } from './cart.js'
import axios from 'axios'
import { API_BASE } from './api.js'
import { loadCliente, getCliente, cliente, logoutCliente } from "./cliente.js"


loadCliente()

const showCart = ref(false)
const cartButton = ref(null)
const sendingOrder = ref(false)
const pedidosCliente = ref([])
const loadingPedidos = ref(false)
const errorPedidos = ref("")
let pedidosInterval = null

// =========================
// 🔙 VOLVER AL ADMIN (SOLO SI VIENE DESDE ADMIN)
// =========================
const showBackToAdmin = ref(false)
const backToAdminUrl = ref("")

function detectAdminEntry() {
  const params = new URLSearchParams(window.location.search)
  const adminLinkEncoded = params.get("admin_link")
  if (!adminLinkEncoded) return // cliente normal

  try {
    // 1) admin_link viene URL-encoded
    const adminLink = decodeURIComponent(adminLinkEncoded)

    // 2) extraemos el return dentro de esa URL firmada
    const inside = new URL(adminLink)
    const retEncoded = inside.searchParams.get("return")
    if (!retEncoded) return

    // 3) return viene doble-encoded en tu ejemplo
    const ret = decodeURIComponent(retEncoded)

    showBackToAdmin.value = true
    backToAdminUrl.value = ret
  } catch (e) {
    // si algo falla, no mostramos botón
    showBackToAdmin.value = false
    backToAdminUrl.value = ""
  }
}


/* =========================
   🔥 TOAST NOTIFICACIÓN PRO
========================= */
const toast = ref({
  show: false,
  message: "",
  type: "success"
})

function showToast(message, type = "success") {
  toast.value.message = message
  toast.value.type = type
  toast.value.show = true

  setTimeout(() => {
    toast.value.show = false
  }, 2500)
}

/* =====================================================
   🔥 TIMELINE
===================================================== */
const timelineSteps = ['pendiente', 'preparando', 'listo', 'entregado']

const stepLabels = {
  pendiente: 'Pendiente',
  preparando: 'En cocina',
  listo: 'Listo',
  entregado: 'Entregado'
}


/* =====================================================
   🔥 LOGOUT
===================================================== */
function handleLogout() {
  logoutCliente()
  alert("👋 Sesión cerrada correctamente")
}

function getItemImage(item) {
  if (item?.image_path) {
    return `/storage/${item.image_path}`
  }

  return item?.imagen || null
}


/* =====================================================
   🔥 TOTAL
===================================================== */
const totalPrice = computed(() =>
  cart.value.reduce((sum, item) => sum + item.precio * item.quantity, 0)
)


/* =====================================================
   🔥 ENVIAR PEDIDO
===================================================== */
async function sendOrder() {

  if (sendingOrder.value) return // 🔥 evita doble click

  sendingOrder.value = true

  try {
    const clienteActual = getCliente()

    await axios.post(`${API_BASE}/orders`, {
      mesa: null,
      cliente_id: clienteActual ? clienteActual.id : null,
      restaurant_id: 1,
      items: cart.value.map(i => ({
        menu_item_id: i.id,
        cantidad: i.quantity,
        precio_unitario: i.precio
      }))
    })

    clearCart()

    showToast("Pedido enviado correctamente ✅", "success")

    loadPedidosCliente(true)

  } catch (error) {

    showToast("Error enviando pedido ❌", "error")

  } finally {

    sendingOrder.value = false // 🔥 vuelve a habilitar

  }
}



/* =====================================================
   🔥 CARGAR PEDIDOS
===================================================== */
async function loadPedidosCliente(silent = false) {

  const clienteActual = cliente.value
  if (!clienteActual) return

  if (!silent) loadingPedidos.value = true
  errorPedidos.value = ""

  try {
    const res = await axios.get(`${API_BASE}/clientes/${clienteActual.id}/pedidos`)
    pedidosCliente.value = res.data.data ?? res.data
  } catch (error) {
    errorPedidos.value = "No pudimos cargar el estado del pedido."
  } finally {
    if (!silent) loadingPedidos.value = false
  }
}


/* =====================================================
   🔥 POLLING SILENCIOSO
===================================================== */
function clearPedidosPolling() {
  if (pedidosInterval) {
    clearInterval(pedidosInterval)
    pedidosInterval = null
  }
}

watch(
  cliente,
  (nuevo) => {
    clearPedidosPolling()
    pedidosCliente.value = []

    if (nuevo) {
      loadPedidosCliente(true)

      pedidosInterval = setInterval(() => {
        loadPedidosCliente(true) // 🔥 silencioso
      }, 6000)
    }
  },
  { immediate: true }
)


/* =====================================================
   🔥 CART BOUNCE
===================================================== */
onMounted(() => {
  detectAdminEntry()

  const handler = () => {
    cartButton.value?.classList.remove("cart-bounce")
    void cartButton.value?.offsetWidth
    cartButton.value?.classList.add("cart-bounce")
  }

  window.addEventListener("cart-updated", handler)

  onUnmounted(() => {
    window.removeEventListener("cart-updated", handler)
  })
})

/* =====================================================
   🔥 COMPUTEDS
===================================================== */
const pedidoActual = computed(() => pedidosCliente.value[0] ?? null)

const currentStepIndex = computed(() => {
  if (!pedidoActual.value) return -1
  const estado = (pedidoActual.value.estado || '').toLowerCase()
  return timelineSteps.indexOf(estado)
})
</script>



<template>
<div>
<!-- ✅ TOPBAR SOLO ADMIN -->
<div v-if="showBackToAdmin" class="admin-topbar">
  <a :href="backToAdminUrl" class="admin-back-btn">
    ← Volver al Admin
  </a>

  <div class="admin-brand">
    <span class="admin-pill">Modo Admin</span>
  </div>

  <div class="admin-right"></div>
</div>

<!-- ✅ espacio para que NO se monte con el resto -->
<div v-if="showBackToAdmin" class="admin-topbar-spacer"></div>



  <!-- BOTÓN CARRITO -->
  <button
    ref="cartButton"
    class="cart-floating"
    @click="showCart = !showCart"
  >
    🛒 <span>{{ cart.length }}</span>
  </button>

  <CartaDigital />


  <!-- PANEL CARRITO -->
  <div v-if="showCart" class="cart-panel">

    <button class="close-cart" @click="showCart = false">✦</button>

    <h2>Tu Pedido</h2>


    <!-- ================= ESTADO PEDIDO ================= -->
    
    <section v-if="cliente" class="order-status-card-pro">

  <div class="order-header-pro">

    <div>
      <h3 class="title">📦 Estado de tu pedido</h3>
      <p class="subtitle">Seguimiento en tiempo real</p>
    </div>

    <button
      class="refresh-pro"
      @click="loadPedidosCliente(false)"
      :disabled="loadingPedidos"
    >
      <span :class="{ spin: loadingPedidos }">⟳</span>
      {{ loadingPedidos ? 'Actualizando' : 'Actualizar' }}
    </button>

  </div>


  <div v-if="!pedidoActual && !loadingPedidos" class="empty-pro">
    Aún no tienes pedidos activos
  </div>


  <div v-else class="timeline-pro">

    <!-- BARRA PROGRESO -->
    <div
      class="progress-bar"
      :style="{ width: ((currentStepIndex+1)/timelineSteps.length*100)+'%' }"
    ></div>

    <div
      v-for="(step, index) in timelineSteps"
      :key="step"
      class="step-pro"
      :class="{
        completed: index < currentStepIndex,
        active: index === currentStepIndex
      }"
    >
      <div class="circle"></div>
      <span>{{ stepLabels[step] }}</span>
    </div>

  </div>


  <div v-if="pedidoActual" class="status-now">
    Estado actual:
    <strong>
      {{ stepLabels[timelineSteps[currentStepIndex]] }}
    </strong>
  </div>

  <p v-if="errorPedidos" class="order-error">{{ errorPedidos }}</p>

</section>



    <!-- ================= CONTENEDOR SCROLL ================= -->
<div class="cart-scroll">

  <div v-if="cart.length === 0">
    Carrito vacío
  </div>

  <div
    v-for="item in cart"
    :key="item.id"
    class="cart-item"
  >
    <img
      v-if="getItemImage(item)"
      :src="getItemImage(item)"
      class="cart-thumb"
      alt="Imagen del plato"
      @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display='flex'"
    />
    <div class="cart-thumb cart-thumb-placeholder" :style="{ display: getItemImage(item) ? 'none' : 'flex' }">
      <span>🍽</span>
    </div>

    <div class="cart-item-info">
      <div class="cart-item-name">{{ item.nombre }}</div>
      <div class="cart-item-meta">
        <span class="qty">x {{ item.quantity }}</span>
        <span class="item-price">${{ (item.precio * item.quantity).toLocaleString() }}</span>
      </div>
    </div>

    <button
      class="remove-item"
      @click="removeFromCart(item.id)"
    >
      🗑
    </button>
  </div>

</div>


<!-- ================= FOOTER FIJO ================= -->
<div v-if="cart.length > 0" class="cart-footer">

  <h3>
    Total: ${{ totalPrice.toLocaleString() }}
  </h3>

  <button
    class="clear-btn"
    @click="clearCart"
  >
    Vaciar
  </button>

  <button
  class="send-btn"
  @click="sendOrder"
  :disabled="sendingOrder"
>
  <span v-if="sendingOrder" class="spinner"></span>
  {{ sendingOrder ? 'Enviando...' : 'Enviar a cocina' }}
</button>


</div>




  </div>
<!-- 🔥 TOAST -->
<transition name="toast">
  <div
    v-if="toast.show"
    class="toast"
    :class="toast.type"
  >
    {{ toast.message }}
  </div>
</transition>

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

  padding: 24px;
  color: #fff;

  display: flex;
  flex-direction: column;

  z-index: 4000;

  overflow: hidden; /* 🔥 CLAVE */
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
  align-items: center;
  gap: 12px;
  padding: 14px 0;
  border-bottom: 1px solid rgba(255,255,255,0.15);
}
.cart-thumb {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  object-fit: cover;
  border: 1px solid rgba(255,255,255,0.2);
  background: #161214;
  flex-shrink: 0;
}
.cart-thumb-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255,255,255,0.75);
}
.cart-item-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
  min-width: 0;
}
.cart-item-name {
  font-size: 14px;
  font-weight: 600;
  color: #f8ece4;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.cart-item-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}
.qty { opacity: 0.8; font-size: 13px; }
.item-price { color: #ffd7aa; font-size: 13px; font-weight: 600; }

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
/* =====================================================
   🔥 CARD PRO ESTADO PEDIDO (ULTRA MODERNO)
===================================================== */

.order-status-card-pro{
  background: linear-gradient(145deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
  border: 1px solid rgba(255,255,255,.12);
  backdrop-filter: blur(18px);
  border-radius: 20px;
  padding: 20px;
  display:flex;
  flex-direction:column;
  gap:18px;
  box-shadow:
    0 8px 28px rgba(0,0,0,.55),
    inset 0 1px 0 rgba(255,255,255,.08);
}

/* HEADER */
.order-header-pro{
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.title{
  font-size:17px;
  font-weight:600;
  margin:0;
}

.subtitle{
  font-size:12px;
  opacity:.65;
  margin:2px 0 0;
}

/* BOTÓN REFRESH PRO */
.refresh-pro{
  display:flex;
  align-items:center;
  gap:6px;
  background:rgba(255,255,255,.08);
  border:1px solid rgba(255,255,255,.18);
  color:#fff;
  padding:7px 12px;
  border-radius:12px;
  cursor:pointer;
  font-size:12px;
  transition:.25s;
}

.refresh-pro:hover{
  background:#9c2030;
  transform:translateY(-2px);
}

.spin{
  animation:spin 1s linear infinite;
}

@keyframes spin{
  from{ transform:rotate(0) }
  to{ transform:rotate(360deg) }
}

/* TIMELINE PRO */
.timeline-pro{
  position:relative;
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:20px 6px 0;
}

/* línea base */
.timeline-pro::before{
  content:"";
  position:absolute;
  top:26px;
  left:0;
  right:0;
  height:2px;
  background:rgba(255,255,255,.15);
}

/* barra progreso animada */
.progress-bar{
  position:absolute;
  top:26px;
  left:0;
  height:2px;
  background:linear-gradient(90deg,#2ecc71,#ffd7aa);
  transition:width .6s ease;
}

/* pasos */
.step-pro{
  display:flex;
  flex-direction:column;
  align-items:center;
  font-size:11px;
  text-align:center;
  gap:8px;
  z-index:2;
}

/* círculo */
.circle{
  width:14px;
  height:14px;
  border-radius:50%;
  background:rgba(255,255,255,.15);
  border:2px solid rgba(255,255,255,.25);
  transition:.35s;
}

/* completado */
.step-pro.completed .circle{
  background:#2ecc71;
  border-color:#2ecc71;
  box-shadow:0 0 10px rgba(46,204,113,.6);
}

/* activo */
.step-pro.active .circle{
  background:#ffd7aa;
  border-color:#ffd7aa;
  box-shadow:0 0 12px rgba(255,215,170,.7);
  transform:scale(1.15);
}

/* estado actual */
.status-now{
  text-align:center;
  font-size:13px;
  background:rgba(255,255,255,.06);
  padding:8px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.12);
}

.status-now strong{
  color:#ffd7aa;
}

/* vacío */
.empty-pro{
  opacity:.7;
  font-size:13px;
  text-align:center;
}
/* SOLO la lista scrollea */
.cart-scroll {
  flex: 1;
  overflow-y: auto;
  padding-right: 4px;
}

/* footer siempre visible */
/* =====================================================
   🔥 FOOTER PROFESIONAL PREMIUM (NUEVO)
===================================================== */

/* =====================================================
   💎 FOOTER ULTRA PROFESIONAL (VERSIÓN FINAL PRO)
===================================================== */

.cart-footer {

  position: sticky;
  bottom: 22px;

  margin-top: 22px;

  padding: 14px;

  display: flex;
  flex-direction: column;
  gap: 14px;

  /* glass premium más liviano */
  background: rgba(18,18,18,.65);
  backdrop-filter: blur(20px);

  border-radius: 20px;
  border: 1px solid rgba(255,255,255,.08);

  box-shadow:
    0 12px 35px rgba(0,0,0,.55),
    inset 0 1px 0 rgba(255,255,255,.04);
}


/* =====================================================
   TOTAL ESTILO BADGE / TARJETA COMPACTA
===================================================== */

.cart-footer h3{
  margin:0;

  display:flex;
  justify-content:space-between;
  align-items:center;

  padding: 10px 16px;

  font-size: 14px;
  font-weight: 500;

  border-radius: 14px;

  background: linear-gradient(
    135deg,
    rgba(255,255,255,.07),
    rgba(255,255,255,.03)
  );

  border: 1px solid rgba(255,255,255,.08);

  letter-spacing:.3px;
}

/* etiqueta TOTAL pequeña */
.cart-footer h3::before{
  content:"Total";
  font-size:12px;
  opacity:.55;
  font-weight:400;
}

/* precio destacado */
.cart-footer h3{
  color:#ffd7aa;
  font-weight:700;
}


/* =====================================================
   CONTENEDOR BOTONES
===================================================== */

.cart-footer .actions{
  display:flex;
  gap:10px;
}


/* =====================================================
   BOTÓN VACIAR (SECUNDARIO SUAVE)
===================================================== */

.clear-btn{
  flex:1;

  padding:11px 12px;

  font-size:14px;

  background: rgba(255,255,255,.05);
  border:1px solid rgba(255,255,255,.12);

  border-radius:14px;

  color:#ffbaba;

  transition:.2s;
}

.clear-btn:hover{
  background: rgba(255,80,80,.22);
  transform: translateY(-1px);
}


/* =====================================================
   BOTÓN ENVIAR (PRIMARIO PRO)
===================================================== */

.send-btn{
  flex:2;

  padding:12px;

  font-size:14px;
  font-weight:600;

  border-radius:14px;
  border:none;

  background: linear-gradient(135deg,#b62232,#d92f45);

  box-shadow: 0 8px 20px rgba(217,47,69,.35);

  transition:.22s;
}

.send-btn:hover{
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(217,47,69,.55);
}
/* =========================
   🔥 TOAST PRO MODERNO
========================= */
.toast{
  position: fixed;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%);
  padding: 14px 22px;
  border-radius: 14px;
  font-weight: 600;
  font-size: 14px;
  backdrop-filter: blur(12px);
  box-shadow: 0 10px 28px rgba(0,0,0,.45);
  z-index: 9999;
  animation: pop .25s ease;
}

/* colores */
.toast.success{
  background: linear-gradient(135deg,#2ecc71,#27ae60);
  color: white;
}

.toast.error{
  background: linear-gradient(135deg,#e74c3c,#c0392b);
  color: white;
}

/* animaciones */
.toast-enter-active,
.toast-leave-active{
  transition: all .25s ease;
}

.toast-enter-from,
.toast-leave-to{
  opacity:0;
  transform:translate(-50%,20px);
}

@keyframes pop{
  from{ transform:translate(-50%,20px) scale(.95); opacity:0 }
  to{ transform:translate(-50%,0) scale(1); opacity:1 }
}

/* =========================
   🔥 BOTÓN LOADING STATE
========================= */

.send-btn:disabled{
  opacity: .65;
  cursor: not-allowed;
  transform: none !important;
}

/* spinner minimalista */
.spinner{
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid rgba(255,255,255,.4);
  border-top: 2px solid #fff;
  display: inline-block;
  margin-right: 8px;
  animation: spinBtn .6s linear infinite;
  vertical-align: middle;
}

@keyframes spinBtn{
  to{ transform: rotate(360deg); }
}

/* 🔙 Volver al Admin (solo cuando admin_link es válido) */

/* =========================
   🧠 ADMIN TOPBAR (PRO)
========================= */

/* =========================
   🧠 ADMIN TOPBAR — PRO CLEAN
========================= */

.admin-topbar{
  position: fixed;
  top: 18px;
  left: 18px;
  right: 18px;
  z-index: 9999;

  display: flex;
  justify-content: space-between;
  align-items: center;

  pointer-events: none; /* 🔥 hace que solo los botones reciban click */
}


/* ✅ baja el contenido para que no se pise con el header/logo */
.admin-topbar-spacer{
  height: 10px;
}

/* botón pro */
.admin-back-btn{
  pointer-events: auto;

  display: inline-flex;
  align-items: center;
  gap: 10px;

  padding: 9px 16px;
  border-radius: 14px;

  font-size: 13px;
  font-weight: 600;

  color: #fff;
  text-decoration: none;

  /* 💎 floating clean */
  background: rgba(20,20,20,.55);
  backdrop-filter: blur(10px);

  border: 1px solid rgba(255,255,255,.12);

  box-shadow:
    0 6px 18px rgba(0,0,0,.45),
    inset 0 1px 0 rgba(255,255,255,.05);

  transition: all .18s ease;
}

/* hover */
.admin-back-btn:hover{
  background: rgba(30,30,30,.7);
  transform: translateY(-1px);
}

/* click */
.admin-back-btn:active{
  transform: translateY(0);
}

/* centro */
.admin-brand{
  display:flex;
  justify-content:center;
  flex: 1;
}

.admin-pill{
  font-size: 12px;
  font-weight: 700;
  padding: 6px 10px;
  border-radius: 999px;

  background: rgba(156,32,48,.22);
  border: 1px solid rgba(156,32,48,.45);
  color: #ffd7aa;

  letter-spacing: .25px;
}

/* derecha vacía para balance visual */
.admin-right{
  width: 140px; /* similar al ancho del botón para centrar el título */
}

</style>
