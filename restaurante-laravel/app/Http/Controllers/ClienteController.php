<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    // --- Helpers para respuestas JSON ---
    private function ok(string $message, array $extra = []) {
        return response()->json(array_merge(['message' => $message], $extra), 200);
    }

    private function okData(string $message, $data, array $extra = []) {
        return response()->json(array_merge(['message' => $message, 'data' => $data], $extra), 200);
    }

    private function created(string $message, $data) {
        return response()->json(['message' => $message, 'data' => $data], 201);
    }

    private function notFound() {
        return response()->json(['error' => ['code' => 404, 'message' => 'No encontrado']], 404);
    }

    // --- GET: Listar clientes ---
    /**
     * @OA\Get(
     *   path="/api/clientes",
     *   tags={"Clientes"},
     *   summary="Listar todos los clientes",
     *   @OA\Response(response=200, description="Lista de clientes")
     * )
     */
    public function index(Request $request)
    {
        $query = Cliente::query();

        // Búsqueda opcional por nombre
        if ($search = $request->query('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(COALESCE(nombres,''), ' ', COALESCE(apellidos,'')) like ?", ["%{$search}%"])
                    ->orWhere('correo', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        $clientes = $query->orderBy('id', 'desc')->paginate(10);

        $meta = [
            'current_page' => $clientes->currentPage(),
            'per_page'     => $clientes->perPage(),
            'total'        => $clientes->total(),
            'last_page'    => $clientes->lastPage(),
        ];

        return $this->okData('Listado de clientes', $clientes->items(), ['meta' => $meta]);
    }

    // --- GET: Mostrar un cliente por ID ---
    /**
     * @OA\Get(
     *   path="/api/clientes/{id}",
     *   tags={"Clientes"},
     *   summary="Mostrar un cliente específico",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Cliente encontrado"),
     *   @OA\Response(response=404, description="Cliente no encontrado")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $cliente = Cliente::find($id);
        if (!$cliente) return $this->notFound();

        return $this->okData('Cliente encontrado', $cliente);
    }


    /**
     * Listado administrativo de clientes con métricas y filtros.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $currentDate = now();

        $validated = $request->validate([
            'search' => 'nullable|string|max:120',
            'registro_desde' => 'nullable|date',
            'registro_hasta' => 'nullable|date|after_or_equal:registro_desde',
            'ultima_visita_desde' => 'nullable|date',
            'segmento' => 'nullable|in:vip,frecuente,nuevo,inactivo',
            'nuevos_en' => 'nullable|in:7,30',
            'min_pedidos' => 'nullable|integer|min:0',
            'gasto_min' => 'nullable|numeric|min:0',
            'gasto_max' => 'nullable|numeric|min:0|gte:gasto_min',
            'sort' => 'nullable|in:nombre_asc,nombre_desc,registro_desc,registro_asc,pedidos_desc,pedidos_asc,gasto_desc,gasto_asc',
        ]);

        $query = Cliente::query()
            ->select('clientes.*')
            ->selectRaw('COALESCE(SUM(pedidos.total), 0) as total_gastado')
            ->selectRaw('COALESCE(pedidos_mes.total_mensual, 0) as total_mensual')
            ->selectRaw('COUNT(pedidos.id) as cantidad_pedidos')
            ->selectRaw('MAX(pedidos.created_at) as ultima_visita')
            ->leftJoin('pedidos', function ($join) {
                $join->on('pedidos.cliente_id', '=', 'clientes.id');
            })
            ->leftJoinSub(
                Pedido::query()
                    ->selectRaw('cliente_id, COALESCE(SUM(total), 0) as total_mensual')
                    ->whereNotNull('cliente_id')
                    ->whereYear('created_at', $currentDate->year)
                    ->whereMonth('created_at', $currentDate->month)
                    ->groupBy('cliente_id'),
                'pedidos_mes',
                function ($join) {
                    $join->on('pedidos_mes.cliente_id', '=', 'clientes.id');
                }
            )
            ->groupBy('clientes.id', 'pedidos_mes.total_mensual');

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($sub) use ($search) {
                $sub->whereRaw("CONCAT(COALESCE(clientes.nombres,''), ' ', COALESCE(clientes.apellidos,'')) like ?", ["%{$search}%"])
                    ->orWhere('clientes.correo', 'like', "%{$search}%");
            });
        }

        if (!empty($validated['registro_desde'])) {
            $query->whereDate('clientes.created_at', '>=', $validated['registro_desde']);
        }
        if (!empty($validated['registro_hasta'])) {
            $query->whereDate('clientes.created_at', '<=', $validated['registro_hasta']);
        }
        if (!empty($validated['ultima_visita_desde'])) {
            $query->havingRaw('MAX(pedidos.created_at) >= ?', [$validated['ultima_visita_desde']]);
        }
        if (!empty($validated['min_pedidos'])) {
            $query->havingRaw('COUNT(pedidos.id) >= ?', [(int) $validated['min_pedidos']]);
        }
        if (array_key_exists('gasto_min', $validated) && $validated['gasto_min'] !== null) {
            $query->havingRaw('COALESCE(SUM(pedidos.total), 0) >= ?', [(float) $validated['gasto_min']]);
        }
        if (array_key_exists('gasto_max', $validated) && $validated['gasto_max'] !== null) {
            $query->havingRaw('COALESCE(SUM(pedidos.total), 0) <= ?', [(float) $validated['gasto_max']]);
        }

        $sortMap = [
            'nombre_asc' => ['clientes.nombres', 'asc'],
            'nombre_desc' => ['clientes.nombres', 'desc'],
            'registro_desc' => ['clientes.created_at', 'desc'],
            'registro_asc' => ['clientes.created_at', 'asc'],
            'pedidos_desc' => [DB::raw('cantidad_pedidos'), 'desc'],
            'pedidos_asc' => [DB::raw('cantidad_pedidos'), 'asc'],
            'gasto_desc' => [DB::raw('total_gastado'), 'desc'],
            'gasto_asc' => [DB::raw('total_gastado'), 'asc'],
        ];
        [$field, $direction] = $sortMap[$validated['sort'] ?? 'nombre_asc'];
        $query->orderBy($field, $direction);

        $rows = $query->limit(250)->get();
        $now = $currentDate;

        $clientes = $rows
            ->map(function (Cliente $cliente) use ($now) {
                $totalGastado = round((float) ($cliente->total_gastado ?? 0), 2);
                $totalMensual = round((float) ($cliente->total_mensual ?? 0), 2);
                $cantidadPedidos = (int) ($cliente->cantidad_pedidos ?? 0);
                $promedio = $cantidadPedidos > 0 ? round($totalGastado / $cantidadPedidos, 2) : 0;
                $ultimaVisita = $cliente->ultima_visita ? Carbon::parse($cliente->ultima_visita) : null;
                $isVip = $totalMensual > 5000000;
                $tipo = $this->resolveTipoCliente(
                    $cliente->created_at,
                    $ultimaVisita,
                    $cantidadPedidos,
                    $totalGastado,
                    $now,
                );

                return [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'correo' => $cliente->correo,
                    'fecha_registro' => optional($cliente->created_at)->toDateTimeString(),
                    'total_gastado' => $totalGastado,
                    'total_mensual' => $totalMensual,
                    'cantidad_pedidos' => $cantidadPedidos,
                    'promedio' => $promedio,
                    'ultima_visita' => optional($ultimaVisita)->toDateTimeString(),
                    'vip' => $isVip,
                    'tipo_cliente' => $tipo,
                ];
            })
            ->filter(function (array $cliente) use ($validated, $now) {
                if (!empty($validated['nuevos_en'])) {
                    $days = (int) $validated['nuevos_en'];
                    $created = $cliente['fecha_registro'] ? Carbon::parse($cliente['fecha_registro']) : null;
                    if (!$created || $created->lt($now->copy()->subDays($days))) {
                        return false;
                    }
                }

                if (!empty($validated['segmento'])) {
                    if ($validated['segmento'] === 'vip') {
                        if (!$cliente['vip']) {
                            return false;
                        }
                    } elseif ($validated['segmento'] !== $cliente['tipo_cliente']) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        return $this->okData('Listado analítico de clientes', $clientes->all(), [
            'meta' => [
                'total' => $clientes->count(),
                'filtros_aplicados' => $validated,
            ],
        ]);
    }

    private function resolveTipoCliente($createdAt, $ultimaVisita, int $cantidadPedidos, float $totalGastado, Carbon $now): string
    {
        if ($cantidadPedidos >= 5) {
            return 'frecuente';
        }

        if ($createdAt && Carbon::parse($createdAt)->gte($now->copy()->subDays(30))) {
            return 'nuevo';
        }

        if ($ultimaVisita && Carbon::parse($ultimaVisita)->lte($now->copy()->subDays(45))) {
            return 'inactivo';
        }

        return 'ocasional';
    }

    /**
     * Historial y analítica de consumo para administración.
     */
    public function historial(Request $request, int $id): JsonResponse
    {
        $cliente = Cliente::find($id);

if (!$cliente) {
    // 🔥 CLIENTE INVITADO
    return $this->okData('Cliente invitado', [
        'cliente' => [
            'id' => null,
            'nombre' => 'Cliente Invitado',
            'correo' => null,
            'telefono' => null,
        ],
        'resumen' => [
            'total_gastado' => 0,
            'total_mensual' => 0,
            'cantidad_pedidos' => 0,
            'ticket_promedio' => 0,
            'ultima_visita' => null,
        ],
        'analisis' => [
            'frecuencia_visitas_dias' => null,
            'productos_top' => [],
        ],
        'vip' => false,
        'clasificacion' => 'ocasional',
        'historial' => [],
    ]);
}

        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'min_pedidos' => 'nullable|integer|min:1',
        ]);

        $ordersQuery = Pedido::query()
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('cliente_id');

        if (!empty($validated['date_from'])) {
            $ordersQuery->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $ordersQuery->whereDate('created_at', '<=', $validated['date_to']);
        }

        $pedidos = (clone $ordersQuery)
            ->with([
    'pedidoDetalles:id,pedido_id,menu_item_id,cantidad,precio_unitario,importe'
])
            ->orderByDesc('created_at')
            ->get();

        $cantidadPedidos = $pedidos->count();
        if (!empty($validated['min_pedidos']) && $cantidadPedidos < (int) $validated['min_pedidos']) {
            return $this->okData('Historial de cliente obtenido', [
                'cliente' => [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'correo' => $cliente->correo,
                    'telefono' => $cliente->telefono,
                ],
                'resumen' => [
                    'total_gastado' => 0,
                    'total_mensual' => 0,
                    'cantidad_pedidos' => $cantidadPedidos,
                    'ticket_promedio' => 0,
                    'ultima_visita' => null,
                ],
                'analisis' => [
                    'frecuencia_visitas_dias' => null,
                    'productos_top' => [],
                ],
                'vip' => false,
                'clasificacion' => 'ocasional',
                'historial' => [],
                'filtros_aplicados' => $validated,
            ]);
        }

        $totalGastado = (float) $pedidos->sum(fn (Pedido $pedido) => (float) $pedido->total);
        $totalMensual = (float) Pedido::query()
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('cliente_id')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total');
        $ticketPromedio = $cantidadPedidos > 0 ? round($totalGastado / $cantidadPedidos, 2) : 0.0;
        $ultimaVisita = optional($pedidos->first()?->created_at)->toDateTimeString();

        $primeraFecha = $pedidos->last()?->created_at;
        $ultimaFecha = $pedidos->first()?->created_at;
        $frecuenciaDias = null;
        if ($primeraFecha && $ultimaFecha && $cantidadPedidos > 1) {
            $diasRango = max($primeraFecha->diffInDays($ultimaFecha), 1);
            $frecuenciaDias = round($diasRango / ($cantidadPedidos - 1), 1);
        }

        $productosTop = (clone $ordersQuery)
    ->join('pedido_detalles', 'pedidos.id', '=', 'pedido_detalles.pedido_id')
    ->leftJoin('menu_items', 'pedido_detalles.menu_item_id', '=', 'menu_items.id')
    ->selectRaw("
        pedido_detalles.menu_item_id,
        COALESCE(menu_items.nombre, 'Producto eliminado') as producto,
        SUM(pedido_detalles.cantidad) as cantidad_total,
        SUM(pedido_detalles.importe) as total_producto
    ")
    ->groupBy('pedido_detalles.menu_item_id', 'menu_items.nombre')
    ->orderByDesc('cantidad_total')
    ->limit(5)
    ->get()
    ->map(fn ($item) => [
        'menu_item_id' => $item->menu_item_id,
        'producto' => $item->producto,
        'cantidad_total' => (int) $item->cantidad_total,
        'total_producto' => (float) $item->total_producto,
    ]);

        $isVip = $totalMensual > 5000000;

        $clasificacion = 'ocasional';
        if ($isVip) {
            $clasificacion = 'vip';
        } elseif ($cantidadPedidos >= 5 || $frecuenciaDias !== null && $frecuenciaDias <= 14) {
            $clasificacion = 'frecuente';
        }

        $historial = $pedidos->map(function (Pedido $pedido) {
            return [
                'id' => $pedido->id,
                'fecha' => optional($pedido->created_at)->format('Y-m-d'),
                'hora' => optional($pedido->created_at)->format('H:i:s'),
                'total' => (float) $pedido->total,
                'estado' => $pedido->estado,
                'mesa_id' => $pedido->mesa_id,
                'cliente_mesa_id' => $pedido->cliente_mesa_id,
                'productos' => $pedido->pedidoDetalles->map(function ($detalle) {
                    return [
                        'nombre' => $detalle->menuItem?->nombre ?? 'Producto no disponible',
                        'cantidad' => (int) $detalle->cantidad,
                        'precio' => (float) ($detalle->precio_unitario ?? 0),
                        'importe' => (float) ($detalle->importe ?? 0),
                    ];
                })->values(),
            ];
        })->values();

        return $this->okData('Historial de cliente obtenido', [
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'correo' => $cliente->correo,
                'telefono' => $cliente->telefono,
            ],
            'resumen' => [
                'total_gastado' => round($totalGastado, 2),
                'total_mensual' => round($totalMensual, 2),
                'cantidad_pedidos' => $cantidadPedidos,
                'ticket_promedio' => $ticketPromedio,
                'ultima_visita' => $ultimaVisita,
            ],
            'analisis' => [
                'frecuencia_visitas_dias' => $frecuenciaDias,
                'productos_top' => $productosTop,
            ],
            'vip' => $isVip,
            'clasificacion' => $clasificacion,
            'historial' => $historial,
            'filtros_aplicados' => $validated,
        ]);
    }

    // --- POST: Crear cliente ---
    /**
     * @OA\Post(
     *   path="/api/clientes",
     *   tags={"Clientes"},
     *   summary="Registrar un nuevo cliente",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"nombre_cliente"},
     *       @OA\Property(property="nombre_cliente", type="string", example="Juan Pérez"),
     *       @OA\Property(property="telefono", type="string", example="3001234567"),
     *       @OA\Property(property="direccion", type="string", example="Calle 10 #5-20")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Cliente creado exitosamente"),
     *   @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function store(Request $request): JsonResponse
{
    $data = $request->validate([
        'nombre_cliente' => 'required|string|max:255|unique:clientes,nombre_cliente',
        'telefono' => 'nullable|digits_between:7,15',
        'direccion' => 'nullable|string|max:255',
    ]);

    $cliente = Cliente::create($data);
    return $this->created('Cliente creado correctamente', $cliente);
}

    // --- PUT: Actualizar cliente ---
    /**
     * @OA\Put(
     *   path="/api/clientes/{id}",
     *   tags={"Clientes"},
     *   summary="Actualizar datos de un cliente",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="nombre_cliente", type="string", example="Carlos Gómez"),
     *       @OA\Property(property="telefono", type="string", example="3105559999"),
     *       @OA\Property(property="direccion", type="string", example="Av. Siempre Viva 742")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Cliente actualizado correctamente"),
     *   @OA\Response(response=404, description="Cliente no encontrado")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
{
    $cliente = Cliente::find($id);
    if (!$cliente) return $this->notFound();

    $data = $request->validate([
        'nombre_cliente' => 'sometimes|required|string|max:255',
        'telefono' => 'nullable|digits_between:7,15',
        'direccion' => 'sometimes|nullable|string|max:255',
    ]);

    $cliente->update($data);
    return $this->okData('Cliente actualizado correctamente', $cliente);
}

    // --- DELETE: Eliminar cliente ---
    /**
     * @OA\Delete(
     *   path="/api/clientes/{id}",
     *   tags={"Clientes"},
     *   summary="Eliminar un cliente",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Cliente eliminado correctamente"),
     *   @OA\Response(response=404, description="Cliente no encontrado")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $cliente = Cliente::find($id);
        if (!$cliente) return $this->notFound();

        if ($cliente->pedidos()->count() > 0) {
        return response()->json([
            'error' => 'No se puede eliminar un cliente con pedidos asociados'
        ], 409);
    }

        $cliente->delete();
        return $this->ok('Cliente eliminado correctamente');
    }
}
