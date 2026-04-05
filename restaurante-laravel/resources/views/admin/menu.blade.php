@extends('layouts.admin')

@section('content')
<div class="admin-container">
    <section class="menu-module-card">
        <div class="menu-header">
            <h2 class="title">Gestión del Menú</h2>
            <p class="text-muted">Administra platos y bebidas con una vista más limpia y consistente.</p>
        </div>

        <!-- ✅ MENSAJES -->
        <div id="msg-status" class="mb-2 menu-status-msg" style="font-weight:700;"></div>

        <!-- BUSCAR -->
        <form method="GET" action="{{ route('admin.menu') }}" class="search-bar">
            <div class="search-input-wrap">
                <span class="search-icon" aria-hidden="true">🔍</span>
                <input type="text" name="buscar" placeholder="Buscar plato..." value="{{ request('buscar') }}">
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="{{ route('admin.menu') }}" class="btn btn-secondary btn-clear">Limpiar</a>
        </form>

        <!-- TABLA -->
        <div class="menu-table-wrap">
            <table class="menu-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item->id }}</td>

                        <td class="thumb-cell">
                            @if($item->imagen)
                                <img src="{{ $item->imagen }}" class="thumb" alt="Imagen de {{ $item->nombre }}">
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td>{{ $item->nombre }}</td>
                        <td>{{ $item->categoria }}</td>
                        <td>${{ number_format($item->precio, 0, '.', '.') }}</td>

                        <td>
                            <div class="table-actions">
                                <a href="{{ route('admin.menu.edit', $item->id) }}" class="btn btn-edit">Editar</a>

                                <form action="{{ route('admin.menu.delete', $item->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-delete" onclick="return confirm('¿Seguro que deseas eliminar este plato?');">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination">
            {{ $items->links() }}
        </div>

        <!-- NUEVO PLATO -->
        <h3 class="section-title">Nuevo Plato</h3>

        <form id="formNuevoPlato" enctype="multipart/form-data" class="new-dish-form">
            @csrf

            <div class="new-dish-grid">
                <div class="inline-field">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" type="text" name="nombre" placeholder="Nombre" value="">
                </div>

                <div class="inline-field">
                    <label for="descripcion">Descripción</label>
                    <input id="descripcion" type="text" name="descripcion" placeholder="Descripción">
                </div>

                <div class="inline-field">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria">
                        <option value="plato">Plato</option>
                        <option value="bebida">Bebida</option>
                    </select>
                </div>

                <div class="inline-field">
                    <label for="precio">Precio</label>
                    <input id="precio" type="number" name="precio" placeholder="Precio" min="1">
                </div>

                <div class="inline-field full-width">
                    <label for="imagenInput">Imagen</label>
                    <div class="file-input-wrap">
                        <input id="imagenInput" type="file" name="imagen" class="file-input-hidden" accept="image/*">
                        <label for="imagenInput" class="file-input-button">Seleccionar archivo</label>
                        <span id="fileName" class="file-input-name">Ningún archivo seleccionado</span>
                    </div>
                </div>
            </div>

            <button type="submit" id="btnCrear" class="btn btn-create">
                <span id="btnCrearText">Crear</span>
                <span id="btnCrearLoad" style="display:none;">Cargando...</span>
            </button>
        </form>
    </section>

</div>

<!-- ✅ SCRIPT AJAX -->
<script>
const imagenInput = document.getElementById("imagenInput");
const fileName = document.getElementById("fileName");

if (imagenInput && fileName) {
    imagenInput.addEventListener("change", function () {
        fileName.textContent = this.files && this.files[0]
            ? this.files[0].name
            : "Ningún archivo seleccionado";
    });
}

document.getElementById("formNuevoPlato").addEventListener("submit", async function(e){
    e.preventDefault();

    const msg = document.getElementById("msg-status");
    const btn = document.getElementById("btnCrear");
    const btnText = document.getElementById("btnCrearText");
    const btnLoad = document.getElementById("btnCrearLoad");

    // Mostrar estado cargando
    btn.disabled = true;
    btnText.style.display = "none";
    btnLoad.style.display = "inline-block";

    try {

        const formData = new FormData(this);

        const res = await fetch("{{ route('admin.menu.store') }}", {
            method: "POST",
            body: formData
        });

        if (!res.ok) throw new Error("Fallo en servidor");

        msg.style.color = "#6fff8f";
        msg.textContent = "✅ Plato creado correctamente";
        this.reset();

        // Refrescar tabla después de 1 segundo
        setTimeout(() => location.reload(), 900);

    } catch (err) {
        msg.style.color = "#ff6f6f";
        msg.textContent = "⚠️ No hay conexión o el servidor no respondió.";
    }

    // Restaurar botón
    btn.disabled = false;
    btnText.style.display = "inline-block";
    btnLoad.style.display = "none";
});
</script>

@endsection
