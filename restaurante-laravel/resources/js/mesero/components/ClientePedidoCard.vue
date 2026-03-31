<template>
  <article class="cliente-card" :class="{ 'is-billed': isBilled }">
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

        <div class="header-buttons">
          <button
            class="btn btn-primary"
            :disabled="isActionDisabled || !canEdit"
            @click="$emit('edit', cliente)"
          >
            ✏️ Editar pedido
          </button>

          <button
            class="btn btn-secondary"
            :disabled="isActionDisabled || !canSendToKitchen"
            @click="$emit('send-to-kitchen', cliente)"
          >
            🍽️ Enviar a cocina
          </button>
        </div>

        <p v-if="isBilled" class="state state--billed">Facturado</p>
        <p v-else-if="!canEdit" class="state">Pedido en cocina, no editable</p>
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

    <section v-if="pedidos.length" class="estado-servicio">
      <ServiceStatusTracker
        :order="serviceOrder"
        :busy="busy || isBilled"
        @deliver-group="(payload) => $emit('deliver-group', { cliente, ...payload })"
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
        <button class="btn btn-primary" :disabled="busy || isBilled || !draftItems.length" @click="saveDraft">Guardar cambios</button>
        <button class="btn-small ghost" :disabled="busy" @click="$emit('cancel-edit')">Cancelar</button>
      </div>
    </section>

    <footer class="footer-actions">
      <button class="btn btn-success" :disabled="isActionDisabled || billing || isBilled" @click="$emit('facturar-cliente', cliente)">
        {{ billing ? 'Facturando...' : 'Facturar cliente' }}
      </button>
    </footer>
  </article>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import ServiceStatusTracker from './ServiceStatusTracker.vue';
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
  canSendToKitchen: { type: Boolean, default: false },
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

const billingFlags = ['facturado', 'is_billed', 'billed', 'factura_id', 'facturado_at'];
const statusFlags = ['facturado', 'cerrado', 'closed', 'paid'];
const isBilled = computed(() => props.pedidos.some((pedido) => {
  const hasFlag = billingFlags.some((flag) => Boolean(pedido?.[flag]));
  const estado = String(pedido?.estado || pedido?.status || '').toLowerCase();
  return hasFlag || statusFlags.includes(estado);
}));

const isActionDisabled = computed(() => props.busy || props.billing || isBilled.value);

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
  font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 18px;
  padding: 18px;
  display: grid;
  gap: 14px;
  background: linear-gradient(155deg, rgba(17, 27, 47, 0.92), rgba(10, 18, 36, 0.88));
  box-shadow: 0 10px 24px rgba(3, 8, 20, 0.3);
  transition: opacity 180ms ease, filter 180ms ease;
}
.cliente-card.is-billed {
  opacity: 0.6;
  filter: grayscale(0.15);
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
  border-radius: 14px;
  display: grid;
  place-items: center;
  font-weight: 700;
  background: rgba(37, 99, 235, 0.28);
  color: #dbe9ff;
}
h3 { margin: 0; font-size: 1.02rem; font-weight: 600; color: #f8fbff; }
h4 { margin: 0; font-size: .96rem; font-weight: 600; color: #edf3ff; }
p { margin: 0; color: #b6c4de; font-size: .86rem; }
.timer { font-size: .82rem; font-weight: 700; }
.timer--ok { color: #4ade80; }
.timer--warning { color: #facc15; }
.timer--danger { color: #fb7185; }
.header-buttons { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
.state { color: #fca5a5; text-align: right; font-size: .8rem; }
.state--billed { color: #86efac; font-weight: 600; }
.pedidos-mini { display: grid; gap: 8px; }
.pedidos-resumen {
  display: grid;
  gap: 12px;
  grid-template-columns: 1fr;
}
.grupo {
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 14px;
  padding: 12px;
  background: rgba(15, 23, 42, 0.54);
}
.grupo ul { list-style: none; padding: 0; margin: 8px 0 0; display: grid; gap: 8px; }
.grupo li { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
.grupo li span { font-weight: 600; color: #e3ecff; }
.nota { color: #fcd34d; font-size: .8rem; }
.empty { color: #91a5c9; }
.editor { border-top: 1px solid rgba(148,163,184,.2); padding-top: 12px; display: grid; gap: 10px; }
.editor-items { display: grid; gap: 8px; }
.editor-item, .editor-add, .editor-actions { display: grid; grid-template-columns: 1.2fr .5fr 1fr auto; gap: 8px; }
.editor-add { grid-template-columns: 1fr auto; }
select, input {
  background: rgba(15, 23, 42, 0.84);
  border: 1px solid rgba(148, 163, 184, 0.35);
  border-radius: 10px;
  color: #dbeafe;
  padding: 9px;
}
.btn, .btn-small {
  border: 0;
  border-radius: 11px;
  padding: 10px 13px;
  font-weight: 600;
  transition: transform 140ms ease, opacity 140ms ease, filter 140ms ease;
}
.btn:hover:not(:disabled), .btn-small:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.05); }
.btn:disabled, .btn-small:disabled {
  opacity: .48;
  cursor: not-allowed;
  transform: none;
  filter: none;
}
.btn-primary { background: #3b82f6; color: #eff6ff; }
.btn-secondary { background: #2563eb; color: #eff6ff; }
.btn-success { background: #10b981; color: #022c22; }
.btn-small { background: #334155; color: #f8fafc; }
.btn-small.danger { background: #b91c1c; }
.btn-small.ghost { background: transparent; border: 1px solid rgba(148, 163, 184, 0.45); }
.footer-actions { display: flex; justify-content: flex-end; }

@media (min-width: 620px) {
  .pedidos-resumen { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 760px) {
  .cliente-header,
  .acciones-header,
  .editor-item,
  .editor-actions { grid-template-columns: 1fr; display: grid; }
  .acciones-header,
  .header-buttons { justify-items: flex-start; justify-content: flex-start; }
  .state { text-align: left; }
}
</style>
