<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import CartaDigital from './components/CartaDigital.vue'
import OrderServiceStatus from './components/OrderServiceStatus.vue'
import { cart, removeFromCart, clearCart, openLoginModal, saveCart } from './cart.js'
import axios from 'axios'
import { API_BASE } from './api.js'
import { loadCliente, getCliente, cliente, logoutCliente } from "./cliente.js"


loadCliente()

function applyClienteToken() {
  const token = cliente.value?.token
  if (token) {
    axios.defaults.headers.common.Authorization = `Bearer ${token}`
  } else {
    delete axios.defaults.headers.common.Authorization
  }
}

applyClienteToken()

const showCart = ref(false)
const cartButton = ref(null)
const sendingOrder = ref(false)
const noteItemId = ref(null)
const noteDraft = ref("")
const noteTextarea = ref(null)
const NOTE_MAX_LENGTH = 140
const pedidosCliente = ref([])
const loadingPedidos = ref(false)
const errorPedidos = ref("")
let pedidosInterval = null
const nowTick = ref(Date.now())
let holdCountdownInterval = null
const holdWindowSeconds = ref(300)
const showSendNowConfirm = ref(false)
const sendingNowToKitchen = ref(false)

const SERVICE_STATUS_PRIORITY = ['pendiente', 'preparando', 'listo', 'entregado']
const params = new URLSearchParams(window.location.search)
const mesaId = params.get('mesa')
if (mesaId) {
    localStorage.setItem('mesa_id', mesaId)
}

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
   🔥 LOGOUT
===================================================== */
async function handleLogout() {
  const loggedOut = await logoutCliente()
  if (loggedOut) {
    alert("👋 Sesión cerrada correctamente")
  }
}

const cartImageErrors = ref({})

function backendBaseUrl() {
  return (API_BASE || '').replace(/\/?api\/?$/, '')
}

function normalizePath(path) {
  return String(path)
    .split('/')
    .map(segment => encodeURIComponent(segment))
    .join('/')
}

function getItemImage(item) {
  if (item?.image_url) {
    return item.image_url
  }

  if (item?.image_path) {
    return `${backendBaseUrl()}/storage/${normalizePath(item.image_path)}`

  }

  return item?.imagen || null
}

function markCartImageError(itemId) {
  cartImageErrors.value = {
    ...cartImageErrors.value,
    [itemId]: true,
  }
}

function hasCartImage(item) {
  return Boolean(getItemImage(item)) && !cartImageErrors.value[item.id]
}



/* =====================================================
   🔥 TOTAL
===================================================== */
const totalPrice = computed(() =>
  cart.value.reduce((sum, item) => sum + item.precio * item.quantity, 0)
)

const activeNoteItem = computed(() =>
  cart.value.find(item => item.id === noteItemId.value) ?? null
)

const noteCharCount = computed(() => noteDraft.value.length)

function getNoteSummary(note) {
  if (!note) return ''
  return note.length > 38 ? `${note.slice(0, 38)}…` : note
}

function openNote(item) {
  noteItemId.value = item.id
  noteDraft.value = item.nota ?? ""

  nextTick(() => {
    noteTextarea.value?.focus()
  })
}

function closeNoteEditor() {
  noteItemId.value = null
  noteDraft.value = ""
}

function cancelNote() {
  closeNoteEditor()
}

function saveNote() {
  const item = cart.value.find(i => i.id === noteItemId.value)
  if (!item) {
    closeNoteEditor()
    return
  }

  const normalizedNote = noteDraft.value.trim()
  const nextNote = normalizedNote || null

  if ((item.nota || null) === nextNote) {
    closeNoteEditor()
    return
  }

  item.nota = nextNote
 
  saveCart()
  closeNoteEditor()
}

function handleNoteEditorEsc(event) {
  if (event.key === 'Escape' && noteItemId.value !== null) {
    cancelNote()
  }
}


/* =====================================================
   🔥 ENVIAR PEDIDO
===================================================== */
async function sendOrder() {

  if (sendingOrder.value) return

  sendingOrder.value = true

  try {
    const mesaId = localStorage.getItem('mesa_id') // 🔥 FALTABA ESTO

    console.log("MESA:", mesaId)

    const clienteActual = getCliente()
    const restaurantId = 1 

    await axios.post(`${API_BASE}/orders`, {
      mesa_id: mesaId,
      cliente_id: clienteActual ? clienteActual.id : null,
      restaurant_id: restaurantId,
      items: cart.value.map(i => ({
        menu_item_id: i.id,
        cantidad: i.quantity,
        precio_unitario: i.precio,
        nota: i.nota || null
      }))
    })

    clearCart()

    showToast("Pedido registrado. Tienes una ventana de cambios antes de cocina ✅", "success")

    loadPedidosCliente(true)

  } catch (error) {
    console.error(error) // 👈 agrega esto para debug
    showToast("Error enviando pedido ❌", "error")
  } finally {
    sendingOrder.value = false
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
    holdWindowSeconds.value = Number(res.data?.meta?.hold_window_seconds || holdWindowSeconds.value)
  } catch (error) {
    errorPedidos.value = "No pudimos cargar el estado del pedido."
  } finally {
    if (!silent) loadingPedidos.value = false
  }
}



async function confirmAndSendNowToKitchen() {
  if (!pedidoActual.value || sendingNowToKitchen.value) return

  const clienteActual = getCliente()

  try {
    sendingNowToKitchen.value = true
    await axios.post(`${API_BASE}/orders/${pedidoActual.value.id}/send-now`, {
      cliente_id: clienteActual?.id ?? null,
    })

    showSendNowConfirm.value = false
    showToast('Pedido enviado inmediatamente a cocina ✅', 'success')
    await loadPedidosCliente(true)
  } catch (error) {
    showToast(error?.response?.data?.message || 'No pudimos enviar el pedido a cocina.', 'error')
  } finally {
    sendingNowToKitchen.value = false
  }
}

const holdWindowMinutes = computed(() => Math.max(1, Math.round(holdWindowSeconds.value / 60)))

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
  () => {
    applyClienteToken()
  },
  { deep: true },
)

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
  holdCountdownInterval = setInterval(() => {
    nowTick.value = Date.now()
  }, 1000)

  const handler = () => {
    cartButton.value?.classList.remove("cart-bounce")
    void cartButton.value?.offsetWidth
    cartButton.value?.classList.add("cart-bounce")
  }

  window.addEventListener("cart-updated", handler)
  window.addEventListener('keydown', handleNoteEditorEsc)

  onUnmounted(() => {
    window.removeEventListener("cart-updated", handler)
    window.removeEventListener('keydown', handleNoteEditorEsc)
    clearInterval(holdCountdownInterval)
  })
})

/* =====================================================
   🔥 COMPUTEDS
===================================================== */
const pedidoActual = computed(() => pedidosCliente.value[0] ?? null)

function extractOrderItems(order) {
  const source = [
    order?.detalle,
    order?.detalles,
    order?.items,
    order?.order_items,
  ].find(Array.isArray)

  return source ?? []
}

function normalizeServiceStatus(rawStatus) {
  const normalized = String(rawStatus || '').toLowerCase().trim()
  if (normalized === 'en_preparacion') return 'preparando'
  return SERVICE_STATUS_PRIORITY.includes(normalized) ? normalized : 'pendiente'
}

function getStatusRank(status) {
  return SERVICE_STATUS_PRIORITY.indexOf(normalizeServiceStatus(status))
}

const pedidoConsolidadoServicio = computed(() => {
  const mergedByItem = new Map()

  for (const order of pedidosCliente.value) {
    const orderId = order?.id ?? 'order'
    const items = extractOrderItems(order)

    items.forEach((item, index) => {
      const detailId = item?.id ?? item?.pedido_detalle_id ?? item?.detalle_id
      const menuItemId = item?.menu_item_id ?? item?.menu_item?.id ?? item?.id_menu
      const uniqueKey = detailId
        ? `${orderId}:detalle:${detailId}`
        : `${orderId}:item:${menuItemId ?? 'unknown'}:${index}`

      const incomingStatus = normalizeServiceStatus(item?.estado_servicio || item?.estado)
      const existing = mergedByItem.get(uniqueKey)

      if (!existing || getStatusRank(incomingStatus) > getStatusRank(existing.estado_servicio)) {
        mergedByItem.set(uniqueKey, {
          ...item,
          estado_servicio: incomingStatus,
        })
      }
    })
  }

  return {
    id: 'consolidado-cliente',
    detalle: Array.from(mergedByItem.values()),
  }
})



const holdSecondsRemaining = computed(() => {
  if (!pedidoActual.value?.hold_expires_at) return 0
  const expiresAt = new Date(pedidoActual.value.hold_expires_at).getTime()
  const seconds = Math.ceil((expiresAt - nowTick.value) / 1000)
  return Math.max(0, seconds)
})

const holdCountdownLabel = computed(() => {
  const total = holdSecondsRemaining.value
  const minutes = Math.floor(total / 60)
  const seconds = total % 60
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

const isPedidoRetenido = computed(() => pedidoActual.value?.estado === 'retenido')

const isChangeRequested = computed(() => pedidoActual.value?.estado === 'modificacion_solicitada')

const holdWindowFinished = computed(() => Boolean(pedidoActual.value) && !isPedidoRetenido.value && !isChangeRequested.value)


const wasSentEarly = computed(() => pedidoActual.value?.release_trigger === 'early_confirmation')

const postReleaseMessage = computed(() => {
  if (!pedidoActual.value || isPedidoRetenido.value || isChangeRequested.value) return ''

  if (wasSentEarly.value) {
    return 'Tu pedido fue enviado a cocina por confirmación anticipada. Ya no es posible realizar cambios.'
  }
  return 'Tu pedido ya fue enviado a cocina. Ya no es posible realizar cambios.'
})


const changeRequestedMessage = computed(() => {
  if (!isChangeRequested.value) return ''
  return 'Tu solicitud de cambio fue registrada. Un mesero atenderá tu mesa para actualizar el pedido. Mientras tanto, tu pedido no se enviará a cocina.'
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

    <div class="cart-panel__header">
      <h2>Tu Pedido</h2>
      <button class="close-cart" @click="showCart = false" aria-label="Cerrar carrito">✦</button>
    </div>


    <!-- ================= ESTADO PEDIDO ================= -->
    
    <section v-if="cliente" class="order-status-card-pro">

  <div class="order-header-pro">

    <div class="order-header-copy">
      <h3 class="title">Estado de tu pedido</h3>
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


  <div v-else class="order-body-pro">

    <div v-if="isPedidoRetenido" class="hold-banner">
      <div class="hold-banner__icon" aria-hidden="true">⏱</div>
      <div class="hold-banner__content">
        <p>Tienes <strong>{{ holdCountdownLabel }}</strong> para llamar a un mesero y modificar tu pedido.</p>
        <small>Puedes solicitar modificaciones durante los próximos {{ holdWindowMinutes }} minutos.</small>
        <small class="hold-banner__warning">Si decides enviarlo ahora, ya no podrás realizar cambios.</small>
      </div>
      <span class="hold-banner__time">{{ holdCountdownLabel }}</span>
      <button class="send-now-btn" type="button" :disabled="!pedidoActual?.can_send_now" @click="showSendNowConfirm = true">
        Confirmar pedido y enviar a cocina
      </button>
    </div>

    <div v-else-if="isChangeRequested" class="hold-banner hold-banner--change-requested">
      <div class="hold-banner__icon" aria-hidden="true">📝</div>
      <strong>{{ changeRequestedMessage }}</strong>

    </div>

    <div v-else-if="holdWindowFinished" class="hold-banner hold-banner--done">
      <div class="hold-banner__icon" aria-hidden="true">✅</div>
      <strong>{{ postReleaseMessage }}</strong>
    </div>


    <OrderServiceStatus v-if="pedidoConsolidadoServicio.detalle.length" :order="pedidoConsolidadoServicio" />

  </div>

  <p v-if="errorPedidos" class="order-error">{{ errorPedidos }}</p>

</section>



    <!-- ================= CONTENEDOR SCROLL ================= -->
<div class="cart-scroll">

  <h4 class="cart-section-title">Carrito</h4>

  <div v-if="cart.length === 0" class="cart-empty-state">
    Carrito vacío
  </div>

  <div
    v-for="item in cart"
    :key="item.id"
    class="cart-item"
  >
    <img
      v-if="hasCartImage(item)"
      :src="getItemImage(item)"
      class="cart-thumb"
      alt="Imagen del plato"
      @error="markCartImageError(item.id)"
    />
    <div v-else class="cart-thumb cart-thumb-placeholder">

      <span>🍽</span>
    </div>

    <div class="cart-item-info">
      <div class="cart-item-name">{{ item.nombre }}</div>
      <div class="cart-item-meta">
        <span class="qty">x {{ item.quantity }}</span>
        <span class="item-price">${{ (item.precio * item.quantity).toLocaleString() }}</span>
      </div>

      <div class="cart-note-row">
        <button
          class="cart-note-btn"
          type="button"
          @click="openNote(item)"
        >
          <span class="note-btn-icon" aria-hidden="true">✎</span>
          {{ item.nota ? 'Editar nota' : 'Añadir nota' }}
        </button>

        <span v-if="item.nota" class="cart-note-status">Guardado</span>
      </div>

      <p v-if="item.nota" class="cart-note-preview">{{ getNoteSummary(item.nota) }}</p>

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
  {{ sendingOrder ? 'Enviando...' : 'Confirmar pedido' }}

</button>


</div>


  </div>

<transition name="note-modal">
  <div
    v-if="activeNoteItem"
    class="note-modal-backdrop"
    @click.self="cancelNote"
  >
    <div class="note-modal" role="dialog" aria-modal="true" :aria-label="`Nota para: ${activeNoteItem.nombre}`">
      <button class="note-close" type="button" aria-label="Cerrar" @click="cancelNote">✕</button>
      <h3>Nota para: {{ activeNoteItem.nombre }}</h3>


      <textarea
        ref="noteTextarea"
        v-model="noteDraft"
        class="note-textarea"
        :maxlength="NOTE_MAX_LENGTH"
        placeholder="Ej: Sin cebolla, salsa aparte…"
      ></textarea>

      <div class="note-meta">
        <span>Instrucciones especiales</span>
        <strong>{{ noteCharCount }}/{{ NOTE_MAX_LENGTH }}</strong>
      </div>


      <div class="note-actions">
        <button type="button" class="note-cancel" @click="cancelNote">Cancelar</button>
        <button type="button" class="note-save" @click="saveNote">Guardar</button>
      </div>
    </div>
  </div>
</transition>

<transition name="note-modal">
  <div
    v-if="showSendNowConfirm"
    class="note-modal-backdrop"
    @click.self="showSendNowConfirm = false"
  >
    <div class="note-modal send-now-modal" role="dialog" aria-modal="true" aria-label="Confirmar envío a cocina">
      <h3>¿Deseas enviar tu pedido ahora a cocina?</h3>
      <p>Una vez enviado, ya no podrás realizar cambios en el pedido.</p>
      <div class="send-now-modal__actions">
        <button type="button" class="note-cancel" @click="showSendNowConfirm = false" :disabled="sendingNowToKitchen">Cancelar</button>
        <button type="button" class="note-save" @click="confirmAndSendNowToKitchen" :disabled="sendingNowToKitchen">
          {{ sendingNowToKitchen ? 'Enviando...' : 'Enviar ahora' }}
        </button>
      </div>
    </div>
  </div>
</transition>

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

  padding: 18px 18px 0;
  color: #fff;

  display: flex;
  flex-direction: column;

  z-index: 4000;

  overflow: hidden; /* 🔥 CLAVE */
}

.cart-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.cart-panel__header h2 {
  margin: 0;
  font-size: 22px;
  font-weight: 700;
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

.cart-note-row {
  margin-top: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.cart-note-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 12px;
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,0.26);
  background: linear-gradient(145deg, rgba(255,255,255,0.14), rgba(255,255,255,0.05));
  color: #f5ece6;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: transform .2s ease, border-color .2s ease, background .2s ease;
}

.cart-note-btn:hover {
  transform: translateY(-1px);
  border-color: rgba(255,215,170,0.65);
  background: linear-gradient(145deg, rgba(255,255,255,0.20), rgba(255,255,255,0.07));
}

.note-btn-icon {
  font-size: 12px;
  opacity: .9;
}

.cart-note-status {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .2px;
  color: #9df1c6;
  background: rgba(46,204,113,0.16);
  border: 1px solid rgba(46,204,113,0.38);
  border-radius: 999px;
  padding: 3px 8px;
}

.cart-note-preview {
  margin: 6px 0 0;
  font-size: 11px;
  line-height: 1.25;
  color: rgba(255,255,255,0.72);
}


.note-modal-enter-active,
.note-modal-leave-active {
  transition: opacity .24s ease;
}

.note-modal-enter-active .note-modal,
.note-modal-leave-active .note-modal {
  transition: transform .24s cubic-bezier(.22,.61,.36,1), opacity .24s ease;

}

.note-modal-enter-from,
.note-modal-leave-to {
  opacity: 0;
}

.note-modal-enter-from .note-modal,
.note-modal-leave-to .note-modal {
  transform: scale(.94);

  opacity: 0;
}

.note-modal-backdrop {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(4,4,6,0.68);
  backdrop-filter: blur(10px);
  z-index: 6500;
  padding: 16px;
}

.note-modal {
  position: relative;
  width: min(92vw, 520px);
  background: linear-gradient(155deg, rgba(26,26,28,0.84), rgba(18,18,18,0.74));
  border: 1px solid rgba(255,255,255,0.16);
  border-radius: 20px;
  box-shadow: 0 24px 56px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.08);
  padding: 22px;
}

.note-close {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 30px;
  height: 30px;
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,0.20);
  background: rgba(255,255,255,0.08);
  color: #fff;
  cursor: pointer;
}

.note-modal h3 {
  margin: 0 34px 12px 0;
  color: #f8ece4;
  font-size: 16px;
  font-weight: 600;

}

.note-textarea {
  width: 100%;
  min-height: 118px;
  max-height: 240px;
  resize: vertical;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.20);
  background: rgba(255,255,255,0.05);
  color: #fff;
  padding: 13px;
  font-size: 13px;
  line-height: 1.35;

  outline: none;
}

.note-textarea:focus {
  border-color: rgba(255,215,170,0.82);
  box-shadow: 0 0 0 3px rgba(255,215,170,0.12);

}

.note-meta {
  margin-top: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11px;
  color: rgba(255,255,255,0.74);

}

.note-actions {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.note-cancel,
.note-save {
  border-radius: 12px;
  padding: 10px 16px;

  border: 1px solid transparent;
  cursor: pointer;
  font-weight: 600;
}

.note-cancel {
  background: rgba(255,255,255,0.09);
  border-color: rgba(255,255,255,0.18);
  color: #fff;
}

.note-save {
  background: linear-gradient(135deg,#b62232,#d92f45);
  color: #fff;
}

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
  background: linear-gradient(145deg, rgba(255,255,255,.07), rgba(255,255,255,.03));
  border: 1px solid rgba(255,255,255,.14);
  backdrop-filter: blur(18px);
  border-radius: 18px;
  padding: 16px;
  display:flex;
  flex-direction:column;
  gap:14px;
  box-shadow:
    0 8px 24px rgba(0,0,0,.45),
    inset 0 1px 0 rgba(255,255,255,.08);
}

/* HEADER */
.order-header-pro{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap: 12px;
}

.order-header-copy {
  min-width: 0;
}

.title{
  font-size:16px;
  font-weight:700;
  margin:0;
}

/* BOTÓN REFRESH PRO */
.refresh-pro{
  display:flex;
  align-items:center;
  gap:6px;
  background:rgba(255,255,255,.09);
  border:1px solid rgba(255,255,255,.18);
  color:#fff;
  padding:8px 11px;
  border-radius:12px;
  cursor:pointer;
  font-size:12px;
  transition:.25s;
  flex-shrink: 0;
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

.order-body-pro {
  display: grid;
  gap: 10px;
}

.order-status-card-pro :deep(.order-service-status) {
  gap: 8px;
}

/* vacío */
.empty-pro{
  opacity:.75;
  font-size:13px;
  text-align:center;
  padding: 10px 0;
}
/* SOLO la lista scrollea */
.cart-scroll {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding-right: 4px;
  margin-top: 14px;
  border-top: 1px solid rgba(255,255,255,.09);
  padding-top: 14px;
}

.cart-section-title {
  margin: 0 0 12px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: .08em;
  opacity: .72;
}

.cart-empty-state {
  color: rgba(255,255,255,.72);
  font-size: 14px;
  padding: 12px;
  border: 1px dashed rgba(255,255,255,.2);
  border-radius: 12px;
  text-align: center;
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
  bottom: 0;
  z-index: 2;
  margin-top: 12px;

  padding: 14px 0 18px;

  display: flex;
  flex-direction: column;
  gap: 14px;

  /* glass premium más liviano */
  background: linear-gradient(to top, rgba(12,12,14,0.96) 65%, rgba(12,12,14,0));
  backdrop-filter: blur(20px);
}


/* =====================================================
   TOTAL ESTILO BADGE / TARJETA COMPACTA
===================================================== */

.cart-footer h3{
  margin:0;

  display:flex;
  justify-content:space-between;
  align-items:center;

  padding: 10px 12px;

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



.hold-banner {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(126, 203, 255, 0.14), rgba(126, 203, 255, 0.06));
  border: 1px solid rgba(126, 203, 255, 0.36);
}

.hold-banner__icon {
  width: 30px;
  height: 30px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  background: rgba(255,255,255,.12);
  font-size: 15px;
}

.hold-banner__content {
  min-width: 0;
}

.hold-banner__content p {
  margin: 0;
  font-size: 12px;
  line-height: 1.35;
}

.hold-banner__content small {
  display: block;
  margin-top: 2px;
  opacity: .8;
  font-size: 11px;
}
.hold-banner__warning {
  color: #ffd7aa;
  opacity: 1;
}

.send-now-btn {
  grid-column: 1 / -1;
  justify-self: start;
  margin-left: 40px;
  margin-top: 2px;
  padding: 7px 12px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,.22);
  background: rgba(255,255,255,.1);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
}

.send-now-btn:hover {
  background: rgba(156, 32, 48, .45);
}

.send-now-btn:disabled {
  opacity: .45;
  cursor: not-allowed;
}


.send-now-modal p {
  margin: 8px 0 0;
  color: rgba(255,255,255,.8);
  line-height: 1.4;
}

.send-now-modal__actions {
  display: flex;
  gap: 10px;
  margin-top: 14px;
}

.send-now-modal__actions .note-cancel,
.send-now-modal__actions .note-save {
  margin-top: 0;
  flex: 1;
}


.hold-banner__time {
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: .03em;
  font-size: 13px;
  padding: 6px 8px;
  border-radius: 10px;
  background: rgba(7, 19, 34, .42);
  border: 1px solid rgba(255,255,255,.14);
}
.hold-banner--change-requested {
  grid-template-columns: auto 1fr;
  background: linear-gradient(135deg, rgba(255, 211, 123, 0.14), rgba(255, 211, 123, 0.05));
  border-color: rgba(255, 211, 123, 0.35);
}


.hold-banner--done {
  grid-template-columns: auto 1fr;
  background: linear-gradient(135deg, rgba(110, 247, 176, 0.14), rgba(110, 247, 176, 0.06));
  border-color: rgba(110, 247, 176, 0.35);
}

@media (max-width: 640px) {
  .cart-panel {
    width: min(100vw, 430px);
    padding: 14px 14px 0;
  }

  .order-status-card-pro {
    border-radius: 16px;
    padding: 14px;
  }

  .order-header-pro {
    align-items: center;
  }

  .title {
    font-size: 15px;
  }

  .refresh-pro {
    padding: 7px 9px;
    font-size: 11px;
  }

  .hold-banner {
    grid-template-columns: auto 1fr;
    gap: 8px;
    padding: 9px 10px;
  }

  .hold-banner__time {
    grid-column: 1 / -1;
    justify-self: start;
    margin-left: 38px;
    font-size: 12px;
  }

  .send-now-btn {
    margin-left: 38px;
    width: calc(100% - 38px);
    box-sizing: border-box;
    text-align: center;
  }

}

</style>
