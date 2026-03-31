@extends('layouts.admin')

@section('content')
<div class="mesas-admin">
    <div class="header">
        <div>
            <h1>🪑 Gestión de Mesas</h1>
            <p>Administra las mesas disponibles en el restaurante.</p>
        </div>

        <button class="btn btn-primary" id="openCreateMesa">Agregar Mesa</button>
    </div>

    <div class="card table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Número de mesa</th>
                    <th>Estado</th>
                    <th style="width: 140px;">Acciones</th>
                </tr>
            </thead>
            <tbody id="mesasTableBody">
                <tr>
                    <td colspan="4" class="table-empty">Cargando mesas...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="mesaModal" hidden>
    <div class="modal-card">
        <h3>Agregar mesa</h3>
        <p class="modal-subtitle">Si no ingresas un número, se genera automáticamente.</p>

        <form id="createMesaForm">
            <label for="mesaNumero">Número de mesa (opcional)</label>
            <input type="number" min="1" id="mesaNumero" name="numero" placeholder="Ej: 12" />

            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" id="cancelMesaModal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear mesa</button>
            </div>
        </form>
    </div>
</div>

<style>
    .mesas-admin .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }

    .mesas-admin .header p {
        margin: 6px 0 0;
        color: #c8c8c8;
    }

    .table-card {
        overflow: hidden;
    }

    .table-empty {
        text-align: center;
        color: #b3b3b3;
        padding: 20px;
    }

    .badge {
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .badge-libre {
        background: rgba(52, 211, 153, 0.15);
        color: #6ee7b7;
    }

    .badge-ocupada {
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
    }

    .btn {
        border: none;
        border-radius: 10px;
        padding: 10px 14px;
        cursor: pointer;
        color: #fff;
        transition: transform .15s ease, opacity .15s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        opacity: .92;
    }

    .btn-primary {
        background: #2563eb;
    }

    .btn-danger {
        background: #dc2626;
    }

    .btn-ghost {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.18);
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        display: grid;
        place-items: center;
        padding: 16px;
        z-index: 50;
    }

    .modal-card {
        width: min(460px, 100%);
        background: #121212;
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 14px;
        padding: 18px;
        animation: fadeIn .2s ease;
    }

    .modal-subtitle {
        color: #b8b8b8;
        margin-top: 6px;
    }

    .modal-card form {
        margin-top: 14px;
        display: grid;
        gap: 10px;
    }

    .modal-card input {
        background: #1f1f1f;
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 10px;
        color: #fff;
        padding: 10px;
    }

    .modal-actions {
        margin-top: 8px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    const tableBody = document.getElementById('mesasTableBody');
    const modal = document.getElementById('mesaModal');
    const createForm = document.getElementById('createMesaForm');
    const openModalBtn = document.getElementById('openCreateMesa');
    const cancelModalBtn = document.getElementById('cancelMesaModal');
    const mesaNumeroInput = document.getElementById('mesaNumero');

    const openModal = () => {
        modal.hidden = false;
        mesaNumeroInput.focus();
    };

    const closeModal = () => {
        modal.hidden = true;
        createForm.reset();
    };

    const renderMesas = (mesas) => {
        if (!mesas.length) {
            tableBody.innerHTML = '<tr><td colspan="4" class="table-empty">No hay mesas registradas.</td></tr>';
            return;
        }

        tableBody.innerHTML = mesas.map((mesa) => {
            const estado = (mesa.estado || 'libre').toLowerCase();
            const estadoClass = estado === 'ocupada' ? 'badge-ocupada' : 'badge-libre';
            const estadoLabel = estado === 'ocupada' ? 'Ocupada' : 'Libre';

            return `
                <tr>
                    <td>${mesa.id}</td>
                    <td>${mesa.numero ?? '-'}</td>
                    <td><span class="badge ${estadoClass}">${estadoLabel}</span></td>
                    <td>
                        <button class="btn btn-danger" data-delete-id="${mesa.id}">Eliminar</button>
                    </td>
                </tr>
            `;
        }).join('');
    };

    const loadMesas = async () => {
        tableBody.innerHTML = '<tr><td colspan="4" class="table-empty">Cargando mesas...</td></tr>';

        try {
            const res = await fetch('/api/mesas', { headers: { Accept: 'application/json' } });
            const payload = await res.json();
            renderMesas(payload.data || []);
        } catch (error) {
            tableBody.innerHTML = '<tr><td colspan="4" class="table-empty">No fue posible cargar las mesas.</td></tr>';
        }
    };

    createForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const numero = mesaNumeroInput.value ? Number(mesaNumeroInput.value) : null;
        const body = {};

        if (numero) {
            body.numero = numero;
        }

        try {
            const res = await fetch('/api/mesas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify(body),
            });

            const payload = await res.json();
            if (!res.ok) {
                alert(payload.message || 'No se pudo crear la mesa.');
                return;
            }

            closeModal();
            await loadMesas();
        } catch (error) {
            alert('No se pudo crear la mesa.');
        }
    });

    tableBody.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-delete-id]');
        if (!button) return;

        const mesaId = button.getAttribute('data-delete-id');
        const confirmed = window.confirm('¿Seguro que deseas eliminar esta mesa?');

        if (!confirmed) return;

        try {
            const res = await fetch(`/api/mesas/${mesaId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            });

            const payload = await res.json();
            if (!res.ok) {
                alert(payload.message || 'No se pudo eliminar la mesa.');
                return;
            }

            await loadMesas();
        } catch (error) {
            alert('No se pudo eliminar la mesa.');
        }
    });

    openModalBtn.addEventListener('click', openModal);
    cancelModalBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    loadMesas();
</script>
@endsection
