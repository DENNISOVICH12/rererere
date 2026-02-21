<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mesero | Pedidos activos</title>
    @vite('resources/js/mesero/main.js')
</head>
<body>
    <div id="mesero-app"></div>
</body>
</html>
