<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ODER EASY · Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>

<body>
    @include('layouts.partials.admin-sidebar')

    <main class="content admin-content">
        @yield('content')
    </main>

    <script src="{{ asset('js/confirm-modal.js') }}" defer></script>
    <script src="{{ asset('js/confirm-actions.js') }}" defer></script>
    <script src="{{ asset('js/logout.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
