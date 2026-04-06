<template>
  <transition name="confirm-fade">
    <div
      v-if="visible"
      class="confirm-modal-overlay"
      role="dialog"
      aria-modal="true"
      aria-labelledby="confirm-modal-title"
      @click.self="cancel"
    >
      <div class="confirm-modal-box">
        <h3 id="confirm-modal-title" class="confirm-modal-title">Confirmación</h3>
        <p class="confirm-modal-message">{{ message }}</p>

        <div class="confirm-modal-actions">
          <button class="btn-secondary" type="button" @click="cancel">Cancelar</button>
          <button class="btn-danger" type="button" @click="confirm">Aceptar</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref } from 'vue'

const visible = ref(false)
const message = ref('')
let resolver = null

function cleanupAndResolve(value) {
  const currentResolver = resolver
  resolver = null
  visible.value = false
  currentResolver?.(value)
}

function open(msg) {
  message.value = msg
  visible.value = true
  return new Promise((resolve) => {
    resolver = resolve
  })
}

function confirm() {
  cleanupAndResolve(true)
}

function cancel() {
  cleanupAndResolve(false)
}

defineExpose({ open })
</script>

<style scoped>
.confirm-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgba(7, 10, 19, 0.72);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.confirm-modal-box {
  width: min(420px, 100%);
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: linear-gradient(170deg, rgba(16, 24, 40, 0.96), rgba(13, 18, 33, 0.96));
  box-shadow: 0 24px 54px rgba(0, 0, 0, 0.45);
  padding: 24px;
  color: #f8ece4;
}

.confirm-modal-title {
  margin: 0 0 8px;
  font-size: 1.2rem;
  color: #ffd7aa;
}

.confirm-modal-message {
  margin: 0;
  line-height: 1.5;
  color: rgba(248, 236, 228, 0.92);
}

.confirm-modal-actions {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-secondary,
.btn-danger {
  border-radius: 10px;
  padding: 9px 16px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.btn-secondary {
  border: 1px solid rgba(248, 236, 228, 0.35);
  background: transparent;
  color: #f8ece4;
}

.btn-danger {
  background: #b91c1c;
  color: #fff;
}

.btn-secondary:hover,
.btn-danger:hover {
  transform: translateY(-1px);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.08);
}

.btn-danger:hover {
  background: #dc2626;
  box-shadow: 0 6px 16px rgba(220, 38, 38, 0.38);
}

.btn-secondary:focus-visible,
.btn-danger:focus-visible {
  outline: 2px solid #ffd7aa;
  outline-offset: 2px;
}

.confirm-fade-enter-active,
.confirm-fade-leave-active {
  transition: opacity 0.2s ease;
}

.confirm-fade-enter-from,
.confirm-fade-leave-to {
  opacity: 0;
}
</style>
