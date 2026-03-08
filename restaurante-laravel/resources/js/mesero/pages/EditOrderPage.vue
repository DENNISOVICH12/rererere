<template>
  <section>
    <button class="back" @click="$emit('back')">← Volver</button>
    <OrderEditor
      :order="order"
      :saving="saving"
      :sending="sending"
      @save="$emit('save', $event)"
      @send-to-kitchen="$emit('send-to-kitchen', $event)"
      @cancel="$emit('back')"
    />
  </section>
</template>

<script setup>
import OrderEditor from '../components/OrderEditor.vue';

defineProps({ order: Object, saving: Boolean, sending: Boolean });
defineEmits(['save', 'send-to-kitchen', 'back']);
</script>

<style scoped>
.back{ margin-bottom:10px; border:1px solid #2f3c58; background:#121a2b; color:#e2edff; border-radius:10px; padding:10px 12px; }
</style>
