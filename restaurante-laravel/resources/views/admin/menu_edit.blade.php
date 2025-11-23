@extends('layouts.admin')

@section('content')
<div style="padding:30px; color:white;">
    <h2>✏️ Editar Plato</h2>

    <form action="{{ route('admin.menu.update', $item->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <input type="text" name="nombre" value="{{ $item->nombre }}" placeholder="Nombre" required>
        <input type="text" name="descripcion" value="{{ $item->descripcion }}" placeholder="Descripción">
        <select name="categoria">
            <option value="plato" {{ $item->categoria=='plato' ? 'selected':'' }}>Plato</option>
            <option value="bebida" {{ $item->categoria=='bebida' ? 'selected':'' }}>Bebida</option>
        </select>
        <input type="number" name="precio" value="{{ $item->precio }}" required>
        <input type="file" name="imagen">

        <button class="btn btn-edit" style="margin-top:10px;">Guardar Cambios</button>
        <a href="{{ route('admin.menu') }}" class="btn" style="background:#444;">Cancelar</a>
    </form>
</div>
@endsection
