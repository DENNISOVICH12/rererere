<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">


    <style>
        body {
            margin: 0;
            background: #0e0e0f;
            color: #fff;
            font-family: 'Inter', sans-serif;
            display: flex;
        }

        /* === SIDEBAR === */
        .sidebar {
            width: 70px;
            background: rgba(255,255,255,0.03);
            border-right: 1px solid rgba(255,255,255,0.07);
            backdrop-filter: blur(14px);
            padding: 18px 10px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            gap: 6px;
            overflow: hidden;
            transition: width .25s ease;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 900;
        }

        .sidebar:hover { width: 230px; }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 15px;
            color: rgba(255,255,255,0.75);
            cursor: pointer;
            white-space: nowrap;
            transition: .25s ease;
            text-decoration: none;
        }

        .nav-item span {
            opacity: 0;
            transform: translateX(-8px);
            transition: .25s ease;
        }

        .sidebar:hover .nav-item span {
            opacity: 1;
            transform: translateX(0);
        }

        .nav-item:hover { background: rgba(255,255,255,0.12); }

        .nav-item.active {
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
            box-shadow: 0 4px 14px rgba(0,0,0,0.35);
        }

        .icon { font-size: 18px; }

        /* === MAIN CONTENT === */
        .main-content {
            margin-left: 80px;
            padding: 30px;
            width: 100%;
            transition: .25s;
        }

        .sidebar:hover ~ .main-content { margin-left: 240px; }

        h1 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 4px;
        }
        .subtitle { opacity: .75; margin-bottom: 30px; }

        /* === CARDS === */
        .cards {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 25px;
        }

        .card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            padding: 16px 22px;
            border-radius: 14px;
            width: 190px;
            font-size: 14px;
            backdrop-filter: blur(12px);
        }
        .card b { font-size: 20px; display: block; margin-top: 4px; }

        /* === TABLE === */
        .table-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px;
            padding: 25px;
            width: 100%;
            max-width: 1000px;
            backdrop-filter: blur(10px);
        }

        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 12px 10px; text-align: left; }
        th { opacity: .75; font-size: 14px; }
        tr + tr { border-top: 1px solid rgba(255,255,255,0.08); }

        /* Badges */
        .badge { padding: 6px 10px; border-radius: 8px; font-size: 13px; font-weight: 600; display: inline-block; }

    </style>
</head>

<body>

<!-- ✅ SIDEBAR -->
<div class="sidebar">
    <a href="/admin" class="nav-item {{ request()->is('admin') ? 'active' : '' }}">
        <i class="icon">🏠</i><span>Dashboard</span>
    </a>

    <a href="/usuarios" class="nav-item {{ request()->is('usuarios*') ? 'active' : '' }}">
        <i class="icon">👤</i><span>Gestionar Usuarios</span>
    </a>

    <a href="/cocina" class="nav-item {{ request()->is('cocina') ? 'active' : '' }}">
        <i class="icon">🍳</i><span>Cocina</span>
    </a>

    <a href="/pedidos" class="nav-item {{ request()->is('pedidos*') ? 'active' : '' }}">
        <i class="icon">🧾</i><span>Mesas / Pedidos</span>
    </a>

    <a href="{{ route('carta.digital') }}" class="nav-item">
        <i class="icon">📋</i><span>Carta Digital</span>
    </a>

    <a href="{{ route('admin.menu') }}" class="nav-item">
        <i class="icon">🍽</i><span> Gestión del Menú</span>
    </a>

</div>


<!-- ✅ MAIN CONTENT -->
<div class="main-content">

    <h1>Panel Administrativo</h1>
    <p class="subtitle">Resumen general del restaurante</p>

    <div class="cards">
        <div class="card">Usuarios Totales<b>{{ $totalUsuarios }}</b></div>
        <div class="card">Clientes<b>{{ $totalClientes }}</b></div>
        <div class="card">Meseros<b>{{ $totalMeseros }}</b></div>
        <div class="card">Cocineros<b>{{ $totalCocineros }}</b></div>
        <div class="card">Pedidos Hoy<b>{{ $totalPedidosHoy }}</b></div>
        <div class="card">Items en Menú<b>{{ $totalMenuItems }}</b></div>
    </div>

    <!-- ✅ PEDIDOS RECIENTES -->
    <div class="table-box">
        <h3 style="margin-bottom: 10px;">Pedidos Recientes</h3>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Mesa</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha</th>
            </tr>
            </thead>
            <tbody id="tablaPedidosRecientes">

            @foreach($pedidosRecientes as $p)
                @php
                    $estado = strtolower($p->estado);
                    $badgeClass = match($estado) {
                        'pendiente'   => 'background:#ff4b4b; color:white;',
                        'preparando'  => 'background:#ffcc00; color:black;',
                        'listo'       => 'background:#2ecc71; color:white;',
                        'entregado'   => 'background:#3498db; color:white;',
                        default       => 'background:#666; color:white;'
                    };

                    $clienteNombre = $p->cliente?->usuario ?? 'Invitado';
                    $clienteStyle = $p->cliente ? 'color:#a4c6ff;' : 'color:#ccc;';
                    $clienteIcon  = $p->cliente ? '✨' : '🧾';
                @endphp

                <tr style="{{ $estado === 'pendiente' ? 'background:rgba(255,0,0,0.08);' : '' }}">
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->mesa ?? '-' }}</td>
                    <td style="{{ $clienteStyle }}">{{ $clienteIcon }} {{ $clienteNombre }}</td>
                    <td>${{ number_format($p->total, 0, ',', '.') }}</td>
                    <td><span class="badge" style="{{ $badgeClass }}">{{ ucfirst($p->estado) }}</span></td>
                    <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                </tr>

            @endforeach

            </tbody>
        </table>

    </div>

</div>

</body>
</html>
