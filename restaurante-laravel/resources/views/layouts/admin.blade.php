<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ODER EASY · Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <style>
        body {
            margin: 0;
            display: flex;
            font-family: 'Inter', sans-serif;
            background: #0f0f0f;
            color: #fff;
        }

        /* === SIDEBAR === */
        .sidebar {
            width: 240px;
            height: 100vh;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(18px);
            border-right: 1px solid rgba(255,255,255,0.12);
            padding: 22px 18px;
            position: fixed;
            top: 0;
            left: 0;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            margin-bottom: 40px;
            text-align: center;
            color: #F8ECE4;
        }

        .nav-link {
            display: block;
            padding: 12px 14px;
            border-radius: 10px;
            color: #bfbfbf;
            text-decoration: none;
            margin-bottom: 8px;
            transition: 0.25s;
            font-size: 15px;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.12);
            color: white;
            transform: translateX(3px);
        }

        .active {
            background: #9c2030 !important;
            color: white !important;
            box-shadow: 0 3px 14px rgba(156,32,48,0.4);
        }

        /* === CONTENT AREA === */
        .content {
            margin-left: 260px;
            padding: 40px;
            width: calc(100% - 260px);
            min-height: 100vh;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 10px;
        }

        /* === CARD STYLE === */
        .card {
            background: rgba(255,255,255,0.05);
            border-radius: 14px;
            padding: 18px 22px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 4px 22px rgba(0,0,0,0.4);
        }

        /* TABLES PREMIUM */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        th {
            text-align: left;
            font-weight: 600;
            color: #ffdede;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 13px;
        }
        .badge-pendiente { background: #ffb84d33; color: #ffb84d; }
        .badge-proceso   { background: #4da6ff33; color: #4da6ff; }
        .badge-listo     { background: #6eff7a33; color: #6eff7a; }

    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand">🍷 ODER EASY · Admin</div>

        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>

        <a href="{{ route('usuarios.panel') }}" class="nav-link {{ request()->routeIs('usuarios.panel') ? 'active' : '' }}">Gestionar Usuarios</a>

        <a href="{{ route('cocina.panel') }}" class="nav-link">Cocina</a>

        <a href="{{ route('cocina.pedidos.todos') }}" class="nav-link">Pedidos / Mesas</a>

        <a href="{{ route('carta.digital') }}" class="nav-link">Carta Digital</a>

        <form method="POST" action="{{ route('logout') }}" style="margin-top: 40px;">
            @csrf
            <button style="width:100%; padding:10px; border-radius:8px; background:#9c2030; border:none; color:white; cursor:pointer;">
                Cerrar Sesión
            </button>
        </form>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content">
        @yield('content')
    </main>

</body>
</html>
