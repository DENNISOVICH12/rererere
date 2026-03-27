<script setup>
import { computed } from 'vue'

const props = defineProps({
  steps: {
    type: Array,
    required: true,
  },
  currentStatusKey: {
    type: String,
    required: true,
  },
})

const activeIndex = computed(() =>
  props.steps.findIndex(step => step.key === props.currentStatusKey),
)
</script>

<template>
  <div class="status-timeline" role="list" aria-label="Progreso del grupo de servicio">
    <div
      v-for="(step, index) in steps"
      :key="step.key"
      class="timeline-step"
      :class="{
        completed: index <= activeIndex,
        active: index === activeIndex,
      }"
      :style="{ '--step-color': step.color }"
      role="listitem"
    >
      <span class="timeline-step__dot" />
      <span class="timeline-step__label">{{ step.label }}</span>
    </div>
  </div>
</template>

<style scoped>
.status-timeline {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 8px;
}

.timeline-step {
  position: relative;
  display: grid;
  justify-items: center;
  gap: 8px;
  text-align: center;
}

.timeline-step::after {
  content: '';
  position: absolute;
  top: 7px;
  left: calc(50% + 10px);
  width: calc(100% - 20px);
  height: 2px;
  background: rgba(255, 255, 255, 0.2);
  transition: background-color 240ms ease;
}

.timeline-step:last-child::after {
  display: none;
}

.timeline-step.completed::after {
  background: color-mix(in srgb, var(--step-color) 68%, white 6%);
}

.timeline-step__dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.34);
  background: rgba(255, 255, 255, 0.1);
  transition: all 240ms ease;
}

.timeline-step.completed .timeline-step__dot {
  border-color: var(--step-color);
  background: var(--step-color);
}

.timeline-step.active .timeline-step__dot {
  box-shadow: 0 0 0 6px color-mix(in srgb, var(--step-color) 22%, transparent);
  animation: pulse 1.6s ease-in-out infinite;
}

.timeline-step__label {
  font-size: 11px;
  line-height: 1.2;
  opacity: 0.86;
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.14);
  }
}

@media (max-width: 480px) {
  .timeline-step__label {
    font-size: 10px;
  }
}
</style>
