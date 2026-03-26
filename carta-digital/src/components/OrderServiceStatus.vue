<script setup>
import { computed } from 'vue'

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
})

const STATUS_STEPS = ['pendiente', 'preparando', 'listo', 'entregado']

const statusMeta = {
  pendiente: { label: 'Pendiente', color: 'var(--status-pending)', icon: '⏳' },
  preparando: { label: 'En preparación', color: 'var(--status-preparing)', icon: '🔄' },
  listo: { label: 'Listo', color: 'var(--status-ready)', icon: '✅' },
  entregado: { label: 'Entregado', color: 'var(--status-delivered)', icon: '📦' },
}

const groupMeta = {
  bebida: { label: 'Barra', icon: '🍹', plural: 'bebidas' },
  plato: { label: 'Cocina', icon: '🍽', plural: 'platos' },
}

const orderItems = computed(() => {
  const source = [
    props.order?.items,
    props.order?.detalles,
    props.order?.order_items,
  ].find(Array.isArray)

  return source ?? []
})

function normalizeStatus(value) {
  const normalized = String(value || '').toLowerCase().trim()
  if (normalized === 'en_preparacion') return 'preparando'
  return STATUS_STEPS.includes(normalized) ? normalized : 'pendiente'
}

function normalizeGroup(item) {
  const rawGroup = String(item?.grupo_servicio || item?.grupo || '').toLowerCase().trim()
  if (rawGroup) return rawGroup

  const category = String(item?.categoria || item?.menu_item?.categoria || '').toLowerCase()
  if (category.includes('beb')) return 'bebida'
  if (category.includes('plat')) return 'plato'

  return 'otros'
}

function resolveGroupStatus(items) {
  const states = items.map(item => normalizeStatus(item?.estado_servicio || item?.estado))

  if (!states.length) return 'pendiente'
  if (states.every(state => state === 'entregado')) return 'entregado'
  if (states.includes('pendiente')) return 'pendiente'
  if (states.includes('preparando')) return 'preparando'

  const allReadyOrDelivered = states.every(state => state === 'listo' || state === 'entregado')
  if (allReadyOrDelivered) return 'listo'

  if (states.every(state => state === 'listo')) return 'listo'

  return 'pendiente'
}

const groupedStatuses = computed(() => {
  const bucket = new Map()

  for (const item of orderItems.value) {
    const groupKey = normalizeGroup(item)
    const current = bucket.get(groupKey) || []
    current.push(item)
    bucket.set(groupKey, current)
  }

  return Array.from(bucket.entries()).map(([groupKey, items]) => {
    const meta = groupMeta[groupKey] || {
      label: groupKey.charAt(0).toUpperCase() + groupKey.slice(1),
      icon: '🧩',
      plural: groupKey,
    }

    const statusKey = resolveGroupStatus(items)
    const activeIndex = STATUS_STEPS.indexOf(statusKey)

    return {
      key: groupKey,
      items,
      statusKey,
      status: statusMeta[statusKey],
      activeIndex,
      ...meta,
    }
  })
})

const allDelivered = computed(() =>
  groupedStatuses.value.length > 0 && groupedStatuses.value.every(group => group.statusKey === 'entregado'),
)

const hasReadyGroup = computed(() =>
  groupedStatuses.value.some(group => group.statusKey === 'listo'),
)

const highlightedMessage = computed(() => {
  if (allDelivered.value) {
    return {
      title: 'Pedido completado ✅',
      body: 'Todos los grupos fueron entregados.',
      tone: 'is-success',
    }
  }

  if (hasReadyGroup.value) {
    return {
      title: 'Estado parcial destacado',
      body: 'Hay grupos listos mientras otros siguen en proceso.',
      tone: 'is-info',
    }
  }

  return null
})

function groupMessage(group) {
  switch (group.statusKey) {
    case 'listo':
      return `Tus ${group.plural} ya están listas.`
    case 'preparando':
      return `Tus ${group.plural} siguen en preparación.`
    case 'entregado':
      return `Tus ${group.plural} ya fueron entregadas.`
    default:
      return `Tus ${group.plural} están pendientes por iniciar.`
  }
}
</script>

<template>
  <div class="service-status-wrap">
    <div v-if="highlightedMessage" class="status-highlight" :class="highlightedMessage.tone">
      <strong>{{ highlightedMessage.title }}</strong>
      <p>{{ highlightedMessage.body }}</p>
    </div>

    <article
      v-for="group in groupedStatuses"
      :key="group.key"
      class="service-card"
      :style="{ '--active-color': group.status.color }"
    >
      <header class="service-card__header">
        <h4>{{ group.icon }} {{ group.label }}</h4>
        <span class="service-badge">{{ group.status.label }} {{ group.status.icon }}</span>
      </header>

      <div class="service-timeline" role="list" :aria-label="`Estado del grupo ${group.label}`">
        <div
          v-for="(step, index) in STATUS_STEPS"
          :key="step"
          class="service-step"
          :class="{
            completed: index < group.activeIndex,
            active: index === group.activeIndex,
          }"
          role="listitem"
        >
          <span class="dot"></span>
          <small>{{ statusMeta[step].label }}</small>
        </div>
      </div>

      <p class="service-current">
        Estado actual:
        <strong>{{ group.status.label.toUpperCase() }}</strong>
      </p>
      <p class="service-message">{{ groupMessage(group) }}</p>
    </article>
  </div>
</template>

<style scoped>
.service-status-wrap {
  display: grid;
  gap: 12px;
}

.status-highlight {
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 10px 12px;
  background: rgba(255, 255, 255, 0.06);
}

.status-highlight p {
  margin: 4px 0 0;
  font-size: 12px;
  opacity: 0.9;
}

.status-highlight.is-success {
  border-color: rgba(73, 220, 139, 0.6);
  background: linear-gradient(135deg, rgba(73, 220, 139, 0.2), rgba(73, 220, 139, 0.05));
}

.status-highlight.is-info {
  border-color: rgba(124, 187, 255, 0.6);
  background: linear-gradient(135deg, rgba(124, 187, 255, 0.2), rgba(124, 187, 255, 0.05));
}

.service-card {
  --status-pending: #9ba2ad;
  --status-preparing: #f3c96e;
  --status-ready: #49dc8b;
  --status-delivered: #64a3ff;
  --active-color: var(--status-pending);

  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02));
  padding: 12px;
  display: grid;
  gap: 10px;
}

.service-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.service-card__header h4 {
  margin: 0;
  font-size: 14px;
  letter-spacing: 0.01em;
}

.service-badge {
  font-size: 11px;
  padding: 5px 10px;
  border-radius: 999px;
  border: 1px solid color-mix(in srgb, var(--active-color) 60%, #ffffff 20%);
  background: color-mix(in srgb, var(--active-color) 18%, transparent);
}

.service-timeline {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
}

.service-step {
  position: relative;
  display: grid;
  justify-items: center;
  gap: 6px;
  font-size: 10px;
  text-align: center;
}

.service-step .dot {
  width: 13px;
  height: 13px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.12);
  transition: all 0.3s ease;
}

.service-step:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 6px;
  left: calc(50% + 8px);
  width: calc(100% - 16px);
  height: 2px;
  background: rgba(255, 255, 255, 0.16);
}

.service-step.completed .dot {
  background: var(--active-color);
  border-color: var(--active-color);
}

.service-step.active .dot {
  background: var(--active-color);
  border-color: color-mix(in srgb, var(--active-color) 70%, white);
  box-shadow: 0 0 10px color-mix(in srgb, var(--active-color) 55%, transparent);
  animation: pulseDot 1.8s ease-in-out infinite;
}

.service-step small {
  opacity: 0.75;
  line-height: 1.2;
}

.service-current,
.service-message {
  margin: 0;
  font-size: 12px;
}

.service-current strong {
  color: var(--active-color);
}

.service-message {
  opacity: 0.86;
}

@keyframes pulseDot {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.15);
  }
}

@media (max-width: 640px) {
  .service-card {
    padding: 10px;
  }

  .service-step small {
    font-size: 9px;
  }
}
</style>
