<script setup>
import { computed } from 'vue'
import GrupoServicio from './GrupoServicio.vue'

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  processingKey: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['change-group-status'])

const hasDelay = computed(() => props.order.elapsedMinutes >= 6)
const cardClass = computed(() => {
  if (props.order.mainStatus === 'pendiente') return 'card-pending'
  if (props.order.mainStatus === 'preparando') return 'card-preparing'
  if (props.order.mainStatus === 'listo') return 'card-ready'
  return 'card-delivered'
})

function formatClock(dateValue) {
  if (!dateValue) return '--:--'
  return new Date(dateValue).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' })
}

function formatAge(minutes) {
  const floorValue = Math.max(0, Math.floor(minutes))
  return `${floorValue} min`
}

function handleStatusChange(group, nextStatus) {
  emit('change-group-status', {
    orderId: props.order.id,
    group,
    status: nextStatus,
  })
}

function groupKey(group, index) {
  return `${group.grupo || 'grupo'}-${index}`
}
</script>

<template>
  <article class="order-card" :class="[cardClass, { 'is-delayed': hasDelay, 'is-new': order.isNew }]">
    <header class="order-card__header">
      <div>
        <h2>#{{ order.id }}</h2>
        <p>{{ formatClock(order.created_at) }} · {{ order.mesa ? `Mesa ${order.mesa}` : 'Para llevar' }}</p>
      </div>
      <div class="timers">
        <span class="age">⏱ {{ formatAge(order.elapsedMinutes) }}</span>
        <span v-if="hasDelay" class="delay">Retraso</span>
      </div>
    </header>

    <section class="order-card__groups">
      <GrupoServicio
        v-for="(group, index) in order.grupos_servicio"
        :key="groupKey(group, index)"
        :group="group"
        :disabled="Boolean(processingKey)"
        @change-status="handleStatusChange(group.grupo, $event)"
      />
    </section>
  </article>
</template>

<style scoped>
.order-card {
  border-radius: 18px;
  background: #141519;
  border: 2px solid #343744;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.32);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.order-card:hover {
  transform: translateY(-2px);
}

.order-card__header {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

h2 {
  margin: 0;
  font-size: 2rem;
  line-height: 1;
}

p {
  margin: 5px 0 0;
  color: #99a0b1;
  font-weight: 600;
}

.timers {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
}

.age {
  border-radius: 999px;
  background: #1f2330;
  color: #dbeafe;
  font-weight: 800;
  padding: 6px 10px;
}

.delay {
  color: #fecaca;
  font-size: 0.84rem;
  font-weight: 700;
}

.order-card__groups {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.card-pending {
  border-color: #ef4444;
}

.card-preparing {
  border-color: #facc15;
}

.card-ready {
  border-color: #22c55e;
}

.card-delivered {
  border-color: #3b82f6;
}

.is-delayed {
  animation: pulseBorder 1.5s ease-in-out infinite;
}

.is-new {
  animation: cardIn 350ms ease;
}

@keyframes pulseBorder {
  0%,
  100% {
    box-shadow: 0 0 0 rgba(239, 68, 68, 0.2);
  }
  50% {
    box-shadow: 0 0 22px rgba(239, 68, 68, 0.55);
  }
}

@keyframes cardIn {
  from {
    opacity: 0;
    transform: scale(0.94);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
