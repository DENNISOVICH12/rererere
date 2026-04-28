<template>
  <transition name="qty-fade">
    <div
      v-if="visible"
      class="qty-overlay"
      role="dialog"
      aria-modal="true"
      :aria-label="`Confirmar cantidad para ${productName}`"
      @click.self="cancel"
    >
      <div class="qty-panel">
        <h3 class="qty-title">Confirmar cantidad</h3>
        <p class="qty-message">Vas a agregar {{ quantity }} unidades de <strong>{{ productName }}</strong>.</p>

        <div class="qty-stepper" aria-label="Seleccionar cantidad">
          <button class="qty-btn" type="button" @click="decrease" :disabled="quantity <= 1">−</button>
          <span class="qty-value">{{ quantity }}</span>
          <button class="qty-btn" type="button" @click="increase">+</button>
        </div>

        <div class="qty-actions">
          <button class="btn-ghost" type="button" @click="cancel">Cancelar</button>
          <button class="btn-confirm" type="button" @click="confirm">Confirmar</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref } from 'vue'

const visible = ref(false)
const productName = ref('producto')
const quantity = ref(1)
let resolver = null

function open(itemName, initialQuantity = 1) {
  productName.value = itemName || 'producto'
  quantity.value = Math.max(1, Number(initialQuantity) || 1)
  visible.value = true

  return new Promise((resolve) => {
    resolver = resolve
  })
}

function close(result) {
  const currentResolver = resolver
  resolver = null
  visible.value = false
  currentResolver?.(result)
}

function increase() {
  quantity.value += 1
}

function decrease() {
  quantity.value = Math.max(1, quantity.value - 1)
}

function confirm() {
  close({ confirmed: true, quantity: quantity.value })
}

function cancel() {
  close({ confirmed: false, quantity: quantity.value })
}

defineExpose({ open })
</script>

<style scoped>
.qty-overlay {
  position: fixed;
  inset: 0;
  z-index: 5100;
  background: rgba(11, 16, 24, 0.72);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.qty-panel {
  width: min(390px, 94vw);
  border-radius: 18px;
  border: 1px solid rgba(244, 246, 248, 0.18);
  background: rgba(24, 34, 49, 0.96);
  box-shadow: 0 18px 34px rgba(11, 16, 24, 0.52);
  padding: 20px;
  color: #f4f6f8;
}

.qty-title {
  margin: 0 0 8px;
  font-size: 1.15rem;
}

.qty-message {
  margin: 0;
  color: rgba(244, 246, 248, 0.82);
  line-height: 1.45;
}

.qty-stepper {
  margin-top: 16px;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  border-radius: 999px;
  border: 1px solid rgba(244, 246, 248, 0.16);
  background: rgba(11, 16, 24, 0.6);
  padding: 6px;
}

.qty-btn {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: none;
  background: #c23a4a;
  color: #f4f6f8;
  font-size: 1.12rem;
  font-weight: 700;
  cursor: pointer;
}

.qty-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.qty-value {
  min-width: 22px;
  text-align: center;
  font-size: 1.02rem;
  font-weight: 700;
}

.qty-actions {
  margin-top: 18px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-ghost,
.btn-confirm {
  border: none;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 700;
  cursor: pointer;
}

.btn-ghost {
  border: 1px solid rgba(244, 246, 248, 0.2);
  background: rgba(24, 34, 49, 0.72);
  color: #f4f6f8;
}

.btn-confirm {
  background: #c23a4a;
  color: #f4f6f8;
}

.qty-fade-enter-active,
.qty-fade-leave-active {
  transition: opacity 0.2s ease;
}

.qty-fade-enter-from,
.qty-fade-leave-to {
  opacity: 0;
}
</style>
