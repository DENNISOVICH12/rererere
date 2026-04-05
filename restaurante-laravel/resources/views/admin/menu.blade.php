@extends('layouts.admin')

@section('content')
<div class="admin-container">

    <h2 class="title">Gestión del Menú</h2>

    <!-- ✅ MENSAJES -->
    <div id="msg-status" class="mb-2" style="font-weight:700;"></div>

    <!-- BUSCAR -->
    <form method="GET" action="{{ route('admin.menu') }}" class="search-bar">
        <input type="text" name="buscar" placeholder="Buscar plato..." value="{{ request('buscar') }}">
        <button>Buscar</button>
        <a href="{{ route('admin.menu') }}" class="btn-clear">Limpiar</a>
    </form>

    <!-- TABLA -->
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

                <td>
                    @if($item->imagen)
                        <img src="{{ $item->imagen }}" class="thumb">
                    @else
                        <span>-</span>
                    @endif
                </td>

                <td>{{ $item->nombre }}</td>
                <td>{{ $item->categoria }}</td>
                <td>${{ number_format($item->precio, 0, '.', '.') }}</td>

                <td>
                    <a href="{{ route('admin.menu.edit', $item->id) }}" class="btn-edit">Editar</a>

                    <form action="{{ route('admin.menu.delete', $item->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-delete" onclick="return confirm('¿Seguro que deseas eliminar este plato?');">
                            Eliminar
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination">
        {{ $items->links() }}
    </div>

    <!-- NUEVO PLATO -->
    <h3 class="section-title">Nuevo Plato</h3>

<form id="formNuevoPlato" enctype="multipart/form-data">
    @csrf

    <div class="inline-form-row">

        <div class="inline-field">
            <input type="text" name="nombre" placeholder="Nombre" value="">
        </div>

        <div class="inline-field">
            <input type="text" name="descripcion" placeholder="Descripción">
        </div>

        <div class="inline-field">
            <select name="categoria">
                <option value="plato">Plato</option>
                <option value="bebida">Bebida</option>
            </select>
        </div>

        <div class="inline-field">
            <input type="number" name="precio" placeholder="Precio" min="1">
        </div>

        <div class="inline-field">
            <input type="file" name="imagen">
        </div>

        <button type="submit" id="btnCrear" class="btn btn-create">
            <span id="btnCrearText">Crear</span>
            <span id="btnCrearLoad" style="display:none;">Cargando...</span>
        </button>

    </div>
</form>

</div>

<!-- ✅ SCRIPT AJAX -->
<script>
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
