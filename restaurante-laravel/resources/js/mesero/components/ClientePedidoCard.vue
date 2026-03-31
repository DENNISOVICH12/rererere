<template>
  <article class="cliente-card">
    <header class="cliente-header">
      <div class="cliente-identidad">
        <span class="avatar">{{ inicialCliente }}</span>
        <div>
          <h3>{{ cliente.nombre || `Cliente #${cliente.id}` }}</h3>
          <p>{{ pedidos.length }} pedido(s) · Total ${{ money(totalCliente) }}</p>
        </div>
      </div>

        <div class="acciones-header">
  <span class="timer" :class="`timer--${timerTone}`">⏱ {{ elapsedText }}</span>

  <button
  class="action"
  :disabled="busy || !canEdit"
  @click="$emit('edit', cliente)"
>
  ✏️ Editar pedido
</button>

<button
  class="action action-secondary"
  :disabled="busy || !canEdit"
  @click="$emit('send-to-kitchen', cliente)"
>
  🍽️ Enviar a cocina
</button>

  <p v-if="!canEdit" class="locked">
    Pedido en cocina, no editable
  </p>
</div>
    </header>

    <div class="pedidos-resumen">
      <section class="grupo grupo--platos">
        <h4>🍽 Platos</h4>
        <ul v-if="platos.length">
          <li v-for="item in platos" :key="item.key">
            <div>
              <strong>{{ item.nombre }}</strong>
              <p>x{{ item.cantidad }}</p>
              <p v-if="item.nota" class="nota">Nota: {{ item.nota }}</p>
            </div>
            <span>${{ money(item.importe) }}</span>
          </li>
        </ul>
        <p v-else class="empty">Sin platos</p>
      </section>

      <section class="grupo grupo--bebidas">
        <h4>🍹 Bebidas</h4>
        <ul v-if="bebidas.length">
          <li v-for="item in bebidas" :key="item.key">
            <div>
              <strong>{{ item.nombre }}</strong>
              <p>x{{ item.cantidad }}</p>
              <p v-if="item.nota" class="nota">Nota: {{ item.nota }}</p>
            </div>
            <span>${{ money(item.importe) }}</span>
          </li>
        </ul>
        <p v-else class="empty">Sin bebidas</p>
      </section>
    </div>


    <section v-if="pedidos.length" class="pedidos-mini">
      <PedidoCliente
        v-for="pedido in pedidos"
        :key="pedido.id"
        :pedido="{ ...pedido, items: normalizeItems(pedido), total: pedido.total }"
        :show-order-id="false"
        title="Orden del cliente"
      />
    </section>

    <section class="estado-servicio" v-if="pedidos.length">
      <ServiceStatusTracker
        :order="serviceOrder"
        :busy="busy"
        @deliver-group="(payload) => {
  console.log('CLICK ENTREGAR', payload)
  $emit('deliver-group', { cliente, ...payload })
}"
      />
    </section>

    <section v-if="editing" class="editor">
      <h4>Editar pedido (5 minutos)</h4>
      <div class="editor-items">
        <div v-for="(item, index) in draftItems" :key="item.key" class="editor-item">
          <select v-model.number="item.menu_item_id">
            <option v-for="opt in menuOptions" :key="opt.id" :value="opt.id">{{ opt.nombre }}</option>
          </select>
          <input v-model.number="item.cantidad" type="number" min="1" />
          <input v-model="item.nota" type="text" placeholder="Nota" />
          <button class="btn-small danger" @click="removeItem(index)">Eliminar</button>
        </div>
      </div>

      <div class="editor-add">
        <select v-model.number="newItemId">
          <option :value="null">Selecciona un producto</option>
          <option v-for="opt in menuOptions" :key="opt.id" :value="opt.id">{{ opt.nombre }}</option>
        </select>
        <button class="btn-small" @click="appendItem">Agregar producto</button>
      </div>

      <div class="editor-actions">
        <button class="action" :disabled="busy || !draftItems.length" @click="saveDraft">Guardar cambios</button>
        <button class="btn-small ghost" :disabled="busy" @click="$emit('cancel-edit')">Cancelar</button>
      </div>
    </section>


    <details v-if="pedidos.length" class="operativa">
      <summary>Vista operativa</summary>
      <OrderCard
        :order="pedidos[0]"
        :elapsed-text="elapsedText"
        :busy="busy"
        :hide-actions="true"
        title="Orden activa"
      />
    </details>

    <footer class="footer-actions">
      <button class="action action-secondary" :disabled="busy || billing" @click="$emit('facturar-cliente', cliente)">
        {{ billing ? 'Facturando...' : 'Facturar cliente' }}
      </button>
    </footer>
  </article>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import ServiceStatusTracker from './ServiceStatusTracker.vue';
import OrderCard from './OrderCard.vue';
import PedidoCliente from './PedidoCliente.vue';
import { normalizeGroupKey } from '../utils/serviceStatus';

const props = defineProps({
  cliente: { type: Object, required: true },
  pedidos: { type: Array, default: () => [] },
  elapsedText: { type: String, required: true },
  timerTone: { type: String, default: 'ok' },
  busy: { type: Boolean, default: false },
  billing: { type: Boolean, default: false },
  canEdit: { type: Boolean, default: false },
  editing: { type: Boolean, default: false },
  draftItems: { type: Array, default: () => [] },
  menuOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['deliver-group', 'facturar-cliente', 'edit', 'save-edit', 'cancel-edit', 'send-to-kitchen']);

const newItemId = ref(null);

watch(
  () => props.editing,
  (value) => {
    if (!value) newItemId.value = null;
  },
);

const inicialCliente = computed(() => (props.cliente?.nombre || 'C').trim().charAt(0).toUpperCase());

const allItems = computed(() => props.pedidos.flatMap((pedido, pedidoIndex) => {
  const items = pedido.detalle ?? pedido.items ?? [];
  return items.map((item, itemIndex) => ({
    key: item.id ?? `${pedido.id}-${pedidoIndex}-${itemIndex}`,
    nombre: item?.menu_item?.nombre ?? item?.menuItem?.nombre ?? item?.nombre ?? 'Ítem',
    cantidad: Number(item?.cantidad ?? 1),
    importe: Number(item?.importe ?? item?.precio_unitario ?? item?.precio ?? 0),
    grupo: normalizeGroupKey(item?.grupo_servicio ?? item?.categoria),
    nota: item?.nota ?? '',
  }));
}));

const platos = computed(() => allItems.value.filter((item) => item.grupo !== 'bebida'));
const bebidas = computed(() => allItems.value.filter((item) => item.grupo === 'bebida'));
const totalCliente = computed(() => allItems.value.reduce((acc, item) => acc + item.importe, 0));

const normalizeItems = (pedido) => {
  const items = pedido?.detalle ?? pedido?.items ?? [];
  return items.map((item, itemIndex) => ({
    id: item.id ?? `${pedido.id}-${itemIndex}`,
    nombre: item?.menu_item?.nombre ?? item?.menuItem?.nombre ?? item?.nombre ?? 'Ítem',
    cantidad: Number(item?.cantidad ?? 1),
    importe: Number(item?.importe ?? item?.precio_unitario ?? item?.precio ?? 0),
  }));
};

const serviceOrder = computed(() => ({
  ...props.pedidos[0],
  items: allItems.value.map((item) => ({
    ...item,
    grupo_servicio: item.grupo,
  })),
}));

const appendItem = () => {
  if (!newItemId.value) return;
  emit('save-edit', {
    mode: 'append',
    item: {
      key: `new-${Date.now()}`,
      menu_item_id: newItemId.value,
      cantidad: 1,
      nota: '',
    },
  });
};

const removeItem = (index) => emit('save-edit', { mode: 'remove', index });

const saveDraft = () => emit('save-edit', { mode: 'commit' });

const money = (value) => Number(value || 0).toFixed(2);
</script>

<style scoped>
.cliente-card {
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 16px;
  padding: 16px;
  display: grid;
  gap: 14px;
  background: linear-gradient(135deg, rgba(17, 27, 47, 0.78), rgba(17, 27, 47, 0.5));
  backdrop-filter: blur(8px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}
.cliente-header,
.cliente-identidad,
.acciones-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.cliente-header { align-items: flex-start; }
.cliente-identidad { justify-content: flex-start; }
.avatar {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-weight: 700;
  background: rgba(37, 99, 235, 0.35);
  color: #dbe9ff;
}
h3, h4 { margin: 0; }
p { margin: 0; color: #9cb0d8; font-size: .88rem; }
.timer { font-weight: 700; }
.timer--ok { color: #34d399; }
.timer--warning { color: #facc15; }
.timer--danger { color: #fb7185; }
.pedidos-mini { display: grid; gap: 8px; }
.locked { color: #fda4af; max-width: 160px; text-align: right; }
.pedidos-resumen {
  display: grid;
  gap: 12px;
  grid-template-columns: 1fr;
}
.grupo {
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  padding: 10px;
  background: rgba(15, 23, 42, 0.45);
}
.grupo ul { list-style: none; padding: 0; margin: 8px 0 0; display: grid; gap: 8px; }
.grupo li { display: flex; justify-content: space-between; gap: 10px; }
.nota { color: #fde68a; font-size: .8rem; }
.empty { color: #8093ba; }
.editor { border-top: 1px solid rgba(255,255,255,.12); padding-top: 12px; display: grid; gap: 10px; }
.editor-items { display: grid; gap: 8px; }
.editor-item, .editor-add, .editor-actions { display: grid; grid-template-columns: 1.2fr .5fr 1fr auto; gap: 8px; }
.editor-add { grid-template-columns: 1fr auto; }
select, input {
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(148, 163, 184, 0.35);
  border-radius: 10px;
  color: #dbeafe;
  padding: 8px;
}
.action, .btn-small {
  border: 0;
  border-radius: 10px;
  padding: 9px 12px;
  font-weight: 600;
  background: #2563eb;
  color: #fff;
}
.btn-small { background: #334155; }
.btn-small.danger { background: #be123c; }
.btn-small.ghost { background: transparent; border: 1px solid rgba(148, 163, 184, 0.45); }
.action-secondary { background: #0ea5e9; }
.operativa summary { cursor: pointer; color: #93c5fd; margin-bottom: 8px; }
.footer-actions { display: flex; justify-content: flex-end; }

@media (min-width: 620px) {
  .pedidos-resumen { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 640px) {
  .cliente-header,
  .acciones-header,
  .editor-item,
  .editor-actions { grid-template-columns: 1fr; display: grid; }
  .acciones-header { justify-items: flex-start; }
  .locked { text-align: left; }
}
</style>
