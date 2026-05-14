<template>
  <Teleport to="body">
    <div v-if="open" class="overlay" @click.self="emit('cancelar')">
      <article class="modal" role="dialog" aria-modal="true">

        <!-- Mesa ya asignada a otro mesero -->
        <template v-if="yaAsignada">
          <div class="modal-icon modal-icon--warn">⚠️</div>
          <h3>Mesa en atención</h3>
          <p class="modal-desc">
            La <strong>Mesa {{ mesaNumero }}</strong> ya está siendo atendida por
            <strong>{{ meseroActual }}</strong>.
          </p>
          <p class="modal-sub">¿Deseas tomar el relevo de esta mesa?</p>

          <div class="modal-actions">
            <button class="btn btn-danger" :disabled="loading" @click="emit('tomar-relevo')">
              <span v-if="loading" class="spinner" />
              {{ loading ? 'Asignando...' : 'Tomar relevo' }}
            </button>
            <button class="btn btn-ghost" :disabled="loading" @click="emit('cancelar')">
              Cancelar
            </button>
          </div>
        </template>

        <!-- Mesa sin asignar -->
        <template v-else>
          <div class="modal-icon modal-icon--info">🍽️</div>
          <h3>¿Atender esta mesa?</h3>
          <p class="modal-desc">
            Vas a quedar a cargo de la <strong>Mesa {{ mesaNumero }}</strong>.
            Los demás meseros verán que esta mesa está siendo atendida por ti.
          </p>

          <div class="modal-actions">
            <button class="btn btn-primary" :disabled="loading" @click="emit('confirmar')">
              <span v-if="loading" class="spinner" />
              {{ loading ? 'Asignando...' : 'Sí, atender mesa' }}
            </button>
            <button class="btn btn-ghost" :disabled="loading" @click="emit('cancelar')">
              Cancelar
            </button>
          </div>
        </template>

      </article>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
  open:        { type: Boolean, default: false },
  mesaNumero:  { type: [String, Number], default: '' },
  yaAsignada:  { type: Boolean, default: false },
  meseroActual:{ type: String, default: '' },
  loading:     { type: Boolean, default: false },
});

const emit = defineEmits(['confirmar', 'cancelar', 'tomar-relevo']);
</script>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.78);
  backdrop-filter: blur(3px);
  display: grid;
  place-items: center;
  z-index: 100;
  padding: 20px;
}

.modal {
  width: min(400px, 100%);
  background: linear-gradient(160deg, rgba(17, 24, 39, 0.99), rgba(10, 14, 26, 0.99));
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 20px;
  padding: 28px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  box-shadow: 0 20px 40px rgba(2, 6, 23, 0.5);
  text-align: center;
}

.modal-icon {
  font-size: 2.8rem;
  line-height: 1;
  margin-bottom: 4px;
}

.modal-icon--info { filter: drop-shadow(0 0 10px rgba(59,130,246,0.4)); }
.modal-icon--warn { filter: drop-shadow(0 0 10px rgba(234,179,8,0.4)); }

h3 {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: #f0f6ff;
}

.modal-desc {
  margin: 0;
  font-size: 0.93rem;
  color: #94a3b8;
  line-height: 1.6;
}

.modal-desc strong { color: #dbeafe; }

.modal-sub {
  margin: 0;
  font-size: 0.88rem;
  color: #f59e0b;
  font-weight: 600;
}

.modal-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  margin-top: 8px;
}

.btn {
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 0;
  border-radius: 12px;
  padding: 12px 18px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: transform 150ms ease, filter 150ms ease, opacity 150ms ease;
}
.btn:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.07); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; filter: none; }

.btn-primary { background: #2563eb; color: #eff6ff; }
.btn-danger  { background: #dc2626; color: #fff; }
.btn-ghost   { background: rgba(148, 163, 184, 0.12); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.25); }

.spinner {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  animation: spin 600ms linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>    