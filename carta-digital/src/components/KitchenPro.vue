<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import NotePreview from './NotePreview.vue'
import {
  deliverKitchenOrder,
  fetchKitchenOrders,
  readyKitchenOrder,
  startKitchenOrder,
} from '../services/kitchenApi'

const props = defineProps({
  pollingMs: {
    type: Number,
    default: 4000,
  },
  deliveredHideMs: {
    type: Number,
    default: 15 * 60 * 1000,
  },
  realtimeMode: {
    type: String,
    default: 'polling', // polling | websocket
  },
  websocketUrl: {
    type: String,
    default: '',
  },
})

const loading = ref(false)
const error = ref('')
const orders = ref([])
const nowTs = ref(Date.now())
const highlightedOrderIds = ref(new Set())
const soundEnabled = ref(true)
const activeRequest = ref(new Set())
const socketState = ref('disconnected')
const ws = ref(null)

let pollingHandle
let nowHandle

const statusConfig = {
  pendiente: { title: '🟡 Pendientes', key: 'pending' },
  preparando: { title: '🔥 En preparación', key: 'preparing' },
  listo: { title: '✅ Listos', key: 'ready' },
  entregado: { title: '📦 Entregados', key: 'delivered' },
}

const statusOrder = ['pendiente', 'preparando', 'listo', 'entregado']

const normalizedOrders = computed(() => {
  const currentNow = nowTs.value

  return orders.value
    .map((order) => {
      const createdAtTs = new Date(order.created_at).getTime()
      const elapsedMs = Math.max(currentNow - createdAtTs, 0)
      const elapsedMinutes = elapsedMs / 60000
      const urgencyScore = elapsedMinutes + (order.estado === 'pendiente' ? 2 : 0)

      return {
        ...order,
        _createdAtTs: createdAtTs,
        _elapsedMs: elapsedMs,
        _elapsedMinutes: elapsedMinutes,
        _urgencyScore: urgencyScore,
      }
    })
    .filter((order) => {
      if (order.estado !== 'entregado') return true
      return nowTs.value - order._createdAtTs < props.deliveredHideMs
    })
})

const groupedOrders = computed(() => {
  const groups = {
    pendiente: [],
    preparando: [],
    listo: [],
    entregado: [],
  }

  for (const order of normalizedOrders.value) {
    const status = (order.estado || '').toLowerCase()
    if (groups[status]) groups[status].push(order)
  }

  groups.pendiente.sort((a, b) => b._urgencyScore - a._urgencyScore)
  groups.preparando.sort((a, b) => b._urgencyScore - a._urgencyScore)
  groups.listo.sort((a, b) => b._createdAtTs - a._createdAtTs)
  groups.entregado.sort((a, b) => b._createdAtTs - a._createdAtTs)

  return groups
})

const activeOrdersCount = computed(
  () =>
    groupedOrders.value.pendiente.length +
    groupedOrders.value.preparando.length +
    groupedOrders.value.listo.length,
)

const delayedOrdersCount = computed(
  () => normalizedOrders.value.filter((order) => order._elapsedMinutes > 6 && order.estado !== 'entregado').length,
)

const averageMinutes = computed(() => {
  const active = normalizedOrders.value.filter((order) => order.estado !== 'entregado')
  if (!active.length) return 0

  const total = active.reduce((acc, order) => acc + order._elapsedMinutes, 0)
  return total / active.length
})

const orderedColumns = computed(() =>
  statusOrder.map((status) => ({
    status,
    title: statusConfig[status].title,
    orders: groupedOrders.value[status],
  })),
)

function formatElapsed(elapsedMs) {
  const totalSeconds = Math.floor(elapsedMs / 1000)
  const minutes = Math.floor(totalSeconds / 60)
  const seconds = totalSeconds % 60
  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
}

function timerClass(order) {
  if (order._elapsedMinutes > 6) return 'timer--critical'
  if (order._elapsedMinutes >= 3) return 'timer--warn'
  return 'timer--ok'
}

function getItemNote(item) {
  return item?.nota || item?.notas || item?.note || ''
}

function getItemContext(item) {
  const qty = item?.cantidad ?? item?.quantity ?? 1
  const name = item?.nombre ?? item?.menu_item?.nombre ?? 'Ítem'
  return `${qty}x ${name}`
}

function isProcessing(orderId) {
  return activeRequest.value.has(orderId)
}

function getActionForOrder(order) {
  if (order.estado === 'pendiente') {
    return {
      label: '🔥 COMENZAR',
      action: () => changeStatus(order, 'preparando'),
      className: 'action-btn action-btn--start',
    }
  }

  if (order.estado === 'preparando') {
    return {
      label: '✅ MARCAR LISTO',
      action: () => changeStatus(order, 'listo'),
      className: 'action-btn action-btn--ready',
    }
  }

  if (order.estado === 'listo') {
    return {
      label: '📦 ENTREGAR',
      action: () => changeStatus(order, 'entregado'),
      className: 'action-btn action-btn--deliver',
    }
  }

  return null
}

async function changeStatus(order, nextStatus) {
  if (isProcessing(order.id)) return

  const pendingSet = new Set(activeRequest.value)
  pendingSet.add(order.id)
  activeRequest.value = pendingSet

  try {
    if (nextStatus === 'preparando') await startKitchenOrder(order.id)
    if (nextStatus === 'listo') await readyKitchenOrder(order.id)
    if (nextStatus === 'entregado') await deliverKitchenOrder(order.id)

    orders.value = orders.value.map((current) =>
      current.id === order.id ? { ...current, estado: nextStatus } : current,
    )
  } catch (err) {
    error.value = 'No pudimos actualizar el pedido. Revisa la conexión con la API.'
  } finally {
    const nextSet = new Set(activeRequest.value)
    nextSet.delete(order.id)
    activeRequest.value = nextSet
  }
}

function notifyNewOrders(newOrders) {
  if (!newOrders.length) return

  if (soundEnabled.value) playBeep()

  const marked = new Set(highlightedOrderIds.value)
  for (const order of newOrders) {
    marked.add(order.id)
    scrollCardIntoView(order.id)
  }
  highlightedOrderIds.value = marked

  setTimeout(() => {
    const cleaned = new Set(highlightedOrderIds.value)
    for (const order of newOrders) cleaned.delete(order.id)
    highlightedOrderIds.value = cleaned
  }, 3800)
}

function scrollCardIntoView(orderId) {
  requestAnimationFrame(() => {
    const el = document.getElementById(`kds-order-${orderId}`)
    el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
  })
}

function playBeep() {
  const context = new (window.AudioContext || window.webkitAudioContext)()
  const oscillator = context.createOscillator()
  const gain = context.createGain()

  oscillator.connect(gain)
  gain.connect(context.destination)

  oscillator.frequency.value = 880
  gain.gain.value = 0.05
  oscillator.start()

  setTimeout(() => {
    oscillator.stop()
    context.close()
  }, 120)
}

async function syncOrders(isInitialLoad = false) {
  if (loading.value && !isInitialLoad) return

  if (isInitialLoad) loading.value = true

  try {
    const data = await fetchKitchenOrders()
    const previousIds = new Set(orders.value.map((order) => order.id))
    const incoming = data.map((item) => ({
      ...item,
      estado: (item.estado || '').toLowerCase(),
    }))

    orders.value = incoming

    if (!isInitialLoad) {
      const newOrders = incoming.filter(
        (order) => !previousIds.has(order.id) && order.estado === 'pendiente',
      )
      notifyNewOrders(newOrders)
    }

    error.value = ''
  } catch (err) {
    error.value = 'Error cargando pedidos de cocina.'
  } finally {
    if (isInitialLoad) loading.value = false
  }
}

function startPolling() {
  stopPolling()
  pollingHandle = setInterval(() => syncOrders(false), props.pollingMs)
}

function stopPolling() {
  if (pollingHandle) {
    clearInterval(pollingHandle)
    pollingHandle = undefined
  }
}

function connectWebsocket() {
  if (!props.websocketUrl) {
    socketState.value = 'missing_url'
    startPolling()
    return
  }

  socketState.value = 'connecting'
  ws.value = new WebSocket(props.websocketUrl)

  ws.value.onopen = () => {
    socketState.value = 'connected'
  }

  ws.value.onmessage = () => {
    syncOrders(false)
  }

  ws.value.onclose = () => {
    socketState.value = 'disconnected'
    startPolling()
  }

  ws.value.onerror = () => {
    socketState.value = 'error'
    startPolling()
  }
}

function disconnectWebsocket() {
  if (ws.value) {
    ws.value.close()
    ws.value = null
  }
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen?.()
    return
  }

  document.exitFullscreen?.()
}

onMounted(async () => {
  await syncOrders(true)

  nowHandle = setInterval(() => {
    nowTs.value = Date.now()
  }, 1000)

  if (props.realtimeMode === 'websocket') {
    connectWebsocket()
    return
  }

  startPolling()
})

onUnmounted(() => {
  stopPolling()
  disconnectWebsocket()
  if (nowHandle) clearInterval(nowHandle)
})
</script>

<template>
  <section class="kitchen-pro">
    <header class="kitchen-pro__topbar">
      <div>
        <h1>Modo Cocina PRO</h1>
        <p class="muted">Flujo alto · KDS profesional</p>
      </div>

      <div class="kitchen-pro__quick-stats">
        <article>
          <span>Activos</span>
          <strong>{{ activeOrdersCount }}</strong>
        </article>
        <article>
          <span>Promedio</span>
          <strong>{{ averageMinutes.toFixed(1) }}m</strong>
        </article>
        <article>
          <span>Atrasados</span>
          <strong class="danger">{{ delayedOrdersCount }}</strong>
        </article>
      </div>

      <div class="kitchen-pro__controls">
        <button class="ghost-btn" @click="soundEnabled = !soundEnabled">
          {{ soundEnabled ? '🔊 Sonido ON' : '🔈 Sonido OFF' }}
        </button>
        <button class="ghost-btn" @click="toggleFullscreen">⛶ Pantalla completa</button>
      </div>
    </header>

    <p v-if="error" class="error-msg">{{ error }}</p>
    <p v-if="loading" class="muted">Cargando pedidos...</p>

    <div class="kitchen-pro__board">
      <section v-for="column in orderedColumns" :key="column.status" class="k-column">
        <header class="k-column__header">
          <h2>{{ column.title }}</h2>
          <span>{{ column.orders.length }}</span>
        </header>

        <TransitionGroup name="order-slide" tag="div" class="k-column__list">
          <article
            v-for="order in column.orders"
            :id="`kds-order-${order.id}`"
            :key="order.id"
            class="order-card"
            :class="{
              'order-card--new': highlightedOrderIds.has(order.id),
              'order-card--critical': order._elapsedMinutes > 6,
            }"
          >
            <header class="order-card__header">
              <strong>#{{ order.id }}</strong>
              <span class="timer" :class="timerClass(order)">{{ formatElapsed(order._elapsedMs) }}</span>
            </header>

            <ul class="order-items">
              <li v-for="item in order.items" :key="item.id || `${order.id}-${item.nombre}`">
                <div class="order-item-main">
                  <span class="qty">{{ item.cantidad ?? item.quantity ?? 1 }}x</span>
                  <span class="name">{{ item.nombre ?? item.menu_item?.nombre ?? 'Item' }}</span>
                </div>
                <NotePreview
                  v-if="getItemNote(item)"
                  :text="getItemNote(item)"
                  :context="getItemContext(item)"
                />
              </li>
            </ul>

            <NotePreview
              v-if="order.notas"
              class="order-note"
              :text="order.notas"
              context="Pedido"
            />

            <button
              v-if="getActionForOrder(order)"
              :class="getActionForOrder(order).className"
              :disabled="isProcessing(order.id)"
              @click="getActionForOrder(order).action()"
            >
              {{ isProcessing(order.id) ? 'Procesando...' : getActionForOrder(order).label }}
            </button>
          </article>
        </TransitionGroup>
      </section>
    </div>
  </section>
</template>

<style scoped>
.kitchen-pro {
  min-height: 100vh;
  padding: 16px;
  background: radial-gradient(circle at top, #1f2937, #0b1020 55%);
  color: #f3f4f6;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.kitchen-pro__topbar {
  display: grid;
  grid-template-columns: 1.2fr 1fr auto;
  gap: 12px;
  align-items: center;
}

.kitchen-pro__topbar h1 {
  margin: 0;
  font-size: 1.65rem;
}

.muted {
  color: #9ca3af;
  margin: 0;
}

.kitchen-pro__quick-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(92px, 1fr));
  gap: 8px;
}

.kitchen-pro__quick-stats article {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 8px 10px;
  text-align: center;
}

.kitchen-pro__quick-stats span {
  font-size: 0.75rem;
  color: #9ca3af;
}

.kitchen-pro__quick-stats strong {
  display: block;
  font-size: 1.15rem;
}

.danger {
  color: #fb7185;
}

.kitchen-pro__controls {
  display: flex;
  gap: 8px;
}

.ghost-btn {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: #e5e7eb;
  border-radius: 10px;
  padding: 10px 12px;
  cursor: pointer;
}

.error-msg {
  margin: 0;
  color: #fecaca;
  background: rgba(185, 28, 28, 0.2);
  border: 1px solid rgba(248, 113, 113, 0.4);
  border-radius: 8px;
  padding: 8px 10px;
}

.kitchen-pro__board {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(4, minmax(260px, 1fr));
  gap: 12px;
  min-height: 0;
}

.k-column {
  background: rgba(17, 24, 39, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.k-column__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.k-column__header h2 {
  margin: 0;
  font-size: 1.1rem;
}

.k-column__list {
  overflow-y: auto;
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.order-card {
  background: #111827;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.order-card--new {
  animation: softGlow 2s ease;
}

.order-card--critical {
  border-color: rgba(244, 63, 94, 0.7);
}

.order-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 1.1rem;
}

.timer {
  font-weight: 700;
  font-size: 1.05rem;
  padding: 4px 9px;
  border-radius: 999px;
}

.timer--ok {
  background: rgba(34, 197, 94, 0.2);
  color: #4ade80;
}

.timer--warn {
  background: rgba(250, 204, 21, 0.2);
  color: #fde047;
}

.timer--critical {
  background: rgba(244, 63, 94, 0.18);
  color: #fb7185;
  animation: pulseRed 1.2s ease-in-out infinite;
}

.order-items {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.order-items li {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.order-item-main {
  display: flex;
  gap: 8px;
  align-items: baseline;
}

.qty {
  font-size: 1.2rem;
  font-weight: 800;
  color: #facc15;
  min-width: 40px;
}

.name {
  color: #f9fafb;
  font-weight: 600;
}

.order-note {
  width: 100%;
}

.action-btn {
  width: 100%;
  border: none;
  border-radius: 12px;
  padding: 14px;
  font-size: 1.05rem;
  font-weight: 800;
  cursor: pointer;
}

.action-btn:disabled {
  opacity: 0.5;
}

.action-btn--start {
  background: #f97316;
  color: white;
}

.action-btn--ready {
  background: #22c55e;
  color: #06210f;
}

.action-btn--deliver {
  background: #38bdf8;
  color: #082f49;
}

.order-slide-move,
.order-slide-enter-active,
.order-slide-leave-active {
  transition: all 0.35s ease;
}

.order-slide-enter-from,
.order-slide-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

@keyframes softGlow {
  0% {
    box-shadow: 0 0 0 rgba(56, 189, 248, 0);
  }
  45% {
    box-shadow: 0 0 28px rgba(56, 189, 248, 0.45);
  }
  100% {
    box-shadow: 0 0 0 rgba(56, 189, 248, 0);
  }
}

@keyframes pulseRed {
  0%,
  100% {
    box-shadow: 0 0 0 rgba(244, 63, 94, 0);
  }
  50% {
    box-shadow: 0 0 18px rgba(244, 63, 94, 0.45);
  }
}

@media (max-width: 1280px) {
  .kitchen-pro__board {
    grid-template-columns: repeat(2, minmax(260px, 1fr));
  }

  .kitchen-pro__topbar {
    grid-template-columns: 1fr;
  }
}
</style>
