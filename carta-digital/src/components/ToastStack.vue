<template>
  <div class="toast-stack" aria-live="polite" aria-atomic="true">
    <transition-group name="toast-slide" tag="div">
      <article
        v-for="toast in toasts"
        :key="toast.id"
        class="toast-item"
        :class="toast.type"
      >
        {{ toast.message }}
      </article>
    </transition-group>
  </div>
</template>

<script setup>
defineProps({
  toasts: {
    type: Array,
    default: () => []
  }
})
</script>

<style scoped>
.toast-stack {
  position: fixed;
  top: 16px;
  right: 16px;
  z-index: 6200;
  pointer-events: none;
}

.toast-item {
  min-width: 220px;
  max-width: min(360px, calc(100vw - 32px));
  margin-bottom: 10px;
  padding: 10px 14px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.22);
  background: rgba(15, 23, 36, 0.88);
  backdrop-filter: blur(10px);
  box-shadow: 0 12px 24px rgba(5, 10, 18, 0.3);
  color: #f4f6f8;
  font-size: 0.9rem;
  font-weight: 600;
}

.toast-item.success {
  border-color: rgba(124, 252, 173, 0.4);
}

.toast-item.info {
  border-color: rgba(194, 58, 74, 0.36);
}

.toast-slide-enter-active,
.toast-slide-leave-active {
  transition: all 0.24s ease;
}

.toast-slide-enter-from,
.toast-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px) translateX(8px);
}

@media (max-width: 640px) {
  .toast-stack {
    right: 10px;
    top: 10px;
  }
}
</style>
