@extends('layouts.admin')

@section('content')
<style>
    .grid-two-cols {
        display: grid;
        grid-template-columns: 1fr 1.6fr;
        gap: 18px;
        align-items: start;
    }
    .card h2 { margin-top: 0; }
    .form-group { margin-bottom: 12px; display: flex; flex-direction: column; gap: 6px; }
    .form-group label { color: #f6dede; font-size: 14px; }
    .form-control, select {
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(0,0,0,0.35);
        color: #fff;
    }
    .form-inline { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .btn {
        padding: 10px 14px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
    }
    .btn-primary { background: #9c2030; color: #fff; box-shadow: 0 6px 18px rgba(156,32,48,0.4); }
    .btn-secondary { background: rgba(255,255,255,0.08); color: #fff; }
    .btn-danger { background: #3d0b12; color: #ffb4c0; border: 1px solid rgba(255,255,255,0.1); }
    .badge-success { background: #6eff7a33; color: #6eff7a; padding: 6px 10px; border-radius: 10px; }
    .badge-muted { background: rgba(255,255,255,0.08); color: #ccc; padding: 6px 10px; border-radius: 10px; }
    .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; }
    .alert-success { background: #153524; color: #9ff8c9; border: 1px solid #1f5f3b; }
    .alert-error { background: #3a0d0d; color: #ffcfcf; border: 1px solid #6e1d1d; }
    details summary { cursor: pointer; color: #ffdede; }
</style>

<div class="card" style="margin-bottom: 18px;">
    <h1>👤 Gestión de usuarios</h1>
    <p style="color:#cfcfcf;">Crea, actualiza o elimina cuentas para el personal y clientes del restaurante.</p>
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
                            <small style="color:#cfcfcf;">{{ $u->nombre }} {{ $u->apellido }}</small><br>
                            <small style="color:#cfcfcf;">{{ $u->correo }}</small>
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
                                <form method="POST" action="{{ route('usuarios.update', $u->id) }}" style="margin-top:10px;">
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
                            <form method="POST" action="{{ route('usuarios.delete', $u->id) }}" onsubmit="return confirm('¿Eliminar al usuario {{ $u->usuario }}?');" style="margin-top:8px;">
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

        <div style="margin-top:14px;">
            {{ $usuarios->links() }}
        </div>
    </section>
</div>
@endsection