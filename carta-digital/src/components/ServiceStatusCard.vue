<script setup>
import { computed } from 'vue'
import StatusTimeline from './StatusTimeline.vue'

const props = defineProps({
  group: {
    type: Object,
    required: true,
  },
})

const currentStatus = computed(() => props.group.currentStatus)

const statusText = computed(() => {
  if (!props.group.itemCount) return 'Sin items'
  return currentStatus.value.headline.toUpperCase()
})

const helperMessage = computed(() => {
  if (!props.group.itemCount) return props.group.emptyStateCopy

  switch (currentStatus.value.key) {
    case 'pendiente':
      return 'Recibimos tu orden y está en cola para iniciar.'
    case 'preparando':
      return 'El equipo ya está trabajando en este grupo.'
    case 'listo':
      return 'Este grupo está listo para salir a tu mesa.'
    case 'entregado':
      return 'Este grupo ya fue entregado. ¡Buen provecho!'
    default:
      return ''
  }
})
</script>

<template>
  <article class="service-card" :style="{ '--accent-color': currentStatus.color }">
    <header class="service-card__header">
      <div>
        <p class="service-card__title">{{ group.icon }} {{ group.title }}</p>
        <p class="service-card__meta">{{ group.itemCount }} item{{ group.itemCount === 1 ? '' : 's' }}</p>
      </div>

      <span class="status-pill">
        {{ currentStatus.label }} {{ currentStatus.icon }}
      </span>
    </header>

    <StatusTimeline :steps="group.statuses" :current-status-key="group.statusKey" />

    <footer class="service-card__footer">
      <p>
        Estado actual:
        <strong>{{ statusText }}</strong>
      </p>
      <small>{{ helperMessage }}</small>
    </footer>
  </article>
</template>

<style scoped>
.service-card {
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: linear-gradient(150deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.03));
  padding: 14px;
  display: grid;
  gap: 14px;
  transition: transform 220ms ease, border-color 220ms ease;
}

.service-card:hover {
  transform: translateY(-1px);
  border-color: color-mix(in srgb, var(--accent-color) 48%, white 18%);
}

.service-card__header {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
}

.service-card__title {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
}

.service-card__meta {
  margin: 4px 0 0;
  opacity: 0.75;
  font-size: 12px;
}

.status-pill {
  border-radius: 999px;
  border: 1px solid color-mix(in srgb, var(--accent-color) 52%, white 14%);
  background: color-mix(in srgb, var(--accent-color) 18%, transparent);
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.service-card__footer p {
  margin: 0;
  font-size: 13px;
}

.service-card__footer strong {
  color: var(--accent-color);
  letter-spacing: 0.02em;
}

.service-card__footer small {
  margin-top: 4px;
  display: block;
  font-size: 12px;
  opacity: 0.85;
}

@media (max-width: 480px) {
  .service-card {
    padding: 12px;
  }

  .service-card__title {
    font-size: 15px;
  }

  .status-pill {
    font-size: 11px;
    padding: 5px 8px;
  }
}
</style>
