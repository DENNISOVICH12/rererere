<template>
  <div v-if="open" class="overlay" @click.self="$emit('cancel')">
    <div class="dialog">
      <h3>{{ title }}</h3>
      <p>{{ message }}</p>
      <div class="actions">
        <button class="muted" @click="$emit('cancel')">Volver</button>
        <button class="danger" :disabled="loading" @click="$emit('confirm')">{{ confirmText }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  open: Boolean,
  title: String,
  message: String,
  confirmText: { type: String, default: 'Confirmar' },
  loading: Boolean,
});

defineEmits(['confirm', 'cancel']);
</script>

<style scoped>
.overlay{ position:fixed; inset:0; background:rgba(0,0,0,.65); display:grid; place-items:center; padding:16px; }
.dialog{ background:#111b2f; border:1px solid #2a3752; border-radius:14px; width:min(420px,100%); padding:16px; color:#eaf2ff; }
.actions{ display:flex; gap:10px; margin-top:14px; }
button{ flex:1; border:0; border-radius:10px; padding:12px; }
.muted{ background:#2a3752; color:#eaf2ff; }
.danger{ background:#ff6f7c; color:#28080c; font-weight:600; }
</style>
