<template>
  <Teleport to="body">
    <div v-if="open" class="overlay" @click.self="emit('close')">
      <article class="modal card-shell" role="dialog" aria-modal="true" aria-label="Resumen de cuenta">
        <header class="modal-header">
          <div>
            <h3>💰 Resumen de cuenta</h3>
            <p>{{ clienteNombre }}</p>
          </div>
          <button class="icon-close" @click="emit('close')">✕</button>
        </header>

        <section class="items-wrap">
          <ul v-if="items.length" class="items">
            <li v-for="item in items" :key="item.key" class="item-row">
              <div>
                <strong>{{ item.nombre }}</strong>
                <p>x{{ item.cantidad }} · ${{ money(item.precioUnitario) }} c/u</p>
              </div>
              <span>${{ money(item.subtotal) }}</span>
            </li>
          </ul>
          <p v-else class="empty">No hay productos para mostrar.</p>
        </section>

        <footer class="modal-footer">
          <div class="total-box">
            <span>Total</span>
            <strong>${{ money(total) }}</strong>
          </div>

          <div class="actions">
            <button class="btn btn-pay" :disabled="paid" @click="emit('mark-paid')">
              {{ paid ? '✅ Pagado' : 'Marcar como pagado' }}
            </button>
            <button class="btn btn-close" @click="emit('close')">Cerrar</button>
          </div>
        </footer>
      </article>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  cliente: { type: Object, default: null },
  pedidos: { type: Array, default: () => [] },
  paid: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'mark-paid']);

const clienteNombre = computed(() => props.cliente?.nombre || `Cliente #${props.cliente?.id || '-'}`);

const items = computed(() =>
  props.pedidos.flatMap((pedido, pedidoIndex) => {
    const detalle = pedido?.detalle ?? pedido?.items ?? [];

    return detalle.map((item, itemIndex) => {
      const cantidad = Number(item?.cantidad ?? 1);
      const precioUnitario = Number(item?.precio_unitario ?? item?.importe ?? item?.precio ?? 0);

      return {
        key: item.id ?? `${pedido?.id || 'pedido'}-${pedidoIndex}-${itemIndex}`,
        nombre: item?.menu_item?.nombre ?? item?.menuItem?.nombre ?? item?.nombre ?? 'Ítem',
        cantidad,
        precioUnitario,
        subtotal: cantidad * precioUnitario,
      };
    });
  }),
);

const total = computed(() => items.value.reduce((sum, item) => sum + item.subtotal, 0));

const money = (value) => Number(value || 0).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
</script>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.75);
  backdrop-filter: blur(2px);
  display: grid;
  place-items: center;
  z-index: 90;
  padding: 16px;
}
.modal {
  width: min(620px, 100%);
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.28);
  background: linear-gradient(160deg, rgba(17, 24, 39, 0.98), rgba(10, 14, 26, 0.98));
  color: #e2e8f0;
  display: grid;
  gap: 14px;
}
.card-shell { padding: 18px; box-shadow: 0 14px 30px rgba(2, 6, 23, 0.45); }
.modal-header { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
.modal-header h3 { margin: 0; color: #f8fafc; }
.modal-header p { margin: 4px 0 0; color: #93c5fd; }
.icon-close { border: 0; border-radius: 10px; background: #334155; color: #fff; padding: 6px 10px; cursor: pointer; }
.items-wrap { max-height: 46vh; overflow: auto; }
.items { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
.item-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 12px;
  padding: 10px;
  background: rgba(15, 23, 42, 0.62);
}
.item-row p { margin: 4px 0 0; color: #cbd5e1; font-size: 0.88rem; }
.item-row span { font-weight: 700; color: #f1f5f9; }
.empty { color: #94a3b8; }
.modal-footer { display: grid; gap: 12px; }
.total-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px dashed rgba(148, 163, 184, 0.4);
  padding-top: 12px;
}
.total-box strong { font-size: 1.5rem; color: #34d399; }
.actions { display: flex; justify-content: flex-end; gap: 8px; }
.btn { border: 0; border-radius: 11px; padding: 10px 14px; font-weight: 700; cursor: pointer; }
.btn-pay { background: #10b981; color: #052e2b; }
.btn-close { background: #334155; color: #f8fafc; }
.btn:disabled { opacity: 0.7; cursor: not-allowed; }
</style>
