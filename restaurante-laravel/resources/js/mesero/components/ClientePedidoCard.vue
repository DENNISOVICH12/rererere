<template>
  <article class="cliente-card" :class="{ 'is-billed': isBilled }">

    <!-- ENCABEZADO: avatar + nombre + total acumulado -->
    <header class="cliente-header">
      <div class="cliente-identidad">
        <div class="avatar">{{ inicialCliente }}</div>
        <div>
          <h3>{{ cliente.nombre || `Cliente #${cliente.id}` }}</h3>
          <p class="meta">Mesa · {{ pedidos.length }} ronda(s)</p>
        </div>
      </div>

      <div class="total-box">
        <span class="total-amount">${{ money(totalCliente) }}</span>
        <span class="total-label">total acumulado</span>
      </div>
    </header>

    <!-- RONDAS -->
    <div class="rondas">
      <section
        v-for="(pedido, index) in pedidosOrdenados"
        :key="pedido.id"
        class="ronda"
        :class="`ronda--${estadoNormalizado(pedido.estado)}`"
      >
        <!-- Cabecera de ronda -->
        <div class="ronda-header">
          <div class="ronda-titulo">
            <span class="ronda-num">Ronda {{ pedidos.length - index }}</span>
            <span class="ronda-badge" :class="`badge--${estadoNormalizado(pedido.estado)}`">
              <i :class="badgeIcon(pedido.estado)" aria-hidden="true"></i>
              {{ badgeLabel(pedido.estado) }}
            </span>
          </div>
          <span class="ronda-tiempo">{{ tiempoRelativo(pedido.created_at) }}</span>
        </div>

        <!-- Ítems de la ronda -->
        <ul class="ronda-items">
          <li
            v-for="item in itemsNormalizados(pedido)"
            :key="item.key"
            class="ronda-item"
          >
            <div class="item-info">
              <i
                :class="item.grupo === 'bebida' ? 'ti ti-glass' : 'ti ti-tools-kitchen-2'"
                class="item-icon"
                aria-hidden="true"
              ></i>
              <span class="item-nombre">{{ item.nombre }}</span>
              <span class="item-qty">x{{ item.cantidad }}</span>
              <span v-if="item.nota" class="item-nota">{{ item.nota }}</span>
            </div>
            <span class="item-precio">${{ money(item.importe) }}</span>
          </li>
        </ul>

        <!-- Botón entregar (solo cuando listo) -->
        <div v-if="puedeEntregar(pedido)" class="ronda-entregar">
          <button
            v-if="tieneGrupo(pedido, 'plato')"
            class="btn-entregar btn-entregar--plato"
            :disabled="busy"
            @click="$emit('deliver-group', { cliente, order: pedido, group: 'plato' })"
          >
            <i class="ti ti-tools-kitchen-2" aria-hidden="true"></i>
            Entregar platos
          </button>
          <button
            v-if="tieneGrupo(pedido, 'bebida')"
            class="btn-entregar btn-entregar--bebida"
            :disabled="busy"
            @click="$emit('deliver-group', { cliente, order: pedido, group: 'bebida' })"
          >
            <i class="ti ti-glass" aria-hidden="true"></i>
            Entregar bebidas
          </button>
        </div>
      </section>
    </div>

    <!-- EDITOR DE PEDIDO (cuando está en modo edición) -->
    <section v-if="editing" class="editor">
      <h4 class="editor-titulo">
        <i class="ti ti-pencil" aria-hidden="true"></i>
        Editar ronda activa
      </h4>

      <div class="editor-items">
        <div v-for="(item, index) in draftItems" :key="item.key" class="editor-item">
          <select v-model.number="item.menu_item_id" class="editor-select">
            <option v-for="opt in menuOptions" :key="opt.id" :value="opt.id">{{ opt.nombre }}</option>
          </select>
          <input v-model.number="item.cantidad" type="number" min="1" class="editor-qty" />
          <input v-model="item.nota" type="text" placeholder="Nota" class="editor-nota" />
          <button class="btn-small btn-danger" @click="removeItem(index)">
            <i class="ti ti-trash" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <div class="editor-add">
        <select v-model.number="newItemId" class="editor-select">
          <option :value="null">Agregar producto...</option>
          <option v-for="opt in menuOptions" :key="opt.id" :value="opt.id">{{ opt.nombre }}</option>
        </select>
        <button class="btn-small" @click="appendItem">
          <i class="ti ti-plus" aria-hidden="true"></i>
          Agregar
        </button>
      </div>

      <div class="editor-actions">
        <!-- Campo de justificación obligatorio cuando el pedido ya salió de la ventana de retención -->
        <div v-if="needsJustification" class="justif-wrap">
          <label class="justif-label">
            <i class="ti ti-notes" aria-hidden="true"></i>
            Justificación del cambio <span class="justif-required">*requerida</span>
          </label>
          <textarea
            v-model="justificacion"
            class="justif-input"
            rows="2"
            placeholder="Ej: El cliente solicitó eliminar el plato porque cambió de opinión..."
            maxlength="500"
          ></textarea>
        </div>

        <button class="btn btn-primary" :disabled="busy || !draftItems.length" @click="saveDraft">
          <i class="ti ti-device-floppy" aria-hidden="true"></i>
          Guardar cambios
        </button>
        <button class="btn btn-ghost" :disabled="busy" @click="$emit('cancel-edit')">
          Cancelar
        </button>
      </div>
    </section>

    <!-- PIE: acciones principales -->
    <footer class="cliente-footer">
      <div class="footer-left">
        <button
          v-if="canEdit && !editing"
          class="btn btn-secondary"
          :disabled="isActionDisabled"
          @click="$emit('edit', cliente)"
        >
          <i class="ti ti-pencil" aria-hidden="true"></i>
          Editar ronda
        </button>
        <button
          v-if="canSendToKitchen && !editing"
          class="btn btn-secondary"
          :disabled="isActionDisabled"
          @click="$emit('send-to-kitchen', cliente)"
        >
          <i class="ti ti-flame" aria-hidden="true"></i>
          Enviar a cocina
        </button>
        <span v-if="isBilled" class="estado-pagado">
          <i class="ti ti-check" aria-hidden="true"></i>
          Pagado
        </span>
        <span v-else-if="!canEdit && !canSendToKitchen && !isBilled" class="estado-info">
          Pedido en cocina
        </span>
      </div>

      <div class="footer-right">
        <button
          class="btn btn-comprobante"
          :disabled="busy"
          @click="$emit('ver-comprobante', cliente)"
        >
          <i class="ti ti-receipt" aria-hidden="true"></i>
          Ver comprobante
        </button>

        <button
          v-if="!isBilled"
          class="btn btn-pagar"
          :disabled="hayRondasSinEntregar || busy"
          @click="$emit('marcar-pagado', cliente)"
        >
          <i class="ti ti-currency-dollar" aria-hidden="true"></i>
          Marcar como pagado
        </button>

        <span v-if="isBilled" class="estado-pagado">
          <i class="ti ti-check" aria-hidden="true"></i>
          Pagado
        </span>
      </div>
    </footer>

    <!-- Aviso cuando hay rondas sin entregar -->
    <div v-if="hayRondasSinEntregar && !isBilled" class="aviso-pendiente">
      <i class="ti ti-info-circle" aria-hidden="true"></i>
      Cobrar se habilita cuando todas las rondas están entregadas
    </div>

  </article>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { normalizeGroupKey, normalizeStatus } from '../utils/serviceStatus';

const props = defineProps({
  cliente: { type: Object, required: true },
  pedidos: { type: Array, default: () => [] },
  elapsedText: { type: String, required: true },
  timerTone: { type: String, default: 'ok' },
  busy: { type: Boolean, default: false },
  canEdit: { type: Boolean, default: false },
  needsJustification: { type: Boolean, default: false },
  canSendToKitchen: { type: Boolean, default: false },
  editing: { type: Boolean, default: false },
  draftItems: { type: Array, default: () => [] },
  menuOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['deliver-group', 'ver-comprobante', 'marcar-pagado', 'edit', 'save-edit', 'cancel-edit', 'send-to-kitchen']);

const newItemId = ref(null);
const justificacion = ref('');

watch(() => props.editing, (val) => {
  if (!val) {
    newItemId.value = null;
    justificacion.value = '';
  }
});

// ── Computed ────────────────────────────────────────────────

const inicialCliente = computed(() =>
  (props.cliente?.nombre || 'C').trim().charAt(0).toUpperCase()
);

// Pedidos del más reciente al más antiguo
const pedidosOrdenados = computed(() =>
  [...props.pedidos].sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0))
);

const isBilled = computed(() => {
  const billingFlags = ['facturado', 'is_billed', 'billed', 'factura_id', 'facturado_at'];
  const statusFlags = ['facturado', 'cerrado', 'closed', 'paid'];
  return props.pedidos.some((p) => {
    const hasFlag = billingFlags.some((f) => Boolean(p?.[f]));
    const estado = String(p?.estado || p?.status || '').toLowerCase();
    return hasFlag || statusFlags.includes(estado);
  });
});

const isActionDisabled = computed(() => props.busy || isBilled.value);

const hayRondasSinEntregar = computed(() =>
  props.pedidos.some((p) => {
    const e = normalizeStatus(p?.estado);
    return e !== 'entregado' && e !== 'facturado' && e !== 'cancelado';
  })
);

// Total acumulado sumando todos los pedidos
const totalCliente = computed(() =>
  props.pedidos.reduce((acc, p) => {
    const items = p.detalle ?? p.items ?? [];
    return acc + items.reduce((s, item) => {
      const importe = Number(item?.importe ?? item?.precio_unitario ?? item?.precio ?? 0);
      const cantidad = Number(item?.cantidad ?? 1);
      return s + (item?.importe !== undefined ? importe : importe * cantidad);
    }, 0);
  }, 0)
);

// ── Helpers ────────────────────────────────────────────────

const itemsNormalizados = (pedido) => {
  const details = pedido.detalle ?? pedido.items ?? [];
  return details.map((item, i) => ({
    key: item.id ?? `${pedido.id}-${i}`,
    nombre: item?.menu_item?.nombre ?? item?.menuItem?.nombre ?? item?.nombre ?? 'Ítem',
    cantidad: Number(item?.cantidad ?? 1),
    importe: Number(item?.importe ?? item?.precio_unitario ?? item?.precio ?? 0),
    grupo: normalizeGroupKey(item?.grupo_servicio ?? item?.categoria),
    nota: item?.nota ?? '',
  }));
};

const estadoNormalizado = (estado) => normalizeStatus(estado);

const badgeLabel = (estado) => ({
  retenido: 'Retenido',
  modificacion_solicitada: 'En revisión',
  pendiente: 'En cocina',
  preparando: 'En cocina',
  listo: 'Listo para entregar',
  entregado: 'Entregado',
  facturado: 'Facturado',
})[String(estado || '').toLowerCase()] ?? 'En cocina';

const badgeIcon = (estado) => ({
  retenido: 'ti ti-clock',
  modificacion_solicitada: 'ti ti-refresh',
  pendiente: 'ti ti-flame',
  preparando: 'ti ti-flame',
  listo: 'ti ti-bell',
  entregado: 'ti ti-check',
  facturado: 'ti ti-check',
})[String(estado || '').toLowerCase()] ?? 'ti ti-flame';

const puedeEntregar = (pedido) => {
  const items = pedido.detalle ?? pedido.items ?? [];
  return items.some((item) => normalizeStatus(item?.estado_servicio) === 'listo');
};

const tieneGrupo = (pedido, grupo) => {
  const items = pedido.detalle ?? pedido.items ?? [];
  return items.some(
    (item) =>
      normalizeGroupKey(item?.grupo_servicio ?? item?.categoria) === grupo &&
      normalizeStatus(item?.estado_servicio) === 'listo'
  );
};

const tiempoRelativo = (fechaStr) => {
  if (!fechaStr) return '';
  const diff = Math.floor((Date.now() - new Date(fechaStr).getTime()) / 60000);
  if (diff < 1) return 'hace un momento';
  if (diff === 1) return 'hace 1 min';
  if (diff < 60) return `hace ${diff} min`;
  const h = Math.floor(diff / 60);
  return `hace ${h}h`;
};

const money = (value) => Number(value || 0).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

// ── Acciones editor ────────────────────────────────────────

const appendItem = () => {
  if (!newItemId.value) return;
  emit('save-edit', {
    mode: 'append',
    item: { key: `new-${Date.now()}`, menu_item_id: newItemId.value, cantidad: 1, nota: '' },
  });
};

const removeItem = (index) => emit('save-edit', { mode: 'remove', index });

const saveDraft = () => {
  if (props.needsJustification && !justificacion.value.trim()) {
    alert('Debes ingresar una justificación para modificar este pedido.');
    return;
  }
  emit('save-edit', { mode: 'commit', justificacion: justificacion.value.trim() || null });
};
</script>

<style scoped>
.cliente-card {
  font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 18px;
  overflow: hidden;
  background: linear-gradient(158deg, rgba(17, 27, 47, 0.94), rgba(10, 18, 36, 0.9));
  box-shadow: 0 10px 28px rgba(3, 8, 20, 0.32);
  transition: opacity 200ms ease;
}
.cliente-card.is-billed { opacity: 0.55; }

/* ── Header ── */
.cliente-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 18px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.12);
}
.cliente-identidad { display: flex; align-items: center; gap: 12px; }
.avatar {
  width: 42px; height: 42px;
  border-radius: 50%;
  background: rgba(37, 99, 235, 0.25);
  color: #93c5fd;
  display: grid; place-items: center;
  font-size: 16px; font-weight: 500;
  flex-shrink: 0;
}
h3 { margin: 0; font-size: 1rem; font-weight: 600; color: #f0f6ff; }
.meta { margin: 2px 0 0; font-size: 12px; color: #8fa4c4; }
.total-box { text-align: right; }
.total-amount { display: block; font-size: 1.3rem; font-weight: 600; color: #f0f6ff; }
.total-label { font-size: 11px; color: #6b83a8; }

/* ── Rondas ── */
.rondas { display: flex; flex-direction: column; }

.ronda {
  padding: 14px 18px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
  display: grid; gap: 10px;
}

.ronda-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.ronda-titulo { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.ronda-num { font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.ronda-tiempo { font-size: 11px; color: #586a88; }

/* Badges de estado */
.ronda-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 600;
  padding: 3px 9px; border-radius: 999px;
}
.badge--retenido      { background: rgba(59, 130, 246, 0.15); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); }
.badge--modificacion_solicitada { background: rgba(234, 179, 8, 0.15); color: #fde047; border: 1px solid rgba(234, 179, 8, 0.3); }
.badge--pendiente, .badge--preparando { background: rgba(249, 115, 22, 0.14); color: #fdba74; border: 1px solid rgba(249, 115, 22, 0.3); }
.badge--listo         { background: rgba(239, 68, 68, 0.14); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
.badge--entregado     { background: rgba(16, 185, 129, 0.14); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }
.badge--facturado     { background: rgba(16, 185, 129, 0.14); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }

/* Ítems de la ronda */
.ronda-items { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
.ronda-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 8px 10px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 10px;
  gap: 10px;
}
.item-info { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
.item-icon { font-size: 14px; color: #6b83a8; }
.item-nombre { font-size: 13px; color: #dce7ff; }
.item-qty { font-size: 12px; color: #6b83a8; }
.item-nota { font-size: 11px; color: #d97706; font-style: italic; }
.item-precio { font-size: 13px; font-weight: 600; color: #c8d9f4; white-space: nowrap; }

/* Botones entregar */
.ronda-entregar { display: flex; gap: 8px; flex-wrap: wrap; }
.btn-entregar {
  display: inline-flex; align-items: center; gap: 6px;
  border: 0; border-radius: 10px;
  padding: 8px 14px; font-size: 12px; font-weight: 700;
  color: #fff; cursor: pointer;
  transition: transform 150ms, filter 150ms, opacity 150ms;
}
.btn-entregar:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.08); }
.btn-entregar:disabled { opacity: 0.45; cursor: not-allowed; transform: none; filter: none; }
.btn-entregar--plato { background: #c2410c; }
.btn-entregar--bebida { background: #0369a1; }

/* ── Editor ── */
.editor {
  padding: 14px 18px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
  display: grid; gap: 10px;
  background: rgba(10, 18, 36, 0.5);
}
.editor-titulo {
  margin: 0; font-size: 13px; font-weight: 600; color: #b8c8e8;
  display: flex; align-items: center; gap: 6px;
}
.editor-items { display: grid; gap: 7px; }
.editor-item { display: grid; grid-template-columns: 1.4fr 0.4fr 1fr auto; gap: 6px; align-items: center; }
.editor-add { display: grid; grid-template-columns: 1fr auto; gap: 6px; }
.editor-actions { display: flex; gap: 8px; }
.editor-select, .editor-qty, .editor-nota {
  background: rgba(15, 23, 42, 0.84);
  border: 1px solid rgba(148, 163, 184, 0.3);
  border-radius: 9px; color: #dbeafe; padding: 8px 10px; font-size: 13px;
}

/* ── Justificación ── */
.justif-wrap {
  width: 100%;
  display: grid; gap: 6px;
  padding: 10px 12px;
  background: rgba(251, 191, 36, 0.06);
  border: 1px solid rgba(251, 191, 36, 0.25);
  border-radius: 10px;
}
.justif-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 600; color: #fbbf24;
}
.justif-required { font-weight: 400; font-size: 11px; color: #f87171; }
.justif-input {
  width: 100%; background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(251, 191, 36, 0.3);
  border-radius: 8px; color: #fef3c7;
  padding: 8px 10px; font-size: 13px; resize: vertical;
}
.justif-input:focus { outline: none; border-color: rgba(251, 191, 36, 0.6); }

/* ── Footer ── */
.cliente-footer {
  display: flex; justify-content: space-between; align-items: center;
  gap: 8px; flex-wrap: wrap;
  padding: 14px 18px;
}
.footer-left { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.btn {
  display: inline-flex; align-items: center; gap: 6px;
  border: 0; border-radius: 11px;
  padding: 9px 14px; font-size: 13px; font-weight: 600; color: #f0f6ff;
  cursor: pointer;
  transition: transform 140ms, filter 140ms, opacity 140ms;
}
.btn:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.06); }
.btn:disabled { opacity: 0.46; cursor: not-allowed; transform: none; filter: none; }
.btn-primary { background: #2563eb; }
.btn-secondary { background: #1e3a5f; border: 1px solid rgba(96, 165, 250, 0.3); }
.btn-ghost { background: transparent; border: 1px solid rgba(148, 163, 184, 0.35); color: #94a3b8; }
.btn-comprobante { background: #1d4ed8; }
.btn-pagar { background: #059669; color: #f0fdf4; }
.btn-pagar:disabled { opacity: 0.4; cursor: not-allowed; }
.footer-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.btn-small {
  display: inline-flex; align-items: center; gap: 5px;
  border: 0; border-radius: 9px; padding: 8px 11px;
  font-size: 12px; font-weight: 600; color: #f0f6ff; cursor: pointer;
  background: #1e3a5f;
  transition: filter 140ms;
}
.btn-small:hover { filter: brightness(1.1); }
.btn-danger { background: #7f1d1d; }

.estado-pagado {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 600; color: #6ee7b7;
  padding: 6px 10px;
  background: rgba(16, 185, 129, 0.14);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 999px;
}
.estado-info {
  font-size: 12px; color: #fca5a5;
}

/* ── Aviso cobrar ── */
.aviso-pendiente {
  display: flex; align-items: center; gap: 6px;
  padding: 10px 18px;
  background: rgba(15, 23, 42, 0.6);
  border-top: 1px solid rgba(148, 163, 184, 0.1);
  font-size: 12px; color: #6b83a8;
}

/* ── Responsive ── */
@media (max-width: 560px) {
  .cliente-header { flex-direction: column; align-items: flex-start; }
  .total-box { text-align: left; }
  .editor-item { grid-template-columns: 1fr 1fr; }
  .cliente-footer { flex-direction: column; align-items: stretch; }
  .footer-left { flex-direction: column; }
  .btn-cuenta { width: 100%; justify-content: center; }
}
</style>