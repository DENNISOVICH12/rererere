<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ODER EASY · Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>

<body>
    <aside class="sidebar">
        <div class="brand">ODER EASY · Admin</div>

        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('usuarios.panel') }}" class="nav-link {{ request()->routeIs('usuarios.panel') ? 'active' : '' }}">Gestionar Usuarios</a>
        <a href="{{ route('cocina.panel') }}" class="nav-link">Cocina</a>
        <a href="{{ route('barra.panel') }}" class="nav-link">Barra</a>
        <a href="{{ route('cocina.pedidos.todos') }}" class="nav-link">Pedidos / Mesas</a>
        <a href="{{ route('admin.mesas') }}" class="nav-link {{ request()->routeIs('admin.mesas') ? 'active' : '' }}">Gestión de Mesas</a>
        <a href="{{ route('carta.digital') }}" class="nav-link">Carta Digital</a>

        <form method="POST" action="{{ route('logout') }}" style="margin-top: 28px;">
            @csrf
            <button type="submit" class="btn btn-primary" style="width: 100%;">Cerrar Sesión</button>
        </form>
    </aside>

    <main class="content">
        @yield('content')
    </main>

</body>
</html>
