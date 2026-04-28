<template>
  <transition name="confirm-fade">
    <div v-if="open" class="overlay" role="dialog" aria-modal="true" @click.self="$emit('cancel')">
      <div class="dialog">
        <h3>{{ title }}</h3>
        <p>{{ message }}</p>
        <div class="actions">
          <button class="muted" type="button" :disabled="loading" @click="$emit('cancel')">
            {{ cancelText }}
          </button>
          <button class="danger" type="button" :disabled="loading" @click="$emit('confirm')">
            {{ loading ? 'Procesando...' : confirmText }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
defineProps({
  open: Boolean,
  title: { type: String, default: 'Confirmación' },
  message: { type: String, default: '' },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  loading: Boolean,
});

defineEmits(['confirm', 'cancel']);
</script>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgba(7, 10, 19, 0.72);
  backdrop-filter: blur(8px);
  display: grid;
  place-items: center;
  padding: 16px;
}

.dialog {
  width: min(420px, 100%);
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: linear-gradient(170deg, rgba(16, 24, 40, 0.96), rgba(13, 18, 33, 0.96));
  box-shadow: 0 24px 54px rgba(0, 0, 0, 0.45);
  padding: 24px;
  color: #f8ece4;
}

h3 {
  margin: 0 0 8px;
  font-size: 1.2rem;
  color: #ffd7aa;
}

p {
  margin: 0;
  line-height: 1.5;
  color: rgba(248, 236, 228, 0.92);
}

.actions {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

button {
  border-radius: 10px;
  padding: 9px 16px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

button:hover:not(:disabled) {
  transform: translateY(-1px);
}

button:focus-visible {
  outline: 2px solid #ffd7aa;
  outline-offset: 2px;
}

button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.muted {
  border: 1px solid rgba(248, 236, 228, 0.35);
  background: transparent;
  color: #f8ece4;
}

.muted:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.08);
}

.danger {
  background: #b91c1c;
  color: #fff;
}

.danger:hover:not(:disabled) {
  background: #dc2626;
  box-shadow: 0 6px 16px rgba(220, 38, 38, 0.38);
}

.confirm-fade-enter-active,
.confirm-fade-leave-active {
  transition: opacity 0.2s ease;
}

.confirm-fade-enter-from,
.confirm-fade-leave-to {
  opacity: 0;
}

@media (max-width: 420px) {
  .actions {
    flex-direction: column-reverse;
  }

  button {
    width: 100%;
  }
}
</style>
