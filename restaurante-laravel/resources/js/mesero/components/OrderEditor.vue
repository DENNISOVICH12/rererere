<template>
  <section class="editor">
    <header>
      <h2>Editar pedido #{{ localOrder.id }}</h2>
      <p>Estado actual: <strong>{{ localOrder.estado }}</strong></p>
      <p v-if="localOrder.estado === 'modificacion_solicitada'" class="state-help">
        Solicitud de cambio registrada. Este pedido no será enviado a cocina hasta que confirmes cambios.
      </p>
      <p v-if="localOrder.change_request_overdue" class="state-alert">
        Atención prioritaria: la solicitud de cambio superó el tiempo recomendado.
      </p>
    </header>

    <label>Mesa ID</label>
    <input v-model.number="localOrder.mesa_id" type="number" min="1" placeholder="ID de mesa" />

    <div class="items">
      <article v-for="(item, idx) in localOrder.items" :key="item.localKey" class="item-row">
        <div>
          <strong>{{ item.nombre }}</strong>
          <small>{{ item.categoria || 'Sin categoría' }}</small>
        </div>
        <div class="qty">
          <button @click="item.cantidad = Math.max(1, item.cantidad - 1)">-</button>
          <span>{{ item.cantidad }}</span>
          <button @click="item.cantidad += 1">+</button>
        </div>
        <textarea v-model="item.nota" rows="2" placeholder="Nota para cocina"></textarea>
        <button class="danger" @click="removeItem(idx)">Eliminar</button>
      </article>
    </div>

    <label>Agregar producto</label>
    <input v-model="search" @input="onSearch" placeholder="Buscar por nombre/categoría" />
    <div class="results" v-if="searchResults.length">
      <button v-for="result in searchResults" :key="result.id" @click="addItem(result)">
        {{ result.nombre }} · {{ result.categoria || '-' }}
      </button>
    </div>

    <footer>
      <button class="muted" @click="$emit('cancel')">Volver</button>
      <button :disabled="saving" @click="save">Guardar cambios</button>
      <button class="confirm" :disabled="saving || sending" @click="sendToKitchen">
        {{ sending ? 'Enviando...' : 'Guardar cambios y enviar a cocina' }}
      </button>
    </footer>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import { searchMenuItems } from '../api';

const props = defineProps({ order: { type: Object, required: true }, saving: Boolean, sending: Boolean });
const emit = defineEmits(['save', 'send-to-kitchen', 'cancel']);

const localOrder = ref({
  ...props.order,
  items: props.order.items.map((item, i) => ({ ...item, localKey: `${item.menu_item_id}-${i}-${Date.now()}` })),
});

const search = ref('');
const searchResults = ref([]);

let searchTimeout;
const onSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(async () => {
    if (!search.value.trim()) {
      searchResults.value = [];
      return;
    }
    searchResults.value = await searchMenuItems(search.value.trim());
  }, 250);
};

const addItem = (menuItem) => {
  localOrder.value.items.push({
    localKey: `new-${menuItem.id}-${Date.now()}`,
    menu_item_id: menuItem.id,
    nombre: menuItem.nombre,
    categoria: menuItem.categoria,
    cantidad: 1,
    nota: '',
  });
  search.value = '';
  searchResults.value = [];
};

const removeItem = (idx) => localOrder.value.items.splice(idx, 1);

const payload = computed(() => ({
  mesa_id: localOrder.value.mesa_id,
  items: localOrder.value.items.map((item) => ({
    menu_item_id: item.menu_item_id,
    cantidad: item.cantidad,
    nota: item.nota || null,
  })),
}));

const save = () => emit('save', { id: localOrder.value.id, payload: payload.value });
const sendToKitchen = () => emit('send-to-kitchen', localOrder.value);
</script>

<style scoped>
.editor{ display:grid; gap:10px; }
input,textarea{ width:100%; background:#0e1628; border:1px solid #2a3653; color:#eaf2ff; border-radius:10px; padding:10px; }
.items{ display:grid; gap:10px; max-height:46vh; overflow:auto; }
.item-row{ border:1px solid #26344f; border-radius:12px; padding:10px; display:grid; gap:8px; }
.qty{ display:flex; align-items:center; gap:12px; }
.qty button, footer button, .results button, .danger{ border:0; border-radius:10px; padding:10px 12px; }
.qty button, footer button:not(.muted):not(.danger){ background:#2b8cff; color:#061124; }
.results{ display:grid; gap:6px; }
.results button{ background:#1c2944; color:#eaf2ff; text-align:left; }
footer{ display:flex; gap:10px; margin-top:8px; flex-wrap:wrap; }
footer .confirm{ background:#35d39a; color:#07251c; flex:1 1 100%; font-weight:700; }
.muted{ background:#2a3752; color:#eaf2ff; }
.danger{ background:#ff6f7c; color:#2f0b12; }
.state-help { margin: 4px 0 0; color: #9bd8ff; font-size: 13px; }
.state-alert { margin: 4px 0 0; color: #ffb88f; font-size: 13px; font-weight: 600; }
</style>
