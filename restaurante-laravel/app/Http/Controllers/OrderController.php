<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Services\WaiterNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $restaurantId = $request->integer('restaurant_id');
        $items = $request->input('items', []);

        if (!$restaurantId) {
            return response()->json([
                'message' => 'No se recibió el restaurante del pedido.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (empty($items) || !is_array($items)) {
            return response()->json([
                'message' => 'El pedido no contiene items válidos.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $resolvedClienteId = $this->resolveAuthenticatedClienteId($restaurantId)
            ?? $this->resolveClienteId($request->input('cliente_id'), $restaurantId);

        if (!$resolvedClienteId) {
            $clienteInvitado = $this->createGuestCliente($request, $restaurantId);

            if (!$clienteInvitado) {
                return response()->json([
                    'message' => 'No se pudo identificar ni crear el cliente para el pedido.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $resolvedClienteId = (int) $clienteInvitado->id;
        }

        $total = collect($items)->sum(
            fn ($item) => ((float) ($item['precio_unitario'] ?? 0)) * ((int) ($item['cantidad'] ?? 0))
        );

        $order = Pedido::create([
            'cliente_id' => $resolvedClienteId,
            'restaurant_id' => $restaurantId,
            'mesa' => $request->mesa_id,
            'estado' => Pedido::STATUS_RETAINED,
            'hold_expires_at' => now()->addSeconds(Pedido::holdWindowSeconds()),
            'total' => $total,
        ]);

        foreach ($items as $item) {
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $precioUnitario = (float) ($item['precio_unitario'] ?? 0);

            $menuItem = \App\Models\MenuItem::find($item['menu_item_id']);
            $grupoServicio = strtolower($menuItem?->categoria ?? '') === 'bebida' ? 'bebida' : 'plato';

            PedidoDetalle::create([
                'restaurant_id' => $restaurantId,
                'pedido_id' => $order->id,
                'menu_item_id' => $item['menu_item_id'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'importe' => $precioUnitario * $cantidad,
                'nota' => $item['nota'] ?? null,
                'grupo_servicio' => $grupoServicio,
                'estado_servicio' => 'pendiente',
            ]);
        }

        $order->load(['detalle.menuItem', 'cliente']);
        app(WaiterNotificationService::class)->createFromPedido(
            $order,
            'new_order',
            '🆕 Nuevo pedido recibido',
            [
                'origin' => 'customer',
                'hold_expires_at' => optional($order->hold_expires_at)->toIso8601String(),
            ]
        );

        return response()->json([
            'message' => 'Pedido creado exitosamente.',
            'data' => $this->transformCustomerOrderPayload($order),
            'meta' => [
                'hold_window_seconds' => Pedido::holdWindowSeconds(),
            ],
        ], Response::HTTP_CREATED);
    }

    public function sendNowToKitchen(Request $request, Pedido $order): JsonResponse
    {
        Pedido::releaseExpiredRetentionWindow();
        $order->refresh();

        $resolvedClienteId = $this->resolveAuthenticatedClienteId((int) $order->restaurant_id)
            ?? $this->resolveClienteId($request->input('cliente_id'), $order->restaurant_id);

        if ($resolvedClienteId && (int) $order->cliente_id !== $resolvedClienteId) {
            return response()->json(['message' => 'No puedes confirmar este pedido.'], Response::HTTP_FORBIDDEN);
        }

        if (!$order->isInRetentionWindow()) {
            return response()->json([
                'message' => 'Este pedido ya no está en ventana de cambios y no puede enviarse con confirmación anticipada.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $order->releaseToKitchen(Pedido::RELEASE_TRIGGER_EARLY_CONFIRMATION);

        return response()->json([
            'message' => 'Pedido confirmado y enviado a cocina.',
            'data' => $this->transformCustomerOrderPayload($order->fresh(['detalle.menuItem', 'cliente'])),
            'meta' => [
                'hold_window_seconds' => Pedido::holdWindowSeconds(),
            ],
        ]);
    }

    public function index()
    {
        Pedido::releaseExpiredRetentionWindow();

        return Pedido::where('estado', Pedido::STATUS_PENDING)
            ->with('detalle.menuItem')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function clientePedidos(int $clienteId): JsonResponse
    {
        Pedido::releaseExpiredRetentionWindow();

        $resolvedClienteId = $this->resolveClienteId($clienteId);

        if (!$resolvedClienteId) {
            return response()->json(['data' => [], 'meta' => ['hold_window_seconds' => Pedido::holdWindowSeconds()]]);
        }

        $pedidos = Pedido::with(['detalle.menuItem', 'cliente'])
            ->where('cliente_id', $resolvedClienteId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn (Pedido $pedido) => $this->transformCustomerOrderPayload($pedido));

        return response()->json(['data' => $pedidos, 'meta' => ['hold_window_seconds' => Pedido::holdWindowSeconds()]]);
    }

    public function updateStatus(Request $request, Pedido $order): JsonResponse
    {
        $order->update(['estado' => $request->estado]);

        return response()->json(['message' => 'Estado actualizado ✅']);
    }

    private function resolveAuthenticatedClienteId(int $restaurantId): ?int
    {
        $authCliente = auth('sanctum')->user();
        if (!$authCliente) return null;

        if ($authCliente instanceof Cliente) {
            return (int) $authCliente->id;
        }

        return Cliente::query()
            ->where('usuario_id', (int) $authCliente->id)
            ->where('restaurant_id', $restaurantId)
            ->value('id');
    }

    private function resolveClienteId($incomingId, ?int $restaurantId = null): ?int
    {
        if (!$incomingId) return null;

        $incomingId = (int) $incomingId;

        $clienteDirecto = Cliente::query()
            ->where('id', $incomingId)
            ->when($restaurantId, fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->first();

        if ($clienteDirecto) return (int) $clienteDirecto->id;

        $clientePorUsuario = Cliente::query()
            ->when($restaurantId, fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->where('usuario_id', $incomingId)
            ->first();

        return $clientePorUsuario ? (int) $clientePorUsuario->id : null;
    }

    private function createGuestCliente(Request $request, int $restaurantId): ?Cliente
    {
        $nombres = trim((string) $request->input('nombres', 'Cliente'));
        $apellidos = trim((string) $request->input('apellidos', 'Invitado'));

        return Cliente::create([
            'nombres' => $nombres !== '' ? $nombres : 'Cliente',
            'apellidos' => $apellidos !== '' ? $apellidos : 'Invitado',
            'correo' => $request->input('correo'),
            'password' => null,
            'telefono' => $request->input('telefono'),
            'dni' => $request->input('dni'),
            'edad' => $request->input('edad'),
            'restaurant_id' => $restaurantId,
            'activo' => true,
        ]);
    }

    private function transformCustomerOrderPayload(Pedido $pedido): array
    {
        $grupos = $pedido->detalle
            ->groupBy('grupo_servicio')
            ->map(fn ($items, $grupo) => [
                'grupo' => $grupo,
                'estado' => $items->pluck('estado_servicio')->unique()->first(),
                'items' => $items->values(),
            ])
            ->values();

        return [
            ...$pedido->toArray(),
            'grupos_servicio' => $grupos,
            'cliente_nombre' => $pedido->cliente
                ? (trim($pedido->cliente->nombres.' '.$pedido->cliente->apellidos) ?: 'Cliente invitado')
                : 'Cliente invitado',
            'hold_expires_at' => optional($pedido->hold_expires_at)->toIso8601String(),
            'released_to_kitchen_at' => optional($pedido->released_to_kitchen_at)->toIso8601String(),
            'release_trigger' => $pedido->release_trigger,
            'can_be_edited' => $pedido->canBeEditedByWaiter(),
            'can_send_now' => $pedido->isInRetentionWindow(),
            'change_request_overdue' => $pedido->isChangeRequestOverdue(),
        ];
    }
}
