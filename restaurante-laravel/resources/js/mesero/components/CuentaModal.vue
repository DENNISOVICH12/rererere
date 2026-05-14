<template>
  <Teleport to="body">
    <div v-if="open" class="overlay" @click.self="emit('close')">
      <article class="modal" role="dialog" aria-modal="true">

        <header class="modal-header">
          <div>
            <h3>💰 Cuenta</h3>
            <p class="cliente-nombre">{{ clienteNombre }}</p>
          </div>
          <button class="icon-close" @click="emit('close')">✕</button>
        </header>

        <section class="items-wrap">
          <ul v-if="items.length" class="items">
            <li v-for="item in items" :key="item.key" class="item-row">
              <div class="item-info">
                <strong>{{ item.nombre }}</strong>
                <p>x{{ item.cantidad }} · ${{ money(item.precioUnitario) }} c/u</p>
              </div>
              <span class="item-subtotal">${{ money(item.subtotal) }}</span>
            </li>
          </ul>
          <p v-else class="empty">No hay productos para mostrar.</p>
        </section>

        <footer class="modal-footer">
          <div class="total-box">
            <span class="total-label">Total</span>
            <strong class="total-value">${{ money(total) }}</strong>
          </div>

          <div class="actions">
            <!-- Comprobante: siempre visible una vez exista la URL -->
            <a
              v-if="comprobanteUrl"
              :href="comprobanteUrl"
              target="_blank"
              class="btn btn-comprobante"
            >
              🧾 Ver comprobante
            </a>

            <!-- Marcar como pagado -->
            <button
              v-if="!paid"
              class="btn btn-pay"
              :disabled="!canMarkPaid || paying"
              @click="handlePay"
            >
              <span v-if="paying" class="spinner" />
              {{ paying ? 'Procesando...' : 'Marcar como pagado' }}
            </button>

            <span v-if="paid" class="badge-pagado">✅ Pagado</span>

            <button class="btn btn-close" @click="emit('close')">Cerrar</button>
          </div>

          <p v-if="!paid && !canMarkPaid" class="warning">
            ⚠️ Solo puedes cobrar cuando todos los pedidos han sido entregados.
          </p>
        </footer>

      </article>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  open:           { type: Boolean, default: false },
  cliente:        { type: Object,  default: null },
  pedidos:        { type: Array,   default: () => [] },
  paid:           { type: Boolean, default: false },
  comprobanteUrl: { type: String,  default: null },
});

const emit = defineEmits(['close', 'mark-paid']);

const paying = ref(false);

const clienteNombre = computed(() =>
  props.cliente?.nombre || `Cliente #${props.cliente?.id || '-'}`
);

const items = computed(() =>
  props.pedidos.flatMap((pedido, pedidoIndex) => {
    const detalle = pedido?.detalle ?? pedido?.items ?? [];
    return detalle.map((item, itemIndex) => {
      const cantidad = Number(item?.cantidad ?? 1);
      const precioUnitario = Number(
        item?.precio_unitario ?? item?.importe ?? item?.precio ?? 0
      );
      return {
        key: item.id ?? `${pedido?.id || 'p'}-${pedidoIndex}-${itemIndex}`,
        nombre:
          item?.menu_item?.nombre ??
          item?.menuItem?.nombre ??
          item?.nombre ??
          'Ítem',
        cantidad,
        precioUnitario,
        subtotal: cantidad * precioUnitario,
      };
    });
  })
);

const total = computed(() =>
  items.value.reduce((sum, item) => sum + item.subtotal, 0)
);

const canMarkPaid = computed(() =>
  props.pedidos.length > 0 &&
  props.pedidos.every(
    (p) => String(p?.estado || '').toLowerCase() === 'entregado'
  )
);

const handlePay = async () => {
  if (paying.value) return;
  paying.value = true;
  try {
    emit('mark-paid');
  } finally {
    setTimeout(() => { paying.value = false; }, 3000);
  }
};

const money = (value) =>
  Number(value || 0).toLocaleString('es-CO', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
</script>

<style scoped>
.overlay {
  position: fixed; inset: 0;
  background: rgba(2, 6, 23, 0.78);
  backdrop-filter: blur(3px);
  display: grid; place-items: center;
  z-index: 90; padding: 16px;
}

.modal {
  width: min(580px, 100%);
  border-radius: 20px;
  border: 1px solid rgba(148, 163, 184, 0.22);
  background: linear-gradient(160deg, rgba(17, 24, 39, 0.98), rgba(10, 14, 26, 0.98));
  color: #e2e8f0;
  display: grid; gap: 0;
  overflow: hidden;
  box-shadow: 0 20px 50px rgba(2, 6, 23, 0.6);
}

.modal-header {
  display: flex; justify-content: space-between;
  gap: 12px; align-items: flex-start;
  padding: 20px 20px 16px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.12);
}
.modal-header h3 { margin: 0; color: #f8fafc; font-size: 1.1rem; }
.cliente-nombre { margin: 4px 0 0; color: #93c5fd; font-size: 0.9rem; }
.icon-close {
  border: 0; border-radius: 10px; background: #1e293b;
  color: #94a3b8; padding: 6px 10px; cursor: pointer; flex-shrink: 0;
}

.items-wrap {
  max-height: 44vh; overflow-y: auto;
  padding: 14px 20px;
}
.items { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
.item-row {
  display: flex; justify-content: space-between; align-items: flex-start;
  gap: 10px;
  border: 1px solid rgba(148, 163, 184, 0.14);
  border-radius: 12px; padding: 10px 12px;
  background: rgba(15, 23, 42, 0.55);
}
.item-info { flex: 1; }
.item-info strong { font-size: 14px; color: #f1f5f9; }
.item-info p { margin: 3px 0 0; color: #94a3b8; font-size: 0.82rem; }
.item-subtotal { font-weight: 700; color: #f1f5f9; font-size: 14px; white-space: nowrap; }
.empty { color: #64748b; font-size: 14px; }

.modal-footer {
  display: grid; gap: 12px;
  padding: 16px 20px 20px;
  border-top: 1px solid rgba(148, 163, 184, 0.12);
  background: rgba(10, 14, 26, 0.5);
}

.total-box {
  display: flex; justify-content: space-between; align-items: center;
}
.total-label { font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; }
.total-value { font-size: 1.7rem; font-weight: 800; color: #34d399; }

.actions {
  display: flex; justify-content: flex-end;
  gap: 8px; flex-wrap: wrap;
}

.btn {
  border: 0; border-radius: 11px; padding: 10px 16px;
  font-weight: 700; cursor: pointer; font-size: 13px;
  display: inline-flex; align-items: center; gap: 6px;
  transition: transform 130ms, filter 130ms, opacity 130ms;
}
.btn:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.07); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; filter: none; }

.btn-pay     { background: #10b981; color: #052e2b; }
.btn-comprobante { background: #1d4ed8; color: #eff6ff; text-decoration: none; }
.btn-close   { background: #1e293b; color: #94a3b8; }

.badge-pagado {
  display: inline-flex; align-items: center;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.4);
  color: #6ee7b7; border-radius: 999px;
  padding: 6px 14px; font-size: 13px; font-weight: 700;
}

.warning { margin: 0; color: #fbbf24; font-size: 0.85rem; }

.spinner {
  width: 13px; height: 13px; border-radius: 50%;
  border: 2px solid rgba(5, 46, 43, 0.3);
  border-top-color: #052e2b;
  animation: spin 600ms linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>