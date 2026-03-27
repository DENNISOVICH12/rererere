<template>
  <article class="card" :class="{ overdue: order.change_request_overdue }">
    <header class="row">
      <strong>Pedido #{{ order.id }}</strong>
      <OrderStatusBadge :status="order.estado" />
    </header>
    <ServiceStatusTracker :order="order" />
    <p class="timer">{{ elapsedText }}</p>
    <p class="meta">{{ order.items_count }} ítems · Mesa {{ order.mesa || '-' }}</p>
    <p class="meta">Cliente: {{ order.cliente?.nombre || 'Sin cliente' }}</p>
    <p v-if="order.estado === 'modificacion_solicitada'" class="locked">Solicitud de cambio registrada. Esperando atención del mesero.</p>
    <p v-if="order.change_request_overdue" class="alert">⚠ Lleva demasiado tiempo retenido. Requiere decisión inmediata.</p>
    <p v-if="!order.can_be_edited" class="locked">Ya fue enviado a cocina.</p>
    <div class="actions">
      <button :disabled="busy || !order.can_be_edited" @click="$emit('edit', order)">Editar</button>
      <button
        class="warn"
        :disabled="busy || !order.can_request_change"
        @click="$emit('request-change', order)"
      >
        Marcar cambio solicitado
      </button>

      <button class="danger" :disabled="busy || !order.can_be_edited" @click="$emit('delete', order)">Cancelar</button>
      <button
        class="deliver deliver--drink"
        :disabled="busy || !canDeliverBebidas"
        @click="$emit('deliver-group', { order, group: 'bebida' })"
      >
        Entregar bebidas
      </button>
      <button
        class="deliver deliver--food"
        :disabled="busy || !canDeliverPlatos"
        @click="$emit('deliver-group', { order, group: 'plato' })"
      >
        Entregar platos
      </button>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue';
import OrderStatusBadge from './OrderStatusBadge.vue';
import ServiceStatusTracker from './ServiceStatusTracker.vue';
import { getGroupStatus, normalizeGroupKey } from '../utils/serviceStatus';

const props = defineProps({
  order: { type: Object, required: true },
  elapsedText: { type: String, required: true },
  busy: { type: Boolean, default: false },
});

const getItemsByGroup = (group) =>
  (Array.isArray(props.order?.items) ? props.order.items : []).filter(
    (item) => normalizeGroupKey(item?.grupo_servicio || item?.categoria) === group,
  );

const canDeliverGroup = (group) => {
  const items = getItemsByGroup(group);
  if (!items.length) return false;
  const currentStatus = getGroupStatus(items, group);
  return ['pendiente', 'preparando', 'listo'].includes(currentStatus);
};

const canDeliverBebidas = computed(() => canDeliverGroup('bebida'));
const canDeliverPlatos = computed(() => canDeliverGroup('plato'));

defineEmits(['edit', 'delete', 'request-change', 'deliver-group']);
</script>

<style scoped>
.card { background: #121a2b; border: 1px solid #29344d; border-radius: 14px; padding: 14px; display: grid; gap: 8px; }
.card.overdue { border-color: #ff9b62; box-shadow: 0 0 0 1px rgba(255,155,98,.25); }
.row { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.timer { margin: 0; color: #7ecbff; font-weight: 600; }
.meta { margin: 0; color: #a8b4ce; font-size: 13px; }
.locked { margin: 0; color: #ffb4a1; font-size: 12px; }
.alert { margin: 0; color: #ffbe8a; font-size: 12px; font-weight: 600; }
.actions { display: grid; grid-template-columns: 1fr; gap: 8px; margin-top: 4px; }
button { background: #2b8cff; color: #061124; border: 0; border-radius: 10px; padding: 10px; font-weight: 600; }
button.warn { background: #ffd37b; color: #382800; }

button.danger { background: #ff6f7c; }
button:disabled { opacity: .5; }
.deliver { color: #fff; }
.deliver--drink { background: linear-gradient(180deg, #06b6d4, #0e7490); }
.deliver--food { background: linear-gradient(180deg, #f59e0b, #b45309); }
</style>
