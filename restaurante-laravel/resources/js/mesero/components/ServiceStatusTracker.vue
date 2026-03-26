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

      <ol class="progress" role="list">
        <li
          v-for="(status, index) in SERVICE_STEPS"
          :key="status"
          class="progress__step"
          :class="stepClass(group.currentStatus, status, index)"
        >
          <span class="dot" />
          <span class="label">{{ statusLabel(status) }}</span>
          <span v-if="index < SERVICE_STEPS.length - 1" class="line" />
        </li>
      </ol>

      <p class="service-group__message">{{ group.message }}</p>
    </article>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
});

const SERVICE_STEPS = ['pendiente', 'preparando', 'listo', 'entregado'];

const GROUP_META = {
  bebida: { label: 'Bebidas', icon: '🍹' },
  plato: { label: 'Cocina', icon: '🍽' },
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

const normalizeStatus = (status) => {
  const normalized = String(status || 'pendiente').toLowerCase();
  return SERVICE_STEPS.includes(normalized) ? normalized : 'pendiente';
};

const normalizeGroupKey = (group) => {
  const normalized = String(group || '').toLowerCase();
  if (normalized === 'bebida' || normalized === 'bar' || normalized === 'barra') return 'bebida';
  if (normalized) return normalized;
  return 'plato';
};

const statusIndex = (status) => SERVICE_STEPS.indexOf(normalizeStatus(status));

const deriveGroupStatus = (items = []) => {
  if (!items.length) return 'pendiente';
  const indexes = items
    .map((item) => normalizeStatus(item?.estado_servicio || item?._serviceStatus || item?.estado))
    .map(statusIndex);

  const min = Math.min(...indexes);
  const max = Math.max(...indexes);

  if (min === max) return SERVICE_STEPS[max];
  if (min < statusIndex('preparando') && max >= statusIndex('preparando')) return 'preparando';
  if (max === statusIndex('entregado') && min >= statusIndex('listo')) return 'listo';
  return SERVICE_STEPS[min] || 'pendiente';
};

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
      currentStatus: normalizeStatus(group?.estado || deriveGroupStatus(items)),
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
        currentStatus: deriveGroupStatus(groupData.items),
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

const stepClass = (currentStatus, status, index) => {
  const currentIndex = statusIndex(currentStatus);
  return {
    'is-done': index <= currentIndex,
    'is-current': index === currentIndex,
    [`is-${status}`]: true,
  };
};

const capitalize = (value) => String(value).charAt(0).toUpperCase() + String(value).slice(1);
</script>

<style scoped>
.service-status {
  margin-top: 8px;
  display: grid;
  gap: 12px;
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
  background: linear-gradient(180deg, rgba(16, 25, 41, 0.92), rgba(10, 18, 32, 0.98));
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
.chip--pendiente { color: #d1d5db; background: rgba(107, 114, 128, 0.15); border-color: rgba(107, 114, 128, 0.5); }
.chip--preparando { color: #fde68a; background: rgba(245, 158, 11, 0.15); border-color: rgba(250, 204, 21, 0.45); }
.chip--listo { color: #86efac; background: rgba(34, 197, 94, 0.16); border-color: rgba(74, 222, 128, 0.48); }
.chip--entregado { color: #93c5fd; background: rgba(59, 130, 246, 0.2); border-color: rgba(96, 165, 250, 0.48); }

.progress {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 4px;
}
.progress__step {
  display: grid;
  justify-items: center;
  gap: 6px;
  position: relative;
}
.dot {
  width: 12px;
  height: 12px;
  border-radius: 999px;
  border: 2px solid #3f516f;
  background: #101a2a;
  transition: all 240ms ease;
  z-index: 2;
}
.line {
  position: absolute;
  top: 5px;
  right: -52%;
  width: 100%;
  height: 2px;
  background: #2a3954;
  transition: background 240ms ease;
}
.label {
  font-size: 10px;
  color: #8fa4c8;
  text-align: center;
  line-height: 1.3;
}

.progress__step.is-done .dot { background: var(--state-color); border-color: var(--state-color); }
.progress__step.is-current .dot {
  box-shadow: 0 0 0 5px color-mix(in srgb, var(--state-color) 28%, transparent);
  animation: pulse 1.9s ease-in-out infinite;
}
.progress__step.is-done .line { background: var(--state-color); }
.progress__step.is-pendiente { --state-color: #9ca3af; }
.progress__step.is-preparando { --state-color: #facc15; }
.progress__step.is-listo { --state-color: #4ade80; }
.progress__step.is-entregado { --state-color: #60a5fa; }

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
</style>
