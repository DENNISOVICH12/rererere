<template>
  <article class="card" :class="[{ overdue: order.change_request_overdue }, { billed: isBilled }]">
    <header class="row">
      <strong>{{ cardTitle }}</strong>
      <span v-if="isBilled" class="badge-pagado">Pagado</span>
    </header>

    <ServiceStatusTracker
      :order="order"
      :busy="busy || isBilled"
      @deliver-group="(payload) => $emit('deliver-group', { order, ...payload })"
    />

    <p class="timer" :class="`timer--${timerTone}`">{{ elapsedText }}</p>
    <p class="meta">{{ order.items_count }} ítems · Mesa {{ order.mesa_numero || order.mesa_id || '-' }}</p>
    <p class="meta">Cliente: {{ customerName }}</p>

    <p v-if="order.estado === 'modificacion_solicitada'" class="locked">
      Solicitud de cambio registrada. Esperando atención del mesero.
    </p>

    <p v-if="order.change_request_overdue" class="alert">
      ⚠ Lleva demasiado tiempo retenido. Requiere decisión inmediata.
    </p>

    <p v-if="!order.can_be_edited" class="locked">
      Pedido en cocina, no editable.
    </p>

    <div v-if="!hideActions" class="actions">
      <button class="btn btn-primary" :disabled="disabledAll || !order.can_be_edited" @click="$emit('edit', order)">✏️ Editar</button>

      <button class="btn btn-secondary" :disabled="disabledAll || !canSendKitchen" @click="$emit('send', order)">🍽️ Enviar a cocina</button>

      <button class="btn btn-warning" :disabled="disabledAll || !order.can_request_change" @click="$emit('request-change', order)">Marcar cambio</button>

      <button class="btn btn-danger" :disabled="disabledAll || !order.can_be_edited" @click="$emit('delete', order)">Cancelar</button>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue';
import ServiceStatusTracker from './ServiceStatusTracker.vue';

const props = defineProps({
  order: { type: Object, required: true },
  elapsedText: { type: String, required: true },
  busy: { type: Boolean, default: false },
  title: { type: String, default: '' },
  hideActions: { type: Boolean, default: false },
  timerTone: { type: String, default: 'ok' },
});

const customerName =
  props.order?.cliente_nombre ||
  props.order?.cliente?.nombre ||
  'Cliente invitado';

const cardTitle = props.title || `Cliente: ${customerName}`;

const isBilled = computed(() => {
  const flags = ['facturado', 'is_billed', 'billed', 'factura_id', 'facturado_at'];
  const status = String(props.order?.estado || props.order?.status || '').toLowerCase();
  return flags.some((flag) => Boolean(props.order?.[flag])) || ['facturado', 'cerrado', 'closed', 'paid'].includes(status);
});

const canSendKitchen = computed(() => props.order?.can_send_to_kitchen ?? props.order?.can_be_edited);
const disabledAll = computed(() => props.busy || isBilled.value);

defineEmits(['edit', 'delete', 'request-change', 'deliver-group', 'send']);
</script>

<style scoped>
.card {
  font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: #121a2b;
  border: 1px solid #29344d;
  border-radius: 18px;
  padding: 16px;
  display: grid;
  gap: 10px;
  box-shadow: 0 8px 24px rgba(3, 8, 20, 0.3);
}
.card.billed { opacity: 0.6; }
.card.overdue {
  border-color: #ff9b62;
  box-shadow: 0 0 0 1px rgba(255, 155, 98, 0.25);
}
.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}
.badge-pagado {
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  color: #bbf7d0;
  border: 1px solid rgba(52, 211, 153, 0.5);
  background: rgba(16, 185, 129, 0.18);
}
.timer { margin: 0; font-weight: 700; }
.timer--ok { color: #4ade80; }
.timer--warning { color: #facc15; }
.timer--danger { color: #fb7185; }
.meta {
  margin: 0;
  color: #b6c4de;
  font-size: 13px;
}
.locked {
  margin: 0;
  color: #ffb4a1;
  font-size: 12px;
}
.alert {
  margin: 0;
  color: #ffbe8a;
  font-size: 12px;
  font-weight: 600;
}
.actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
  margin-top: 4px;
}
.btn {
  border: 0;
  border-radius: 11px;
  padding: 10px;
  font-weight: 600;
  color: #eff6ff;
  transition: transform 140ms ease, filter 140ms ease, opacity 140ms ease;
}
.btn:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.05); }
.btn-primary { background: #3b82f6; }
.btn-secondary { background: #2563eb; }
.btn-warning { background: #f59e0b; color: #2a1202; }
.btn-danger { background: #ef4444; }
.btn:disabled {
  opacity: .5;
  cursor: not-allowed;
  transform: none;
  filter: none;
}
</style>
