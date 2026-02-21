<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
  text: {
    type: String,
    default: '',
  },
  context: {
    type: String,
    default: '',
  },
})

const isOpen = ref(false)
const bodyOverflowBeforeLock = ref('')
const modalTitleId = `note-dialog-title-${Math.random().toString(36).slice(2, 11)}`

const normalizedText = computed(() => String(props.text || '').trim())
const hasText = computed(() => normalizedText.value.length > 0)

function openDialog() {
  if (!hasText.value) return
  isOpen.value = true
}

function closeDialog() {
  isOpen.value = false
}

function onBackdropClick(event) {
  if (event.target !== event.currentTarget) return
  closeDialog()
}

function onKeydown(event) {
  if (!isOpen.value) return
  if (event.key === 'Escape') closeDialog()
}

watch(
  isOpen,
  (nextOpen) => {
    if (typeof document === 'undefined') return

    if (nextOpen) {
      bodyOverflowBeforeLock.value = document.body.style.overflow
      document.body.style.overflow = 'hidden'
      window.addEventListener('keydown', onKeydown)
      return
    }

    document.body.style.overflow = bodyOverflowBeforeLock.value
    window.removeEventListener('keydown', onKeydown)
  },
  { immediate: false },
)

onUnmounted(() => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = bodyOverflowBeforeLock.value
    window.removeEventListener('keydown', onKeydown)
  }
})
</script>

<template>
  <template v-if="hasText">
    <button
      type="button"
      class="note-preview"
      :title="normalizedText"
      aria-haspopup="dialog"
      @click="openDialog"
    >
      <span class="note-preview__icon" aria-hidden="true">📝</span>
      <span class="note-preview__text">{{ normalizedText }}</span>
    </button>

    <Teleport to="body">
      <Transition name="note-modal">
        <div
          v-if="isOpen"
          class="note-modal-backdrop"
          @click="onBackdropClick"
        >
          <section
            class="note-modal"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="modalTitleId"
          >
            <header class="note-modal__header">
              <h3 :id="modalTitleId">Nota del cliente</h3>
              <button type="button" class="note-modal__close" aria-label="Cerrar" @click="closeDialog">✕</button>
            </header>

            <p v-if="context" class="note-modal__context">{{ context }}</p>

            <div class="note-modal__content">
              <p>{{ normalizedText }}</p>
            </div>

            <footer class="note-modal__footer">
              <button type="button" class="note-modal__btn" @click="closeDialog">Cerrar</button>
            </footer>
          </section>
        </div>
      </Transition>
    </Teleport>
  </template>
</template>

<style scoped>
.note-preview {
  width: 100%;
  margin: 0;
  display: inline-flex;
  align-items: flex-start;
  gap: 8px;
  text-align: left;
  background: rgba(250, 204, 21, 0.1);
  border: 1px solid rgba(250, 204, 21, 0.25);
  color: #fde68a;
  border-radius: 10px;
  padding: 8px 10px;
  font-size: 0.86rem;
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

.note-preview:hover,
.note-preview:focus-visible {
  border-color: rgba(253, 230, 138, 0.75);
  background: rgba(250, 204, 21, 0.16);
  box-shadow: 0 0 0 2px rgba(250, 204, 21, 0.16);
  outline: none;
}

.note-preview__icon {
  flex-shrink: 0;
  margin-top: 2px;
}

.note-preview__text {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.35;
}

.note-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.72);
  backdrop-filter: blur(1px);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1200;
  padding: 12px;
}

.note-modal {
  width: min(560px, 100%);
  background: #0f172a;
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 16px;
  box-shadow: 0 18px 44px rgba(2, 6, 23, 0.5);
  padding: 14px;
  max-height: min(76vh, 680px);
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.note-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.note-modal__header h3 {
  margin: 0;
  color: #e2e8f0;
  font-size: 1rem;
}

.note-modal__close {
  border: 1px solid rgba(148, 163, 184, 0.25);
  background: rgba(15, 23, 42, 0.9);
  color: #cbd5e1;
  border-radius: 10px;
  width: 34px;
  height: 34px;
  cursor: pointer;
}

.note-modal__context {
  margin: 0;
  color: #94a3b8;
  font-size: 0.9rem;
}

.note-modal__content {
  overflow-y: auto;
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 12px;
  background: rgba(15, 23, 42, 0.65);
  padding: 12px;
}

.note-modal__content p {
  margin: 0;
  color: #f8fafc;
  line-height: 1.6;
  white-space: pre-wrap;
}

.note-modal__footer {
  display: flex;
  justify-content: flex-end;
}

.note-modal__btn {
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 10px;
  background: rgba(30, 41, 59, 0.85);
  color: #f8fafc;
  padding: 8px 14px;
  cursor: pointer;
}

.note-modal-enter-active,
.note-modal-leave-active {
  transition: opacity 0.2s ease;
}

.note-modal-enter-active .note-modal,
.note-modal-leave-active .note-modal {
  transition: transform 0.22s ease, opacity 0.22s ease;
}

.note-modal-enter-from,
.note-modal-leave-to {
  opacity: 0;
}

.note-modal-enter-from .note-modal,
.note-modal-leave-to .note-modal {
  transform: translateY(14px) scale(0.98);
  opacity: 0;
}

@media (min-width: 768px) {
  .note-modal-backdrop {
    align-items: center;
    padding: 20px;
  }
}
</style>
