<?php

namespace App\Http\Controllers;

use App\Models\ClienteMesa;
use App\Models\Pedido;
use App\Models\Mesa; // 🔥 NUEVO
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class MesaController extends Controller
{
    private const PEDIDO_ESTADOS_INACTIVOS = ['facturado', 'cancelado'];

    public function store(Request $request): JsonResponse
    {
        $restaurantId = (int) ($request->user()->restaurant_id ?? 1);

        $payload = $request->validate([
            'numero' => [
                'nullable',
                'integer',
                'min:1',
                Rule::unique('mesas', 'numero')->where(fn ($query) => $query->where('restaurant_id', $restaurantId)),
            ],
        ]);

        $numero = $payload['numero'] ?? null;

        if ($numero === null) {
            $ultimoNumero = (int) Mesa::query()
                ->where('restaurant_id', $restaurantId)
                ->max('numero');

            $numero = $ultimoNumero > 0 ? ($ultimoNumero + 1) : 1;
        }

        $mesa = Mesa::query()->create([
            'restaurant_id' => $restaurantId,
            'numero' => $numero,
            'estado' => 'libre',
        ]);

        return response()->json([
            'message' => 'Mesa creada correctamente.',
            'data' => [
                'id' => (string) $mesa->id,
                'numero' => $mesa->numero,
                'estado' => 'libre',
            ],
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $restaurantId = (int) ($request->user()->restaurant_id ?? 1);

        $mesa = Mesa::query()
            ->where('restaurant_id', $restaurantId)
            ->findOrFail($id);

        $mesaValues = array_filter([
            (string) $mesa->id,
            $mesa->numero !== null ? (string) $mesa->numero : null,
        ]);

        $tienePedidosActivos = Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('mesa', $mesaValues)
            ->whereNotIn('estado', self::PEDIDO_ESTADOS_INACTIVOS)
            ->exists();

        if ($tienePedidosActivos) {
            return response()->json([
                'message' => 'No se puede eliminar la mesa porque tiene pedidos activos.',
            ], 422);
        }

        $mesa->delete();

        return response()->json([
            'message' => 'Mesa eliminada correctamente.',
        ]);
    }

    public function index(Request $request)
{
    try {
        // 🔥 FORZAMOS restaurant_id (temporal mientras no uses login multi-restaurante)
        $restaurantId = 1;
        $mesas = Mesa::query()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('numero')
            ->orderBy('id')
            ->get();

        $mesaKeys = $mesas
            ->flatMap(fn (Mesa $mesa) => array_filter([
                (string) $mesa->id,
                $mesa->numero !== null ? (string) $mesa->numero : null,
            ]))
            ->unique()
            ->values();

        $pedidosActivosPorMesa = Pedido::query()
            ->selectRaw('mesa, COUNT(*) as pedidos_activos_count')
            ->where('restaurant_id', $restaurantId)
            ->whereIn('mesa', $mesaKeys)
            ->whereNotIn('estado', self::PEDIDO_ESTADOS_INACTIVOS)
            ->groupBy('mesa')
            ->pluck('pedidos_activos_count', 'mesa');

        return response()->json([
            'data' => $mesas->map(function (Mesa $mesa) use ($pedidosActivosPorMesa) {
                $pedidosActivos = (int) (
                    ($pedidosActivosPorMesa[(string) $mesa->id] ?? 0)
                    + ($mesa->numero !== null ? ($pedidosActivosPorMesa[(string) $mesa->numero] ?? 0) : 0)
                );

                return [
                    'id' => (string) $mesa->id,
                    'numero' => $mesa->numero,
                    'codigo' => $mesa->numero ?: $mesa->id,
                    'display_name' => 'Mesa ' . ($mesa->numero ?: $mesa->id),
                    'pedidos_activos_count' => $pedidosActivos,
                    'estado' => $pedidosActivos > 0 ? 'ocupada' : 'libre',
                    'clientes_activos' => 0,
                ];
            })->values(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function show(Request $request, string $id): JsonResponse
    {
        $mesa = rawurldecode($id);
        $restaurantId = 1;

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
                'estado' => $pedidosPorCliente->flatten(1)->isNotEmpty() ? 'ocupada' : 'libre',
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

    public function pedidos(Request $request, string $mesaId): JsonResponse
    {
        $restaurantId = (int) ($request->user()->restaurant_id ?? 1);

        $pedidos = Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->where('mesa', rawurldecode($mesaId))
            ->with(['detalle.menuItem', 'cliente:id,nombres,apellidos'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $pedidos,
        ]);
    }

    private function transformPedido(Pedido $pedido): array
    {
        return [
            'id' => $pedido->id,
            'estado' => $pedido->estado,
            'total' => (float) $pedido->total,
            'created_at' => optional($pedido->created_at)?->toISOString(),
            'hold_expires_at' => optional($pedido->hold_expires_at)?->toISOString(),
            'can_be_edited' => $pedido->canBeEditedByWaiter(),
            'can_send_to_kitchen' => $pedido->canBeEditedByWaiter(),
            'cliente_id' => $pedido->cliente_id,
            'cliente' => [
                'id' => $pedido->cliente?->id,
                'nombre' => $pedido->cliente?->nombre,
            ],
            'cliente_nombre' => $pedido->cliente?->nombre ?? 'Cliente invitado',
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
    public function generarQR($id)
{
    $mesa = Mesa::findOrFail($id);

    // ⚠️ CAMBIA ESTA IP POR LA TUYA
    $url = "http://192.168.10.171:5174?mesa=" . $mesa->id;

    return QrCode::format('svg')
        ->size(300)
        ->generate($url);
}

}
