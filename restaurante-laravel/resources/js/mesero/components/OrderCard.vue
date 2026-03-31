<template>
  <article class="card" :class="{ overdue: order.change_request_overdue }">
    
    <header class="row">
      <strong>{{ cardTitle }}</strong>
    </header>

    <ServiceStatusTracker
      :order="order"
      :busy="busy"
      @deliver-group="(payload) => $emit('deliver-group', { order, ...payload })"
    />

    <p class="timer">{{ elapsedText }}</p>
    <p class="meta">{{ order.items_count }} ítems · Mesa {{ order.mesa || '-' }}</p>
    <p class="meta">Cliente: {{ customerName }}</p>

    <!-- Estados informativos -->
    <p v-if="order.estado === 'modificacion_solicitada'" class="locked">
      Solicitud de cambio registrada. Esperando atención del mesero.
    </p>

    <p v-if="order.change_request_overdue" class="alert">
      ⚠ Lleva demasiado tiempo retenido. Requiere decisión inmediata.
    </p>

    <p v-if="!order.can_be_edited" class="locked">
      Pedido en cocina, no editable.
    </p>

    <!-- ACCIONES -->
    <div v-if="!hideActions" class="actions">

      <!-- EDITAR -->
      <button
        :disabled="busy || !order.can_be_edited"
        @click="$emit('edit', order)"
      >
        ✏️ Editar
      </button>

      <!-- ENVIAR A COCINA (NUEVO) -->
      <button
        class="success"
        :disabled="busy || !order.can_be_edited"
        @click="$emit('send', order)"
      >
        🍽️ Enviar a cocina
      </button>

      <!-- SOLICITAR CAMBIO -->
      <button
        class="warn"
        :disabled="busy || !order.can_request_change"
        @click="$emit('request-change', order)"
      >
        Marcar cambio solicitado
      </button>

      <!-- CANCELAR -->
      <button
        class="danger"
        :disabled="busy || !order.can_be_edited"
        @click="$emit('delete', order)"
      >
        Cancelar
      </button>

    </div>
  </article>
</template>

<script setup>
import ServiceStatusTracker from './ServiceStatusTracker.vue';

const props = defineProps({
  order: { type: Object, required: true },
  elapsedText: { type: String, required: true },
  busy: { type: Boolean, default: false },
  title: { type: String, default: '' },
  hideActions: { type: Boolean, default: false },
});

const customerName =
  props.order?.cliente_nombre ||
  props.order?.cliente?.nombre ||
  'Cliente invitado';

const cardTitle = props.title || `Cliente: ${customerName}`;

defineEmits([
  'edit',
  'delete',
  'request-change',
  'deliver-group',
  'send' // 🔥 NUEVO EVENTO
]);
</script>

<style scoped>
.card {
  background: #121a2b;
  border: 1px solid #29344d;
  border-radius: 14px;
  padding: 14px;
  display: grid;
  gap: 8px;
}

.card.overdue {
  border-color: #ff9b62;
  box-shadow: 0 0 0 1px rgba(255,155,98,.25);
}

.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.timer {
  margin: 0;
  color: #7ecbff;
  font-weight: 600;
}

.meta {
  margin: 0;
  color: #a8b4ce;
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
  grid-template-columns: 1fr;
  gap: 8px;
  margin-top: 4px;
}

button {
  background: #2b8cff;
  color: #061124;
  border: 0;
  border-radius: 10px;
  padding: 10px;
  font-weight: 600;
}

/* NUEVO */
button.success {
  background: #10b981;
  color: white;
}

button.warn {
  background: #ffd37b;
  color: #382800;
}

button.danger {
  background: #ff6f7c;
}

button:disabled {
  opacity: .5;
}
</style>