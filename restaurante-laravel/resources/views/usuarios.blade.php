<!DOCTYPE html>
<html>
<head>
    <title>Usuarios Registrados</title>
</head>
<body>

<h1>👤 Usuarios Registrados</h1>

@if($usuarios->count() > 0)
<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Usuario</th>
        <th>Rol</th>
        <th>Activo</th>
    </tr>

    @foreach ($usuarios as $u)
    <tr>
        <td>{{ $u->id }}</td>
        <td>{{ $u->usuario }}</td>
        <td>{{ $u->rol }}</td>
        <td>{{ $u->activo ? '✅' : '❌' }}</td>
    </tr>
    @endforeach
</table>

{{ $usuarios->links() }}

@else
<p>No hay usuarios registrados.</p>
@endif

</body>
</html>
