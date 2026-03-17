<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import OrderCard from './OrderCard.vue'
import { fetchKitchenOrders, updateKitchenGroupStatus } from '../services/kitchenApi'

const props = defineProps({
  pollingMs: {
    type: Number,
    default: 4000,
  },
})

const orders = ref([])
const now = ref(Date.now())
const loading = ref(false)
const error = ref('')
const soundEnabled = ref(true)
const busyGroupKey = ref('')
const highlighted = ref(new Set())
let pollingHandle
let clockHandle

const normalizedOrders = computed(() => {
  return orders.value
    .map((order) => {
      const createdTs = new Date(order.created_at).getTime()
      const elapsedMinutes = Math.max((now.value - createdTs) / 60000, 0)
      const groupStatuses = (order.grupos_servicio || []).map((g) => String(g.estado || 'pendiente').toLowerCase())
      const hasPending = groupStatuses.includes('pendiente')
      const hasPreparing = groupStatuses.includes('preparando')
      const hasReady = groupStatuses.includes('listo')
      const hasOnlyDelivered = groupStatuses.length > 0 && groupStatuses.every((s) => s === 'entregado')

      let mainStatus = 'pendiente'
      if (hasOnlyDelivered) mainStatus = 'entregado'
      else if (hasPending) mainStatus = 'pendiente'
      else if (hasPreparing) mainStatus = 'preparando'
      else if (hasReady) mainStatus = 'listo'

      return {
        ...order,
        elapsedMinutes,
        mainStatus,
        isNew: highlighted.value.has(order.id),
      }
    })
    .sort((a, b) => b.elapsedMinutes - a.elapsedMinutes)
})

const stats = computed(() => {
  const active = normalizedOrders.value.filter((o) => o.mainStatus !== 'entregado')
  return {
    active: active.length,
    delayed: active.filter((o) => o.elapsedMinutes >= 6).length,
    avg: active.length
      ? (active.reduce((acc, o) => acc + o.elapsedMinutes, 0) / active.length).toFixed(1)
      : '0.0',
  }
})

function playNewOrderSound() {
  const context = new (window.AudioContext || window.webkitAudioContext)()
  const oscillator = context.createOscillator()
  const gain = context.createGain()
  oscillator.type = 'triangle'
  oscillator.frequency.value = 920
  gain.gain.value = 0.05
  oscillator.connect(gain)
  gain.connect(context.destination)
  oscillator.start()
  setTimeout(() => {
    oscillator.stop()
    context.close()
  }, 140)
}

async function loadOrders(initial = false) {
  if (initial) loading.value = true

  try {
    const oldIds = new Set(orders.value.map((o) => o.id))
    const data = await fetchKitchenOrders()
    orders.value = data

    const incoming = data.filter((order) => !oldIds.has(order.id))
    if (incoming.length) {
      if (soundEnabled.value) playNewOrderSound()

      highlighted.value = new Set([...highlighted.value, ...incoming.map((o) => o.id)])
      setTimeout(() => {
        const next = new Set(highlighted.value)
        for (const order of incoming) next.delete(order.id)
        highlighted.value = next
      }, 2500)
    }

    error.value = ''
  } catch (err) {
    error.value = 'No pudimos cargar pedidos de cocina.'
  } finally {
    if (initial) loading.value = false
  }
}

async function handleGroupStatusChange({ orderId, group, status }) {
  const key = `${orderId}-${group}`
  if (busyGroupKey.value) return
  busyGroupKey.value = key

  const previous = structuredClone(orders.value)

  orders.value = orders.value.map((order) => {
    if (order.id !== orderId) return order
    return {
      ...order,
      grupos_servicio: (order.grupos_servicio || []).map((serviceGroup) =>
        String(serviceGroup.grupo).toLowerCase() === String(group).toLowerCase()
          ? { ...serviceGroup, estado: status }
          : serviceGroup,
      ),
    }
  })

  try {
    await updateKitchenGroupStatus(orderId, group, status)
  } catch (err) {
    orders.value = previous
    error.value = 'No se pudo actualizar el grupo de servicio.'
  } finally {
    busyGroupKey.value = ''
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
  await loadOrders(true)
  pollingHandle = setInterval(() => loadOrders(false), props.pollingMs)
  clockHandle = setInterval(() => {
    now.value = Date.now()
  }, 1000)
})

onUnmounted(() => {
  if (pollingHandle) clearInterval(pollingHandle)
  if (clockHandle) clearInterval(clockHandle)
})
</script>

<template>
  <main class="kds-view">
    <header class="kds-topbar">
      <div>
        <h1>ODER EASY · Kitchen Display System</h1>
        <p>Vista en tiempo real para bebidas y platos</p>
      </div>
      <div class="kds-stats">
        <article><span>Activos</span><strong>{{ stats.active }}</strong></article>
        <article><span>Promedio</span><strong>{{ stats.avg }} min</strong></article>
        <article><span>Atrasados</span><strong>{{ stats.delayed }}</strong></article>
      </div>
      <div class="kds-actions">
        <button @click="soundEnabled = !soundEnabled">{{ soundEnabled ? '🔊 Sonido ON' : '🔈 Sonido OFF' }}</button>
        <button @click="toggleFullscreen">⛶ Fullscreen</button>
      </div>
    </header>

    <p v-if="loading" class="helper">Cargando tablero de cocina...</p>
    <p v-if="error" class="error">{{ error }}</p>

    <section class="orders-grid">
      <TransitionGroup name="grid-anim" tag="div" class="orders-grid__inner">
        <OrderCard
          v-for="order in normalizedOrders"
          :key="order.id"
          :order="order"
          :processing-key="busyGroupKey"
          @change-group-status="handleGroupStatusChange"
        />
      </TransitionGroup>
    </section>
  </main>
</template>

<style scoped>
.kds-view {
  min-height: 100vh;
  padding: 18px;
  background: #0e0e0f;
  color: #f8fafc;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.kds-topbar {
  display: grid;
  grid-template-columns: 1.2fr auto auto;
  gap: 12px;
  align-items: center;
}

h1 {
  margin: 0;
  font-size: clamp(1.3rem, 2vw, 2rem);
}

p {
  margin: 4px 0 0;
  color: #97a0b4;
}

.kds-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(88px, 1fr));
  gap: 8px;
}

.kds-stats article {
  background: #16171d;
  border: 1px solid #252836;
  border-radius: 12px;
  padding: 8px 10px;
  text-align: center;
}

.kds-stats span {
  display: block;
  color: #7f88a1;
  font-size: 0.8rem;
}

.kds-stats strong {
  font-size: 1.3rem;
}

.kds-actions {
  display: flex;
  gap: 8px;
}

.kds-actions button {
  border: 1px solid #303446;
  background: #181a23;
  color: #e2e8f0;
  border-radius: 12px;
  min-height: 46px;
  padding: 0 14px;
  font-weight: 700;
  cursor: pointer;
}

.orders-grid {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding-right: 6px;
}

.orders-grid__inner {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 12px;
  align-content: start;
}

.helper,
.error {
  margin: 0;
}

.error {
  color: #fecaca;
}

.grid-anim-move,
.grid-anim-enter-active,
.grid-anim-leave-active {
  transition: all 0.3s ease;
}

.grid-anim-enter-from,
.grid-anim-leave-to {
  opacity: 0;
  transform: scale(0.96);
}

@media (min-width: 1800px) {
  .orders-grid__inner {
    grid-template-columns: repeat(5, minmax(280px, 1fr));
  }
}

@media (max-width: 1200px) {
  .kds-topbar {
    grid-template-columns: 1fr;
  }
}
</style>
