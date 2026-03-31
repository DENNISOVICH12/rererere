<template>
  <section class="service-status" aria-label="Estado del pedido por grupo de servicio">
    <header class="service-status__header">
      <h3>Estado del pedido</h3>
      <span v-if="allDelivered" class="summary summary--success">Pedido completado ✅</span>
      <span v-else-if="hasReadyGroup" class="summary summary--partial">Entrega parcial en curso</span>
      <span v-else class="summary">Seguimiento en tiempo real</span>
    </header>

    <p v-if="!serviceGroups.length" class="empty">Aún no hay grupos de servicio con estado para mostrar.</p>

    <article v-for="group in serviceGroups" :key="group.key" class="service-group">
      <header class="service-group__header">
        <div class="service-group__title-wrap">
          <span class="service-group__icon">{{ group.icon }}</span>
          <h4>{{ group.label }}</h4>
        </div>
        <span class="chip" :class="`chip--${group.currentStatus}`">
          {{ statusLabel(group.currentStatus) }}
        </span>
      </header>

      <div class="service-group__line-wrap">
        <ol class="progress" role="list">
          <li
            v-for="(status, index) in SERVICE_STEPS"
            :key="status"
            class="progress__step"
            :class="stepClass(group.currentStatus, status, index, group.key)"
          >
            <span class="dot" />
            <span class="label">{{ statusLabel(status) }}</span>
            <span v-if="index < SERVICE_STEPS.length - 1" class="line" />
          </li>
        </ol>

        <button
          v-if="canDeliverGroup(group)"
          class="deliver-btn"
          :class="`deliver-btn--${group.key}`"
          :disabled="busy || isDeliveringGroup(group.key)"
          @click="deliverGroup(group.key)"
        >
          <span v-if="isDeliveringGroup(group.key)" class="spinner" aria-hidden="true" />
          {{ isDeliveringGroup(group.key) ? 'Entregando...' : deliverLabel(group.key) }}
        </button>
      </div>

      <p class="service-group__message">{{ group.message }}</p>
    </article>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { SERVICE_STEPS, getGroupStatus, normalizeGroupKey, normalizeStatus } from '../utils/serviceStatus';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  busy: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['deliver-group']);

const GROUP_META = {
  bebida: { label: 'Bebidas', icon: '🍹' },
  plato: { label: 'Platos', icon: '🍽' },
};

const statusLabel = (status) => {
  const normalized = normalizeStatus(status);
  return {
    pendiente: 'Pendiente',
    preparando: 'En preparación',
    listo: 'Listo',
    entregado: 'Entregado',
  }[normalized] || 'Pendiente';
};

const statusIndex = (status) => SERVICE_STEPS.indexOf(normalizeStatus(status));
const pendingDeliveryGroup = ref(null);

watch(
  () => props.busy,
  (isBusy) => {
    if (!isBusy) pendingDeliveryGroup.value = null;
  },
);

const fallbackItems = computed(() => {
  const items = Array.isArray(props.order?.items) ? props.order.items : [];
  return items.map((item) => {
    const groupFromCategory = normalizeGroupKey(item?.grupo_servicio || item?.categoria);
    return {
      ...item,
      grupo_servicio: groupFromCategory,
      estado_servicio: item?.estado_servicio || item?._serviceStatus || props.order?.estado || 'pendiente',
    };
  });
});

const serviceGroups = computed(() => {
  const sourceGroups = Array.isArray(props.order?.grupos_servicio) ? props.order.grupos_servicio : [];

  const groupedMap = new Map();

  sourceGroups.forEach((group) => {
    const key = normalizeGroupKey(group?.grupo || group?.key);
    const items = Array.isArray(group?.items) ? group.items : [];
    if (!items.length) return;
    groupedMap.set(key, {
      key,
      items,
      currentStatus: getGroupStatus(items, key) || normalizeStatus(group?.estado),
    });
  });

  if (!groupedMap.size) {
    fallbackItems.value.forEach((item) => {
      const key = normalizeGroupKey(item?.grupo_servicio);
      if (!groupedMap.has(key)) groupedMap.set(key, { key, items: [], currentStatus: 'pendiente' });
      groupedMap.get(key).items.push(item);
    });

    groupedMap.forEach((groupData, key) => {
      groupedMap.set(key, {
        ...groupData,
        currentStatus: getGroupStatus(groupData.items, key),
      });
    });
  }

  return Array.from(groupedMap.values()).map((group) => {
    const meta = GROUP_META[group.key] || { label: capitalize(group.key), icon: '📦' };
    return {
      ...group,
      label: meta.label,
      icon: meta.icon,
      message: buildMessage(group.key, meta.label, group.currentStatus),
    };
  });
});

const hasReadyGroup = computed(() => serviceGroups.value.some((group) => group.currentStatus === 'listo'));
const allDelivered = computed(
  () => serviceGroups.value.length > 0 && serviceGroups.value.every((group) => group.currentStatus === 'entregado'),
);

const canDeliverGroup = (group) => group.currentStatus === 'listo';

const deliverLabel = (groupKey) => (normalizeGroupKey(groupKey) === 'bebida' ? 'Entregar bebidas' : 'Entregar platos');
const isDeliveringGroup = (groupKey) => props.busy && pendingDeliveryGroup.value === groupKey;

const deliverGroup = (groupKey) => {
  if (!groupKey || props.busy) return;

  pendingDeliveryGroup.value = groupKey;

  emit('deliver-group', {
    order: props.order,
    group: groupKey,
  });
};

const buildMessage = (groupKey, label, status) => {
  const byGroup = {
    bebida: {
      listo: 'Tus bebidas ya están listas.',
      preparando: 'Tus bebidas siguen en preparación.',
      entregado: 'Bebidas entregadas. ¡Disfrútalas!',
      pendiente: 'Tus bebidas están pendientes por iniciar.',
    },
    plato: {
      listo: 'Tus platos ya están listos.',
      preparando: 'Tus platos siguen en preparación.',
      entregado: 'Platos entregados. ¡Buen provecho!',
      pendiente: 'Tus platos están pendientes por iniciar.',
    },
  };

  const fallback = byGroup[groupKey]?.[status];
  if (fallback) return fallback;

  if (status === 'listo') return `${label} listos para entregar.`;
  if (status === 'preparando') return `${label} en preparación.`;
  if (status === 'entregado') return `${label} entregados.`;
  return `${label} pendientes por iniciar.`;
};

const stepClass = (currentStatus, status, index, groupKey) => {
  const currentIndex = statusIndex(currentStatus);
  return {
    'is-done': index <= currentIndex,
    'is-current': index === currentIndex,
    [`is-group-${groupKey}`]: true,
    [`is-${status}`]: true,
  };
};

const capitalize = (value) => String(value).charAt(0).toUpperCase() + String(value).slice(1);
</script>

<style scoped>
.service-status {
  margin-top: 4px;
  display: grid;
  gap: 12px;
  font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
.service-status__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.service-status__header h3 {
  margin: 0;
  font-size: 14px;
  color: #dce7ff;
}
.summary {
  border: 1px solid #314262;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 11px;
  color: #9ab0d5;
  background: rgba(16, 24, 40, 0.72);
}
.summary--success { border-color: rgba(52, 211, 153, 0.5); color: #79efbd; }
.summary--partial { border-color: rgba(250, 204, 21, 0.45); color: #ffd86e; }

.service-group {
  border: 1px solid #2f3e5f;
  border-radius: 14px;
  padding: 12px;
  background: linear-gradient(180deg, rgba(16, 25, 41, 0.9), rgba(10, 18, 32, 0.96));
  display: grid;
  gap: 10px;
}

.service-group__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}
.service-group__title-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}
.service-group__icon { font-size: 16px; }
.service-group__header h4 {
  margin: 0;
  font-size: 14px;
}

.chip {
  font-size: 11px;
  border-radius: 999px;
  padding: 4px 9px;
  border: 1px solid transparent;
}
.chip--pendiente { color: #d1d5db; background: rgba(107, 114, 128, 0.15); border-color: rgba(107, 114, 128, 0.45); }
.chip--preparando { color: #bfdbfe; background: rgba(59, 130, 246, 0.18); border-color: rgba(96, 165, 250, 0.45); }
.chip--listo { color: #fed7aa; background: rgba(249, 115, 22, 0.18); border-color: rgba(251, 146, 60, 0.44); }
.chip--entregado { color: #bbf7d0; background: rgba(16, 185, 129, 0.18); border-color: rgba(52, 211, 153, 0.45); }

.service-group__line-wrap {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.progress {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 2px;
  flex: 1;
}
.progress__step {
  display: grid;
  justify-items: center;
  gap: 6px;
  position: relative;
}
.dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  border: 1.7px solid #3f516f;
  background: #101a2a;
  transition: all 260ms ease;
  z-index: 2;
}
.line {
  position: absolute;
  top: 4px;
  right: -52%;
  width: 100%;
  height: 1.5px;
  background: #2a3954;
  transition: background 260ms ease;
}
.label {
  font-size: 10px;
  color: #8fa4c8;
  text-align: center;
  line-height: 1.3;
}

.progress__step.is-done .dot { background: var(--state-color); border-color: var(--state-color); }
.progress__step.is-current .dot {
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--state-color) 26%, transparent);
  animation: pulse 1.8s ease-in-out infinite;
}
.progress__step.is-done .line { background: var(--state-color); }
.progress__step.is-pendiente { --state-color: #9ca3af; }
.progress__step.is-preparando { --state-color: #60a5fa; }
.progress__step.is-listo { --state-color: #fb923c; }
.progress__step.is-entregado { --state-color: #34d399; }

.deliver-btn {
  min-width: 106px;
  height: 36px;
  border: 0;
  border-radius: 11px;
  padding: 0 12px;
  font-size: 13px;
  font-weight: 700;
  color: #f8fbff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  cursor: pointer;
  transition: transform 170ms ease, filter 170ms ease, opacity 170ms ease;
}
.deliver-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  filter: brightness(1.05);
}
.deliver-btn:disabled {
  opacity: 0.46;
  cursor: not-allowed;
}
.deliver-btn--bebida {
  background: linear-gradient(180deg, #38bdf8, #0369a1);
}
.deliver-btn--plato {
  background: linear-gradient(180deg, #fb923c, #c2410c);
}
.spinner {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: rgba(255, 255, 255, 0.95);
  animation: spin 640ms linear infinite;
}

.service-group__message {
  margin: 0;
  font-size: 12px;
  color: #c3d2ee;
}

.empty {
  margin: 0;
  color: #9fb0cf;
  font-size: 13px;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.08); }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
