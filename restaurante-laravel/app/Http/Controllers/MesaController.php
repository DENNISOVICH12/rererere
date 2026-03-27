<?php

namespace App\Http\Controllers;

use App\Models\ClienteMesa;
use App\Models\Pedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MesaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $restaurantId = (int) $request->user()->restaurant_id;

        $clientesByMesa = ClienteMesa::query()
            ->where('restaurant_id', $restaurantId)
            ->where('activo', true)
            ->get()
            ->groupBy('mesa');

        $pedidos = Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('estado', ['retenido', 'modificacion_solicitada', 'pendiente', 'preparando', 'listo'])
            ->with(['detalle:id,pedido_id,estado_servicio'])
            ->get();

        $mesas = collect($pedidos)
            ->pluck('mesa')
            ->merge($clientesByMesa->keys())
            ->filter(fn ($mesa) => filled($mesa))
            ->unique()
            ->sortBy(fn ($mesa) => (int) preg_replace('/\D+/', '', (string) $mesa))
            ->values()
            ->map(function ($mesa) use ($pedidos, $clientesByMesa) {
                $mesaPedidos = $pedidos->where('mesa', $mesa);
                $hasPendingService = $mesaPedidos->contains(function (Pedido $pedido) {
                    return $pedido->detalle->contains(fn ($item) => ($item->estado_servicio ?? 'pendiente') !== 'entregado');
                });

                $estado = 'libre';
                if ($mesaPedidos->isNotEmpty() || ($clientesByMesa[$mesa] ?? collect())->isNotEmpty()) {
                    $estado = $hasPendingService ? 'pendiente' : 'en_uso';
                }

                return [
                    'id' => rawurlencode((string) $mesa),
                    'codigo' => (string) $mesa,
                    'estado' => $estado,
                    'clientes_activos' => (int) ($clientesByMesa[$mesa]->count() ?? 0),
                    'pedidos_activos' => $mesaPedidos->count(),
                ];
            });

        return response()->json(['data' => $mesas]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $mesa = rawurldecode($id);
        $restaurantId = (int) $request->user()->restaurant_id;

        $clientes = ClienteMesa::query()
            ->where('restaurant_id', $restaurantId)
            ->where('mesa', $mesa)
            ->where('activo', true)
            ->orderBy('id')
            ->get();

        $pedidoQuery = Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->where('mesa', $mesa)
            ->whereIn('estado', ['retenido', 'modificacion_solicitada', 'pendiente', 'preparando', 'listo'])
            ->with(['detalle.menuItem:id,nombre,categoria,precio']);

        $pedidosPorCliente = $pedidoQuery
            ->get()
            ->groupBy('cliente_mesa_id');

        return response()->json([
            'data' => [
                'id' => rawurlencode($mesa),
                'codigo' => $mesa,
                'clientes' => $clientes->map(function (ClienteMesa $cliente) use ($pedidosPorCliente) {
                    $pedidos = $pedidosPorCliente->get($cliente->id, collect());

                    return [
                        'id' => $cliente->id,
                        'nombre' => $cliente->nombre ?: "Cliente #{$cliente->id}",
                        'pedidos' => $pedidos->map(fn (Pedido $pedido) => $this->transformPedido($pedido))->values(),
                        'total' => (float) $pedidos->sum('total'),
                    ];
                })->values(),
            ],
        ]);
    }

    public function clientes(Request $request, string $id): JsonResponse
    {
        return $this->show($request, $id);
    }

    public function storeCliente(Request $request, string $id): JsonResponse
    {
        $mesa = rawurldecode($id);
        $restaurantId = (int) $request->user()->restaurant_id;

        $payload = $request->validate([
            'nombre' => ['nullable', 'string', 'max:120'],
        ]);

        $cliente = ClienteMesa::query()->create([
            'restaurant_id' => $restaurantId,
            'mesa' => $mesa,
            'nombre' => $payload['nombre'] ?? null,
            'activo' => true,
        ]);

        return response()->json([
            'message' => 'Cliente agregado a la mesa.',
            'data' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre ?: "Cliente #{$cliente->id}",
            ],
        ], 201);
    }

    public function pedidosCliente(Request $request, int $id): JsonResponse
    {
        $restaurantId = (int) $request->user()->restaurant_id;

        $cliente = ClienteMesa::query()
            ->where('restaurant_id', $restaurantId)
            ->findOrFail($id);

        $pedidos = Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->where('cliente_mesa_id', $cliente->id)
            ->whereIn('estado', ['retenido', 'modificacion_solicitada', 'pendiente', 'preparando', 'listo'])
            ->with(['detalle.menuItem:id,nombre,categoria,precio'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $pedidos->map(fn (Pedido $pedido) => $this->transformPedido($pedido))->values(),
            'meta' => [
                'cliente' => [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre ?: "Cliente #{$cliente->id}",
                ],
                'total' => (float) $pedidos->sum('total'),
            ],
        ]);
    }

    public function facturarCliente(Request $request, int $id): JsonResponse
    {
        $restaurantId = (int) $request->user()->restaurant_id;

        $cliente = ClienteMesa::query()
            ->where('restaurant_id', $restaurantId)
            ->findOrFail($id);

        $pedidos = Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->where('cliente_mesa_id', $cliente->id)
            ->whereIn('estado', ['retenido', 'modificacion_solicitada', 'pendiente', 'preparando', 'listo'])
            ->get();

        $total = (float) $pedidos->sum('total');

        foreach ($pedidos as $pedido) {
            $pedido->estado = 'entregado';
            $pedido->save();
        }

        return response()->json([
            'message' => 'Cuenta individual facturada correctamente.',
            'data' => [
                'cliente_id' => $cliente->id,
                'total_facturado' => $total,
                'pedidos_facturados' => $pedidos->count(),
            ],
        ]);
    }

    private function transformPedido(Pedido $pedido): array
    {
        return [
            'id' => $pedido->id,
            'estado' => $pedido->estado,
            'total' => (float) $pedido->total,
            'created_at' => optional($pedido->created_at)?->toISOString(),
            'items' => $pedido->detalle->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->menuItem->nombre ?? 'Ítem',
                    'cantidad' => (int) $item->cantidad,
                    'precio_unitario' => (float) $item->precio_unitario,
                    'importe' => (float) $item->importe,
                    'grupo_servicio' => $item->grupo_servicio,
                    'estado_servicio' => $item->estado_servicio,
                    'nota' => $item->nota,
                ];
            })->values(),
        ];
    }
}
