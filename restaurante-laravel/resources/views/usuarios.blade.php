@extends('layouts.admin')

@section('content')
<div class="card mb-2">
    <h1>👤 Gestión de usuarios</h1>
    <p class="text-muted">Crea, actualiza o elimina cuentas para el personal interno del restaurante.</p>
</div>

    @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-error">
        <strong>Revisa los datos ingresados:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid-two-cols">
    <section class="card">
        <h2>Agregar nuevo usuario</h2>
        <p class="text-muted">Los clientes se registran automáticamente desde la carta digital.</p>
        <form method="POST" action="{{ route('usuarios.store') }}">
            @csrf
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input class="form-control" id="usuario" name="usuario" value="{{ old('usuario') }}" required>
            </div>
            <div class="form-inline">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre') }}" placeholder="Ej: Ana">
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido</label>
                    <input class="form-control" id="apellido" name="apellido" value="{{ old('apellido') }}" placeholder="Ej: Torres">
                </div>
            </div>
            <div class="form-group">
                <label for="correo">Correo</label>
                <input class="form-control" id="correo" name="correo" type="email" value="{{ old('correo') }}" required>
            </div>
            <div class="form-inline">
                <div class="form-group">
                    <label for="rol">Rol</label>
                    <select class="form-control" id="rol" name="rol" required>
                        <option value="" disabled {{ old('rol') ? '' : 'selected' }}>Selecciona un rol</option>
                        @foreach ($roles as $key => $label)
                            <option value="{{ $key }}" {{ old('rol') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input class="form-control" id="password" name="password" type="password" required>
                </div>
            </div>
            <div class="form-group" style="flex-direction: row; align-items: center; gap: 10px;">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox" id="activo" name="activo" value="1" checked>
                <label for="activo" style="margin: 0;">Usuario activo</label>
            </div>
            <button type="submit" class="btn btn-primary">Crear usuario</button>
        </form>
    </section>

    <section class="card">
        <h2>Usuarios registrados</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $u)
                    <tr>
                        <td>#{{ $u->id }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $u->usuario }}</div>
                            <small class="text-muted">{{ $u->nombre }} {{ $u->apellido }}</small><br>
                            <small class="text-muted">{{ $u->correo }}</small>
                        </td>
                        <td>{{ $roles[$u->rol] ?? $u->rol }}</td>
                        <td>
                            @if($u->activo)
                                <span class="badge-success">Activo</span>
                            @else
                                <span class="badge-muted">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <details>
                                <summary>Editar</summary>
                                <form method="POST" action="{{ route('usuarios.update', $u->id) }}" class="mt-1">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label>Usuario</label>
                                        <input class="form-control" name="usuario" value="{{ $u->usuario }}" required>
                                    </div>
                                    <div class="form-inline">
                                        <div class="form-group">
                                            <label>Nombre</label>
                                            <input class="form-control" name="nombre" value="{{ $u->nombre }}">
                                        </div>
                                        <div class="form-group">
                                            <label>Apellido</label>
                                            <input class="form-control" name="apellido" value="{{ $u->apellido }}">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Correo</label>
                                        <input class="form-control" type="email" name="correo" value="{{ $u->correo }}" required>
                                    </div>
                                    <div class="form-inline">
                                        <div class="form-group">
                                            <label>Rol</label>
                                            <select class="form-control" name="rol" required>
                                                @foreach ($roles as $key => $label)
                                                    <option value="{{ $key }}" {{ $u->rol === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Nueva contraseña (opcional)</label>
                                            <input class="form-control" type="password" name="password" placeholder="••••••">
                                        </div>
                                    </div>
                                    <div class="form-group" style="flex-direction: row; align-items: center; gap: 10px;">
                                        <input type="hidden" name="activo" value="0">
                                        <input type="checkbox" name="activo" value="1" {{ $u->activo ? 'checked' : '' }}>
                                        <label style="margin: 0;">Activo</label>
                                    </div>
                                    <button type="submit" class="btn btn-secondary">Guardar cambios</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route('usuarios.delete', $u->id) }}" data-confirm-message="¿Eliminar al usuario {{ $u->usuario }}?" data-confirm-title="Eliminar usuario" data-confirm-accept="Eliminar" data-confirm-cancel="Cancelar" class="mt-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No hay usuarios registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-2">
            {{ $usuarios->links() }}
        </div>
    </section>
</div>
@endsection