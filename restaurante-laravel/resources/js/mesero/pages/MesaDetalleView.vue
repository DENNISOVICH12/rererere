<template>
  <section class="layout" v-if="mesa">
    <header class="header">
      <button class="back" @click="$emit('back')">← Mesas</button>
      <h1>Mesa {{ mesa.codigo }}</h1>
    </header>

    <form class="add-cliente" @submit.prevent="submitCliente">
      <input v-model="nombre" type="text" placeholder="Nombre cliente (opcional)" maxlength="120" />
      <button :disabled="creating">{{ creating ? 'Agregando...' : 'Agregar cliente' }}</button>
    </form>

    <div class="clientes-grid">
      <ClienteCard
        v-for="cliente in mesa.clientes"
        :key="cliente.id"
        :cliente="cliente"
        :loading="facturandoId === cliente.id"
        @facturar="$emit('facturar', $event)"
      />
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import ClienteCard from '../components/ClienteCard.vue';

const props = defineProps({
  mesa: { type: Object, default: null },
  creating: { type: Boolean, default: false },
  facturandoId: { type: Number, default: null },
});

const emit = defineEmits(['back', 'add-cliente', 'facturar']);
const nombre = ref('');

const submitCliente = () => {
  emit('add-cliente', { nombre: nombre.value.trim() || null });
  nombre.value = '';
};
</script>

<style scoped>
.layout { display: grid; gap: 14px; }
.header { display: flex; align-items: center; gap: 12px; }
.back { border: 0; border-radius: 8px; padding: 8px 10px; background: #1f2f52; color: #eaf1ff; }
.add-cliente { display: grid; grid-template-columns: 1fr auto; gap: 10px; }
input { background: #0d172b; border: 1px solid #2a3a5f; color: #fff; border-radius: 10px; padding: 10px; }
.add-cliente button { border: 0; border-radius: 10px; background: #1d4ed8; color: #fff; padding: 10px 14px; font-weight: 600; }
.clientes-grid { display: grid; gap: 12px; }
</style>
