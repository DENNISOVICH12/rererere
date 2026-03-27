<script setup>
import { computed } from 'vue'
import ServiceStatusCard from './ServiceStatusCard.vue'

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
})

const STATUS_PRIORITY = ['pendiente', 'preparando', 'listo', 'entregado']

const statusMeta = {
  pendiente: {
    key: 'pendiente',
    label: 'Pendiente',
    headline: 'Pendiente',
    icon: '⏳',
    color: '#8f97a3',
  },
  preparando: {
    key: 'preparando',
    label: 'Preparando',
    headline: 'En preparación',
    icon: '🔄',
    color: '#f5c451',
  },
  listo: {
    key: 'listo',
    label: 'Listo',
    headline: 'Listo',
    icon: '✅',
    color: '#2ecc71',
  },
  entregado: {
    key: 'entregado',
    label: 'Entregado',
    headline: 'Entregado',
    icon: '📦',
    color: '#4098ff',
  },
}

const groupMeta = {
  bebida: {
    key: 'bebida',
    title: 'Bebidas',
    icon: '🍹',
    emptyStateCopy: 'Aún no hay bebidas registradas para este pedido.',
  },
  plato: {
    key: 'plato',
    title: 'Platos',
    icon: '🍽',
    emptyStateCopy: 'Aún no hay platos registrados para este pedido.',
  },
}

const orderItems = computed(() => {
  const source = [
    props.order?.detalle,
    props.order?.detalles,
    props.order?.items,
    props.order?.order_items,
  ].find(Array.isArray)

  return source ?? []
})

function normalizeStatus(rawStatus) {
  const normalized = String(rawStatus || '').toLowerCase().trim()
  if (normalized === 'en_preparacion') return 'preparando'
  return STATUS_PRIORITY.includes(normalized) ? normalized : 'pendiente'
}

function normalizeGroup(item) {
  const rawGroup = String(item?.grupo_servicio || item?.grupo || '').toLowerCase().trim()

  if (rawGroup.includes('beb')) return 'bebida'
  if (rawGroup.includes('plat')) return 'plato'

  const category = String(item?.categoria || item?.menu_item?.categoria || '').toLowerCase().trim()
  if (category.includes('beb')) return 'bebida'
  if (category.includes('plat')) return 'plato'

  return null
}

function computeGroupStatus(items) {
  const statuses = items.map(item => normalizeStatus(item?.estado_servicio || item?.estado))

  for (const status of STATUS_PRIORITY) {
    if (statuses.includes(status)) {
      return status
    }
  }

  return 'pendiente'
}

const groupedOrderStatus = computed(() => {
  const grouped = {
    bebida: [],
    plato: [],
  }

  for (const item of orderItems.value) {
    const groupKey = normalizeGroup(item)
    if (groupKey && grouped[groupKey]) {
      grouped[groupKey].push(item)
    }
  }

  return Object.values(groupMeta).map(group => {
    const items = grouped[group.key] || []
    const statusKey = items.length ? computeGroupStatus(items) : 'pendiente'

    return {
      ...group,
      items,
      itemCount: items.length,
      statusKey,
      currentStatus: statusMeta[statusKey],
      statuses: STATUS_PRIORITY.map(status => statusMeta[status]),
    }
  })
})

const hasTrackedItems = computed(() => groupedOrderStatus.value.some(group => group.itemCount > 0))
</script>

<template>
  <section class="order-service-status" aria-label="Estado de pedido por grupo de servicio">
    <header class="order-service-status__header">
      <p class="eyebrow">ODER EASY · Seguimiento en vivo</p>
      <h3>Estado de tu pedido</h3>
      <p>Te mostramos el avance independiente de bebidas y platos en tiempo real.</p>
    </header>

    <div v-if="hasTrackedItems" class="status-grid">
      <ServiceStatusCard
        v-for="group in groupedOrderStatus"
        :key="group.key"
        :group="group"
      />
    </div>

    <div v-else class="empty-state">
      <span>🧾</span>
      <p>Cuando cocina o barra tomen tu orden, aquí verás el avance por servicio.</p>
    </div>
  </section>
</template>

<style scoped>
.order-service-status {
  display: grid;
  gap: 14px;
}

.order-service-status__header {
  border-radius: 16px;
  padding: 16px;
  background: linear-gradient(130deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.04));
  border: 1px solid rgba(255, 255, 255, 0.14);
}

.order-service-status__header .eyebrow {
  margin: 0 0 6px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  opacity: 0.75;
  font-size: 11px;
}

.order-service-status__header h3 {
  margin: 0;
  font-size: clamp(18px, 3.8vw, 22px);
}

.order-service-status__header p {
  margin: 6px 0 0;
  opacity: 0.9;
  font-size: 13px;
}

.status-grid {
  display: grid;
  gap: 12px;
}

.empty-state {
  display: grid;
  justify-items: center;
  gap: 8px;
  text-align: center;
  border-radius: 14px;
  padding: 18px;
  border: 1px dashed rgba(255, 255, 255, 0.24);
  background: rgba(255, 255, 255, 0.03);
}

.empty-state span {
  font-size: 22px;
}

.empty-state p {
  margin: 0;
  font-size: 13px;
  opacity: 0.88;
}
</style>
