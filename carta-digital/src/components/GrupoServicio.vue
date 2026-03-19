<script setup>
import { computed } from 'vue'

const props = defineProps({
  group: {
    type: Object,
    required: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['change-status'])

const stateConfig = {
  pendiente: { label: 'Pendiente', icon: '🔴', className: 'is-pendiente' },
  preparando: { label: 'Preparando', icon: '🟡', className: 'is-preparando' },
  listo: { label: 'Listo', icon: '🟢', className: 'is-listo' },
}

const groupConfig = {
  bebida: { label: 'BAR', icon: '🍹' },
  plato: { label: 'COCINA', icon: '🍽' },
}

const normalizedGroup = computed(() => String(props.group?.grupo || '').toLowerCase())
const normalizedState = computed(() => String(props.group?.estado || 'pendiente').toLowerCase())

const visualGroup = computed(() => groupConfig[normalizedGroup.value] || { label: props.group?.grupo || 'Grupo', icon: '🧩' })
const visualState = computed(() => stateConfig[normalizedState.value] || stateConfig.pendiente)
const canStartService = computed(() => normalizedState.value === 'pendiente')

function getItemLabel(item) {
  const quantity = item?.cantidad ?? item?.quantity ?? 1
  const name = item?.nombre ?? item?.menu_item?.nombre ?? 'Ítem'
  return `${quantity}x ${name}`
}
</script>

<template>
  <section class="grupo" :class="visualState.className">
    <header class="grupo__header">
      <h3>{{ visualGroup.icon }} {{ visualGroup.label }}</h3>
      <span class="status-badge" :class="visualState.className">
        {{ visualState.icon }} {{ visualState.label }}
      </span>
    </header>

    <ul class="grupo__items">
      <li v-for="(item, index) in group.items || []" :key="item.id || `${normalizedGroup}-${index}`">
        {{ getItemLabel(item) }}
      </li>
      <li v-if="!(group.items || []).length" class="empty">Sin ítems</li>
    </ul>

    <div class="grupo__actions">
      <button
        v-if="canStartService"
        :disabled="disabled"
        @click="emit('change-status', 'preparando')"
      >
        Iniciar preparación
      </button>
    </div>
  </section>
</template>

<style scoped>
.grupo {
  border-radius: 14px;
  border: 2px solid #2d2f37;
  padding: 12px;
  background: #15161a;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.grupo__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

h3 {
  margin: 0;
  font-size: 1rem;
  letter-spacing: 0.08em;
}

.status-badge {
  border-radius: 999px;
  font-size: 0.86rem;
  font-weight: 800;
  padding: 6px 10px;
}

.grupo__items {
  margin: 0;
  padding-left: 18px;
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-height: 72px;
}

.grupo__items li {
  font-size: 1rem;
  line-height: 1.35;
}

.empty {
  color: #7f8392;
  list-style: none;
  margin-left: -18px;
}

.grupo__actions button {
  border: none;
  border-radius: 10px;
  font-weight: 800;
  font-size: 0.86rem;
  min-height: 42px;
  cursor: pointer;
  color: #f8fafc;
  background: #2d3240;
}

.grupo__actions button:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.is-pendiente {
  border-color: #ef4444;
}

.is-pendiente .status-badge {
  background: #ef4444;
  color: #fee2e2;
}

.is-preparando {
  border-color: #facc15;
}

.is-preparando .status-badge {
  background: #facc15;
  color: #1f2937;
}

.is-listo {
  border-color: #22c55e;
}

.is-listo .status-badge {
  background: #22c55e;
  color: #062c12;
}
</style>
