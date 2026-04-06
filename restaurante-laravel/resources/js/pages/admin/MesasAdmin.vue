<template>
  <section class="mesas-admin">
    <header class="mesas-header">
      <div>
        <h1>🪑 Gestión de Mesas</h1>
        <p>Crear, visualizar y eliminar mesas desde administración.</p>
      </div>
      <button class="btn btn-primary" @click="showModal = true">Agregar Mesa</button>
    </header>

    <div class="card">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Número</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4">Cargando mesas...</td>
          </tr>
          <tr v-else-if="!mesas.length">
            <td colspan="4">No hay mesas registradas.</td>
          </tr>
          <tr v-for="mesa in mesas" :key="mesa.id">
            <td>{{ mesa.id }}</td>
            <td>{{ mesa.numero ?? '-' }}</td>
            <td>
              <span :class="['badge', mesa.estado === 'ocupada' ? 'badge-ocupada' : 'badge-libre']">
                {{ mesa.estado === 'ocupada' ? 'Ocupada' : 'Libre' }}
              </span>
            </td>
            <td>
              <button class="btn btn-danger" @click="removeMesa(mesa.id)">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="overlay" @click.self="showModal = false">
      <form class="modal" @submit.prevent="submitMesa">
        <h3>Nueva mesa</h3>
        <label>Número (opcional)</label>
        <input v-model.number="form.numero" type="number" min="1" placeholder="Ej: 8" />
        <div class="actions">
          <button type="button" class="btn" @click="showModal = false">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear</button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { createMesa, deleteMesa, listMesas } from '../../mesero/api';

const mesas = ref([]);
const loading = ref(false);
const showModal = ref(false);
const form = ref({ numero: null });

const loadMesas = async () => {
  loading.value = true;
  try {
    mesas.value = await listMesas();
  } finally {
    loading.value = false;
  }
};

const submitMesa = async () => {
  await createMesa(form.value.numero ? { numero: form.value.numero } : {});
  form.value.numero = null;
  showModal.value = false;
  await loadMesas();
};

const removeMesa = async (id) => {
  const ok = typeof window.showConfirm === 'function'
    ? await window.showConfirm('¿Seguro que deseas eliminar esta mesa?', {
        title: 'Eliminar mesa',
        confirmText: 'Eliminar',
        cancelText: 'Cancelar',
      })
    : false;

  if (!ok) return;
  await deleteMesa(id);
  await loadMesas();
};

onMounted(loadMesas);
</script>
