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
use Illuminate\Support\Facades\DB;



class MesaController extends Controller
{
    private const PEDIDO_ESTADOS_INACTIVOS = ['facturado', 'cancelado'];
    private const PEDIDO_ESTADOS_ACTIVOS = ['pendiente', 'preparando', 'listo'];

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

    $mesas = DB::table('mesas')
        ->leftJoin('pedidos', function ($join) use ($restaurantId) {
            $join->on('mesas.id', '=', 'pedidos.mesa_id')
                ->where('pedidos.restaurant_id', $restaurantId)
                ->whereIn('pedidos.estado', ['pendiente', 'preparando', 'listo', 'retenido', 'modificacion_solicitada']);
        })
        ->where('mesas.restaurant_id', $restaurantId)
        ->groupBy('mesas.id', 'mesas.numero', 'mesas.mesero_id')
        ->select(
            'mesas.id',
            'mesas.numero',
            'mesas.mesero_id',
            DB::raw('COUNT(pedidos.id) as pedidos_activos_count')
        )
        ->orderByRaw('mesas.numero::integer')
        ->get();

    return response()->json([
        'data' => $mesas->map(function ($mesa) {
            $mesero = $mesa->mesero_id
                ? \App\Models\Usuario::find($mesa->mesero_id)
                : null;

            return [
                'id' => $mesa->id,
                'numero' => $mesa->numero,
                'estado' => $mesa->pedidos_activos_count > 0 ? 'ocupada' : 'libre',
                'pedidos_activos_count' => (int) $mesa->pedidos_activos_count,
                'mesero_id' => $mesa->mesero_id,
                'mesero_nombre' => $mesero ? trim($mesero->nombre . ' ' . $mesero->apellido) : null,
            ];
        }),
    ]);
}

    public function show(Request $request, string $id): JsonResponse
{
    $mesaNumero = rawurldecode($id);
    $restaurantId = 1;

    // Resolver el ID real de la mesa a partir del número
    $mesaRecord = \App\Models\Mesa::query()
        ->where('restaurant_id', $restaurantId)
        ->where('numero', $mesaNumero)
        ->first();

    $mesaId = $mesaRecord?->id ?? (int) $mesaNumero;

    // 🔹 Clientes activos en la mesa
    $clientes = ClienteMesa::query()
        ->where('restaurant_id', $restaurantId)
        ->where('mesa', $mesaNumero)
        ->where('activo', true)
        ->orderBy('id')
        ->get();

    // 🔥 Pedidos OPTIMIZADOS (sin N+1 y sin duplicados)
    $pedidosPorCliente = Pedido::query()
        ->select([
            'id',
            'estado',
            'total',
            'created_at',
            'hold_expires_at',
            'cliente_id',
            'cliente_mesa_id'
        ])
        ->where('restaurant_id', $restaurantId)
        ->where('mesa_id', $mesaId)
        ->whereIn('estado', [
            'retenido',
            'modificacion_solicitada',
            'pendiente',
            'preparando',
            'listo'
        ])
        ->with([
            'detalle' => function ($q) {
                $q->select([
                    'id',
                    'pedido_id',
                    'menu_item_id',
                    'cantidad',
                    'precio_unitario',
                    'importe',
                    'grupo_servicio',
                    'estado_servicio',
                    'nota'
                ])->with([
                    'menuItem:id,nombre,categoria'
                ]);
            }
        ])
        ->get()
        ->groupBy('cliente_mesa_id');

    $mesaModel = $mesaRecord;
    $mesaModel?->load('mesero:id,nombre,apellido');

    return response()->json([
        'data' => [
            'id' => (int) $mesaNumero,
            'codigo' => $mesaNumero,
            'estado' => $pedidosPorCliente->flatten(1)->isNotEmpty() ? 'ocupada' : 'libre',
            'mesero_id' => $mesaModel?->mesero_id,
            'mesero_nombre' => $mesaModel?->mesero
                ? trim($mesaModel->mesero->nombre . ' ' . $mesaModel->mesero->apellido)
                : null,

            'clientes' => $clientes->map(function (ClienteMesa $cliente) use ($pedidosPorCliente) {

                $pedidos = $pedidosPorCliente->get($cliente->id, collect());

                return [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre ?: "Cliente #{$cliente->id}",

                    'pedidos' => $pedidos
                        ->map(fn (Pedido $pedido) => $this->transformPedido($pedido))
                        ->values(),

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

        $cliente = \App\Models\Cliente::query()
            ->where('restaurant_id', $restaurantId)
            ->findOrFail($id);

        $pedidos = Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->where('cliente_id', $cliente->id)
            ->whereNotIn('estado', ['facturado', 'cancelado'])
            ->get();

        if ($pedidos->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay pedidos pendientes de facturar para este cliente.',
            ], 400);
        }

        $hayPendientes = $pedidos->contains(
            fn ($p) => !in_array($p->estado, ['entregado', 'facturado', 'cancelado'])
        );

        if ($hayPendientes) {
            return response()->json([
                'ok' => false,
                'message' => 'No se puede cobrar mientras hay pedidos que aún no han sido entregados.',
            ], 400);
        }

        $total = (float) $pedidos->sum('total');

        Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->where('cliente_id', $cliente->id)
            ->whereNotIn('estado', ['facturado', 'cancelado'])
            ->update(['estado' => 'facturado']);

        // ── Generar comprobante ───────────────────────────────────────
        $pedidosConDetalle = Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->where('cliente_id', $cliente->id)
            ->where('estado', 'facturado')
            ->with(['detalle.menuItem', 'mesa'])
            ->orderBy('created_at')
            ->get();

        // Buscar mesero del primer pedido
        $meseroNombre = null;
        $meseroId = $pedidosConDetalle->first()?->mesero_id;
        if ($meseroId) {
            $mesero = \App\Models\Usuario::find($meseroId);
            $meseroNombre = $mesero ? trim($mesero->nombre . ' ' . $mesero->apellido) : null;
        }

        // Construir snapshot de ítems
        $detalleSnapshot = [];
        foreach ($pedidosConDetalle as $pedido) {
            foreach ($pedido->detalle as $item) {
                $nombre = $item->menuItem?->nombre ?? 'Ítem';
                $key = $nombre . '|' . ($item->nota ?? '');
                if (isset($detalleSnapshot[$key])) {
                    $detalleSnapshot[$key]['cantidad'] += (int) $item->cantidad;
                    $detalleSnapshot[$key]['subtotal'] += (float) $item->importe;
                } else {
                    $detalleSnapshot[$key] = [
                        'nombre'   => $nombre,
                        'cantidad' => (int) $item->cantidad,
                        'precio'   => (float) $item->precio_unitario,
                        'subtotal' => (float) $item->importe,
                        'nota'     => $item->nota ?? null,
                    ];
                }
            }
        }

        $mesaNumero = $pedidosConDetalle->first()?->mesa?->numero;

        $comprobante = \App\Models\Comprobante::create([
            'token'          => \App\Models\Comprobante::generarToken(),
            'cliente_id'     => $cliente->id,
            'restaurant_id'  => $restaurantId,
            'mesa_numero'    => $mesaNumero,
            'pedidos_ids'    => $pedidosConDetalle->pluck('id')->toArray(),
            'detalle'        => array_values($detalleSnapshot),
            'total'          => $total,
            'mesero_nombre'  => $meseroNombre,
            'pagado_at'      => now(),
        ]);

        // Marcar cliente como inactivo para que el próximo pedido cree uno nuevo
        \App\Models\Cliente::where('id', $cliente->id)
            ->update(['activo' => false]);

        return response()->json([
            'ok' => true,
            'message' => 'Comprobante generado correctamente.',
            'total' => $total,
            'pedidos' => $pedidos->pluck('id'),
            'comprobante_url' => route('comprobante.show', $comprobante->token),
            'comprobante_token' => $comprobante->token,
        ]);
    }
    
    public function pedidos(Request $request, string $mesaId): JsonResponse
    {
        $restaurantId = (int) ($request->user()->restaurant_id ?? 1);
        $mesaNumero = rawurldecode($mesaId);

        // Resolver el ID real de la mesa a partir del número
        $mesaRecord = \App\Models\Mesa::query()
            ->where('restaurant_id', $restaurantId)
            ->where('numero', $mesaNumero)
            ->first();

        $mesaRealId = $mesaRecord?->id ?? (int) $mesaNumero;

        $pedidos = Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->where('mesa_id', $mesaRealId)
            ->whereIn('estado', [
                'retenido',
                'modificacion_solicitada',
                'pendiente',
                'preparando',
                'listo',
                'entregado',
            ])
            ->with([
    'detalle:id,pedido_id,menu_item_id,cantidad,precio_unitario,importe,grupo_servicio,estado_servicio,nota',
    'detalle.menuItem:id,nombre,categoria',
    'cliente:id,nombres,apellidos'
])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $pedidos->map(fn (Pedido $pedido) => $this->transformPedido($pedido))->values(),
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

    public function asignarMesero(Request $request, string $mesaNumero): JsonResponse
    {
        $restaurantId = (int) ($request->user()->restaurant_id ?? 1);
        $usuarioId = (int) $request->user()->id;

        $mesa = \App\Models\Mesa::query()
            ->where('restaurant_id', $restaurantId)
            ->where('numero', rawurldecode($mesaNumero))
            ->first();

        if (!$mesa) {
            return response()->json(['ok' => false, 'message' => 'Mesa no encontrada.'], 404);
        }

        // Si ya tiene un mesero diferente asignado, devolver info para mostrar alerta
        if ($mesa->mesero_id && $mesa->mesero_id !== $usuarioId) {
            $meseroActual = \App\Models\Usuario::find($mesa->mesero_id);
            return response()->json([
                'ok' => false,
                'already_assigned' => true,
                'mesero_id' => $mesa->mesero_id,
                'mesero_nombre' => $meseroActual
                    ? trim($meseroActual->nombre . ' ' . $meseroActual->apellido)
                    : 'Otro mesero',
            ], 409);
        }

        $mesa->mesero_id = $usuarioId;
        $mesa->save();

        // Estampar mesero_id en todos los pedidos activos de esta mesa
        Pedido::query()
            ->where('restaurant_id', $restaurantId)
            ->where('mesa_id', $mesa->id)
            ->whereNotIn('estado', ['facturado', 'cancelado'])
            ->whereNull('mesero_id')
            ->update(['mesero_id' => $usuarioId]);

        $usuario = $request->user();

        return response()->json([
            'ok' => true,
            'message' => 'Mesa asignada correctamente.',
            'mesero_id' => $usuarioId,
            'mesero_nombre' => trim($usuario->nombre . ' ' . $usuario->apellido),
        ]);
    }

    public function liberarMesero(Request $request, string $mesaNumero): JsonResponse
    {
        $restaurantId = (int) ($request->user()->restaurant_id ?? 1);
        $usuarioId = (int) $request->user()->id;

        $mesa = \App\Models\Mesa::query()
            ->where('restaurant_id', $restaurantId)
            ->where('numero', rawurldecode($mesaNumero))
            ->first();

        if (!$mesa) {
            return response()->json(['ok' => false, 'message' => 'Mesa no encontrada.'], 404);
        }

        // Solo el mesero asignado o un admin puede liberar
        if ($mesa->mesero_id && $mesa->mesero_id !== $usuarioId) {
            $rol = $request->user()->rol ?? '';
            if ($rol !== 'admin') {
                return response()->json(['ok' => false, 'message' => 'No tienes permiso para liberar esta mesa.'], 403);
            }
        }

        $mesa->mesero_id = null;
        $mesa->save();

        return response()->json(['ok' => true, 'message' => 'Mesa liberada.']);
    }

    public function generarQR($id)
    {
        $mesa = Mesa::findOrFail($id);
        $restaurant = \App\Models\Restaurant::find(1);
        $host = request()->getHost();
        $cartaUrl = "http://{$host}:5174?mesa={$mesa->id}";

        $wifiSsid     = $restaurant->wifi_ssid ?? '';
        $wifiPassword = $restaurant->wifi_password ?? '';
        $wifiSecurity = $restaurant->wifi_security ?? 'WPA';

        if ($wifiSsid) {
            $wifiStr = $wifiSecurity === 'nopass'
                ? "WIFI:T:nopass;S:{$wifiSsid};;"
                : "WIFI:T:{$wifiSecurity};S:{$wifiSsid};P:{$wifiPassword};;";
        } else {
            $wifiStr = '';
        }

        $restaurantName = $restaurant->nombre ?? 'Restaurante';
        $mesaNumero     = $mesa->numero ?? $mesa->id;

        return response()->view('admin.mesa_qr', compact(
            'mesa', 'mesaNumero', 'restaurantName',
            'cartaUrl', 'wifiStr', 'wifiSsid'
        ))->header('Content-Type', 'text/html');
    }

}