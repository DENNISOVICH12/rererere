<script setup>
import { computed } from 'vue'

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
    label: 'PEND',
    color: '#7b8794',
  },
  preparando: {
    key: 'preparando',
    label: 'PREP',
    color: '#f5b84d',
  },
  listo: {
    key: 'listo',
    label: 'LISTO',
    color: '#2ecc71',
  },
  entregado: {
    key: 'entregado',
    label: 'ENTREGADO',
    color: '#4ca5ff',
  },
}

const groupMeta = {
  bebida: {
    key: 'bebida',
    title: 'Bebidas',
    icon: '🍹',
  },
  plato: {
    key: 'plato',
    title: 'Platos',
    icon: '🍽',
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
      progress: STATUS_PRIORITY.map(status => ({
        key: status,
        active: STATUS_PRIORITY.indexOf(status) <= STATUS_PRIORITY.indexOf(statusKey),
      })),
    }
  })
})

const hasTrackedItems = computed(() => groupedOrderStatus.value.some(group => group.itemCount > 0))
</script>

<template>
  <section class="order-service-status" aria-label="Estado de pedido por grupo de servicio">
    <div v-if="hasTrackedItems" class="status-list">
      <article
        v-for="group in groupedOrderStatus"
        :key="group.key"
        class="status-row"
      >
        <p class="status-row__name">{{ group.icon }} {{ group.title }}</p>

        <div class="status-row__progress" aria-hidden="true">
          <span
            v-for="step in group.progress"
            :key="step.key"
            class="progress-dot"
            :class="{ active: step.active }"
            :style="step.active ? { '--dot-color': group.currentStatus.color } : undefined"
          ></span>
        </div>

        <p class="status-row__label" :style="{ color: group.currentStatus.color }">
          {{ group.currentStatus.label }}
        </p>
      </article>
    </div>

    <div v-else class="status-empty">
      Sin seguimiento disponible todavía
    </div>
  </section>
</template>

<style scoped>
.order-service-status {
  display: flex;
  flex-direction: column;
}

.status-list {
  display: grid;
  gap: 8px;
}

.status-row {
  display: grid;
  grid-template-columns: minmax(92px, 1fr) auto auto;
  align-items: center;
  gap: 10px;
}

.status-row__name {
  margin: 0;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
}

.status-row__progress {
  display: inline-flex;
  gap: 6px;
}

.progress-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.26);
  border: 1px solid rgba(255, 255, 255, 0.24);
}

.progress-dot.active {
  background: var(--dot-color, #2ecc71);
  border-color: var(--dot-color, #2ecc71);
  box-shadow: 0 0 8px color-mix(in srgb, var(--dot-color, #2ecc71) 45%, transparent);
}

.status-row__label {
  margin: 0;
  min-width: 68px;
  text-align: right;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
}

.status-empty {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.72);
}

@media (max-width: 480px) {
  .status-row {
    grid-template-columns: 1fr auto auto;
    gap: 8px;
  }

  .status-row__name {
    font-size: 12px;
  }

  .status-row__label {
    min-width: 60px;
    font-size: 10px;
  }
}
</style>
