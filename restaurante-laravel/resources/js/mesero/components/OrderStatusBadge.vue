<template>
    <span :class="['status-badge', `status-${status}`]">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ status: { type: String, required: true } });

const map = {
    retenido: 'Ventana de cambios',
    modificacion_solicitada: 'Modificación solicitada',
    pendiente: 'Pendiente',
    preparando: 'En preparación',
    listo: 'Listo',
    entregado: 'Entregado',
};

const label = computed(() => map[props.status] ?? props.status);
</script>

<style scoped>
.status-badge { padding: 4px 10px; border-radius: 999px; font-size: 12px; border: 1px solid; }
.status-retenido { color: #7ecbff; border-color: #7ecbff; }
.status-modificacion_solicitada { color: #ffd37b; border-color: #ffd37b; }
.status-pendiente { color: #a9b4cc; border-color: #5a667f; }
.status-preparando { color: #ffbf4d; border-color: #ffbf4d; }
.status-listo { color: #62c4ff; border-color: #62c4ff; }
.status-entregado { color: #4dd596; border-color: #4dd596; }
</style>
