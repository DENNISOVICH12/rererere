<?php

namespace App\Http\Controllers;

use App\Models\ClienteMesa;
use App\Models\Pedido;
use App\Models\Mesa; // 🔥 NUEVO
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;


class MesaController extends Controller
{
    private const PEDIDO_ESTADOS_INACTIVOS = ['facturado', 'cancelado'];

    public function store(Request $request): JsonResponse
{
    try {
        Log::info('📥 [MESA] Intentando crear mesa', [
            'request' => $request->all()
        ]);

        $restaurantId = 1;

        $payload = $request->validate([
            'numero' => [
                'nullable',
                'integer',
                'min:1',
                Rule::unique('mesas', 'numero')->where(fn ($query) => 
                    $query->where('restaurant_id', $restaurantId)
                ),
            ],
        ]);

        Log::info('✅ [MESA] Validación OK', [
            'payload' => $payload
        ]);

        $numero = $payload['numero'] ?? null;

        if ($numero === null) {
            $ultimoNumero = (int) Mesa::query()
                ->where('restaurant_id', $restaurantId)
                ->max('numero');

            $numero = $ultimoNumero > 0 ? ($ultimoNumero + 1) : 1;

            Log::info('🔢 [MESA] Número autogenerado', [
                'numero' => $numero
            ]);
        }

        $mesa = Mesa::query()->create([
            'restaurant_id' => $restaurantId,
            'numero' => $numero,
            'estado' => 'libre',
        ]);

        Log::info('🎉 [MESA] Mesa creada correctamente', [
            'mesa_id' => $mesa->id,
            'numero' => $mesa->numero
        ]);

        return response()->json([
            'message' => 'Mesa creada correctamente.',
            'data' => [
                'id' => (string) $mesa->id,
                'numero' => $mesa->numero,
                'estado' => 'libre',
            ],
        ], 201);

    } catch (\Throwable $e) {

        Log::error('❌ [MESA] ERROR AL CREAR MESA', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'message' => 'No se pudo crear la mesa.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function destroy(Request $request, int $id): JsonResponse
    {
        $restaurantId = (int) ($request->user()->restaurant_id ?? 1);

        $mesa = Mesa::query()
            ->where('restaurant_id', $restaurantId)
            ->findOrFail($id);

        $tienePedidosActivos = Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->where('mesa_id', $mesa->id)
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
    $restaurantId = 1;

    $mesas = Mesa::query()
        ->where('restaurant_id', $restaurantId)
        ->orderBy('numero')
        ->orderBy('id')
        ->get();

    // 🔥 FORZAMOS SIEMPRE JSON (para admin)
    return response()->json([
        'data' => $mesas->map(function ($mesa) {
            return [
                'id' => $mesa->id,
                'numero' => $mesa->numero,
                'estado' => $mesa->estado ?? 'libre',
            ];
        })
    ]);
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
            ->where('mesa_id', (int) $mesa)
            ->whereIn('estado', ['retenido', 'modificacion_solicitada', 'pendiente', 'preparando', 'listo'])
            ->with(['mesa:id,numero', 'detalle.menuItem:id,nombre,categoria,precio']);

        $pedidosPorCliente = $pedidoQuery
            ->get()
            ->groupBy('cliente_mesa_id');

        return response()->json([
            'data' => [
                'id' => (int) $mesa,
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
            ->where('mesa_id', (int) rawurldecode($mesaId))
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
        $host = request()->getHost(); // solo IP sin puerto
    $url = "http://{$host}:5174?mesa=" . $mesa->id;
        return QrCode::format('svg')
            ->size(300)
            ->generate($url);
    }

}
