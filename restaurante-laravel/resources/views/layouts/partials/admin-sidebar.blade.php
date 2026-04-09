@php
    $sidebarSections = [
        [
            'title' => 'GENERAL',
            'links' => [
                [
                    'label' => 'Dashboard',
                    'icon' => '📊',
                    'href' => route('admin.dashboard'),
                    'active' => request()->routeIs('admin.dashboard'),
                ],
                [
                    'label' => 'Historial de Clientes',
                    'icon' => '🧠',
                    'href' => route('admin.clientes.historial'),
                    'active' => request()->routeIs('admin.clientes.historial'),
                ],
            ],
        ],
        [
            'title' => 'OPERACIÓN',
            'links' => [
                [
                    'label' => 'Gestión de Mesas',
                    'icon' => '🪑',
                    'href' => route('admin.mesas'),
                    'active' => request()->routeIs('admin.mesas'),
                ],
            ],
        ],
        [
            'title' => 'PRODUCCIÓN',
            'links' => [
                [
                    'label' => 'Cocina',
                    'icon' => '👨‍🍳',
                    'href' => route('cocina.panel'),
                    'active' => request()->routeIs('cocina.panel') || request()->is('cocina*'),
                ],
                [
                    'label' => 'Barra',
                    'icon' => '🍹',
                    'href' => route('barra.panel'),
                    'active' => request()->routeIs('barra.panel') || request()->routeIs('bar.panel') || request()->is('bar*') || request()->is('barra*'),
                ],
                [
                    'label' => 'Carta Digital',
                    'icon' => '📱',
                    'href' => route('carta.digital'),
                    'active' => request()->routeIs('carta.digital'),
                ],
            ],
        ],
        [
            'title' => 'CONFIGURACIÓN',
            'links' => [
                [
                    'label' => 'Gestionar Usuarios',
                    'icon' => '👥',
                    'href' => route('usuarios.panel'),
                    'active' => request()->routeIs('usuarios.panel') || request()->is('usuarios*'),
                ],
                [
                    'label' => 'Gestión de Menú',
                    'icon' => '🍽️',
                    'href' => route('admin.menu'),
                    'active' => request()->routeIs('admin.menu') || request()->routeIs('admin.menu.*') || request()->is('admin/menu*'),
                ],
            ],
        ],
    ];
@endphp

<aside class="global-sidebar" aria-label="Navegación principal">
    <div class="global-sidebar__brand">
        <span class="global-sidebar__icon" aria-hidden="true">🍽️</span>
        <span class="global-sidebar__text">ODER EASY</span>
    </div>

    <nav class="global-sidebar__nav">
        @foreach ($sidebarSections as $section)
            <section class="global-sidebar__section">
                <h2 class="global-sidebar__section-title">{{ $section['title'] }}</h2>
                <div class="global-sidebar__section-links">
                    @foreach ($section['links'] as $link)
                        <a href="{{ $link['href'] }}" class="global-sidebar__link {{ $link['active'] ? 'is-active' : '' }}" title="{{ $link['label'] }}">
                            <span class="global-sidebar__icon" aria-hidden="true">{{ $link['icon'] }}</span>
                            <span class="global-sidebar__text">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="global-sidebar__logout-form">
        @csrf
        <button type="submit" class="global-sidebar__logout" data-logout title="Cerrar sesión">
            <span class="global-sidebar__icon" aria-hidden="true">⏻</span>
            <span class="global-sidebar__text">Cerrar sesión</span>
        </button>
    </form>
</aside>
