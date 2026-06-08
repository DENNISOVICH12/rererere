<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Pedido;
use App\Models\Comprobante;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $range = $this->resolveDateRange($request);
        $payload = $this->buildDashboardPayload($range['start'], $range['end'], $range['preset']);

        return view('admin.dashboard', [
            'initialRange' => [
                'preset' => $range['preset'],
                'start_date' => $range['start']->toDateString(),
                'end_date' => $range['end']->toDateString(),
                'label' => $range['label'],
            ],
            'initialData' => $payload,
        ]);
    }

    // ── Pedidos admin ─────────────────────────────────────────────────

    // ── Backup de base de datos / proyecto completo ───────────────────────

    public function backup(Request $request)
    {
        $tipo = $request->query('tipo', 'bd');

        $host     = config('database.connections.pgsql.host');
        $port     = config('database.connections.pgsql.port', 5432);
        $database = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        $timestamp = now()->format('Y-m-d_H-i-s');
        $sqlFile   = storage_path("app/backup_{$timestamp}.sql");

        // 1. Generar el dump de PostgreSQL
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s %s > %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($sqlFile)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($sqlFile) || filesize($sqlFile) === 0) {
            return response()->json([
                'error' => 'Error generando el dump de PostgreSQL. Verifica que pg_dump esté disponible.'
            ], 500);
        }

        // Solo base de datos
        if ($tipo === 'bd') {
            $filename = "backup_bd_{$timestamp}.sql";
            return response()->download($sqlFile, $filename, [
                'Content-Type' => 'application/octet-stream',
            ])->deleteFileAfterSend(true);
        }

        // Proyecto completo: código + BD en un ZIP
        $zipFile = storage_path("app/backup_completo_{$timestamp}.zip");
        $baseDir = base_path();

        $excludes = ['vendor', 'node_modules', '.git', 'storage/app/backup_'];

        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($sqlFile);
            return response()->json(['error' => 'No se pudo crear el archivo ZIP.'], 500);
        }

        // Agregar el dump de BD al zip
        $zip->addFile($sqlFile, "database/backup_{$timestamp}.sql");

        // Agregar archivos del proyecto (excepto carpetas pesadas)
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $filePath    = $file->getRealPath();
            $relativePath = substr($filePath, strlen($baseDir) + 1);

            // Excluir carpetas pesadas o irrelevantes
            $skip = false;
            foreach ($excludes as $ex) {
                if (str_starts_with($relativePath, $ex) || str_contains($relativePath, DIRECTORY_SEPARATOR . ltrim($ex, 'storage/app/'))) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile()) {
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
        @unlink($sqlFile);

        if (!file_exists($zipFile) || filesize($zipFile) === 0) {
            return response()->json(['error' => 'Error generando el ZIP.'], 500);
        }

        $filename = "backup_completo_{$timestamp}.zip";
        return response()->download($zipFile, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────────────────────

    public function pedidosIndex(Request $request)
    {
        $pedidos = Pedido::query()
            ->with(['mesa:id,numero', 'cliente:id,nombres,apellidos'])
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admin.pedidos.index', compact('pedidos'));
    }

    public function pedidoDetalle(Request $request, int $id)
    {
        $pedido = Pedido::with([
            'mesa:id,numero',
            'cliente:id,nombres,apellidos',
            'detalle.menuItem:id,nombre,precio,categoria',
        ])->findOrFail($id);

        $estados    = ['pendiente', 'preparando', 'listo', 'entregado', 'cancelado'];
        $menuItems  = MenuItem::where('disponible', true)
            ->orderBy('categoria')->orderBy('nombre')
            ->get(['id', 'nombre', 'precio', 'categoria']);

        $bloqueado  = in_array($pedido->estado, ['facturado', 'cancelado']);

        return view('admin.pedidos.detalle', compact('pedido', 'estados', 'menuItems', 'bloqueado'));
    }

    public function pedidoEditarItems(Request $request, int $id)
    {
        $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
            'items.*.cantidad'     => 'required|integer|min:1',
            'items.*.nota'         => 'nullable|string|max:255',
            'justificacion'        => 'required|string|min:5|max:500',
        ]);

        $pedido = Pedido::with('detalle')->findOrFail($id);

        if (in_array($pedido->estado, ['facturado', 'cancelado'])) {
            return back()->withErrors(['error' => 'Este pedido no puede modificarse.']);
        }

        $menuItems = MenuItem::whereIn('id',
            collect($request->items)->pluck('menu_item_id')
        )->get()->keyBy('id');

        DB::transaction(function () use ($pedido, $request, $menuItems) {
            // Guardar auditoría del cambio
            $pedido->update([
                'change_request_reason' => $request->justificacion,
                'change_requested_by'   => $request->user()?->id,
                'change_requested_at'   => now(),
                // Si estaba entregado, lo regresa a pendiente para que cocina reprocese
                'estado' => $pedido->estado === 'entregado' ? 'pendiente' : $pedido->estado,
            ]);

            // Reemplazar ítems
            $pedido->detalle()->delete();

            $detalles = [];
            $total    = 0;

            foreach ($request->items as $item) {
                $menuItem = $menuItems[$item['menu_item_id']] ?? null;
                if (!$menuItem) continue;

                $cantidad = (int) $item['cantidad'];
                $precio   = (float) $menuItem->precio;

                $detalles[] = [
                    'pedido_id'        => $pedido->id,
                    'menu_item_id'     => $menuItem->id,
                    'cantidad'         => $cantidad,
                    'precio_unitario'  => $precio,
                    'importe'          => $cantidad * $precio,
                    'nota'             => $item['nota'] ?? null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];

                $total += $cantidad * $precio;
            }

            DB::table('pedido_detalles')->insert($detalles);
            $pedido->update(['total' => $total]);
        });

        return redirect()
            ->route('admin.pedidos.detalle', $id)
            ->with('success', 'Pedido actualizado correctamente.');
    }

    public function pedidoCambiarEstado(Request $request, int $id)
    {
        $request->validate([
            'estado'        => 'required|string|in:pendiente,preparando,listo,entregado,cancelado',
            'justificacion' => 'nullable|string|max:500',
        ]);

        $pedido = Pedido::findOrFail($id);

        $updateData = ['estado' => $request->estado];

        if ($request->filled('justificacion')) {
            $updateData['change_request_reason']  = $request->justificacion;
            $updateData['change_requested_by']    = $request->user()?->id;
            $updateData['change_requested_at']    = now();
        }

        $pedido->update($updateData);

        return redirect()
            ->route('admin.pedidos.detalle', $id)
            ->with('success', 'Estado actualizado correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────

    // ── Todos los pedidos del rango (para "Ver todos" del dashboard) ──────

    public function dashboardPedidos(Request $request): JsonResponse
    {
        $range = $this->resolveDateRange($request);
        $start = $range['start'];
        $end   = $range['end'];

        $pedidos = Pedido::query()
            ->with(['mesa:id,numero', 'mesero:id,nombre,apellido', 'cliente:id,nombres,apellidos', 'detalle.menuItem:id,nombre,precio,categoria'])
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($o) => [
                'id'           => $o->id,
                'mesa_numero'  => $o->mesa?->numero ?? '-',
                'cliente'      => $o->cliente
                    ? trim(($o->cliente->nombres ?? '') . ' ' . ($o->cliente->apellidos ?? '')) ?: 'Invitado'
                    : 'Invitado',
                'mesero'       => $o->mesero
                    ? trim(($o->mesero->nombre ?? '') . ' ' . ($o->mesero->apellido ?? ''))
                    : '—',
                'estado'       => $o->estado,
                'total'        => (float) $o->total,
                'created_at'   => optional($o->created_at)->toIso8601String(),
                'bloqueado'    => in_array($o->estado, ['facturado', 'cancelado']),
                'change_request_reason' => $o->change_request_reason,
                'change_requested_at'   => optional($o->change_requested_at)?->toIso8601String(),
                'items'        => $o->detalle->map(fn ($d) => [
                    'id'           => $d->id,
                    'menu_item_id' => $d->menu_item_id,
                    'nombre'       => $d->menuItem?->nombre ?? "Ítem #{$d->menu_item_id}",
                    'cantidad'     => (int) $d->cantidad,
                    'precio'       => (float) ($d->menuItem?->precio ?? 0),
                    'importe'      => (float) $d->importe,
                    'nota'         => $d->nota,
                ])->values(),
            ]);

        $menuItems = MenuItem::where('disponible', true)
            ->orderBy('categoria')->orderBy('nombre')
            ->get(['id', 'nombre', 'precio', 'categoria']);

        return response()->json([
            'pedidos'    => $pedidos,
            'menu_items' => $menuItems,
        ]);
    }

    // ── Editar ítems de un pedido desde el dashboard ──────────────────────

    public function dashboardEditarPedido(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'items'                => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
                'items.*.cantidad'     => 'required|integer|min:1',
                'items.*.nota'         => 'nullable|string|max:255',
                'justificacion'        => 'required|string|min:5|max:500',
            ]);

            $pedido = Pedido::with('detalle')->findOrFail($id);

            if ($pedido->estado !== 'entregado') {
                return response()->json(['error' => 'Solo se pueden editar pedidos en estado entregado.'], 422);
            }

            $menuItems = MenuItem::whereIn('id',
                collect($request->items)->pluck('menu_item_id')
            )->get()->keyBy('id');

            DB::transaction(function () use ($pedido, $request, $menuItems) {
                $totalAnterior = (float) $pedido->total;

                $pedido->ajuste_pendiente_at   = now();
                $pedido->change_request_reason = $request->justificacion;
                $pedido->change_requested_by   = $request->user()?->id;
                $pedido->change_requested_at   = now();
                $pedido->save();

                $pedido->detalle()->delete();

                $detalles  = [];
                $total     = 0;
                $itemNames = [];

                foreach ($request->items as $item) {
                    $menuItem = $menuItems[$item['menu_item_id']] ?? null;
                    if (!$menuItem) continue;

                    $cantidad = (int) $item['cantidad'];
                    $precio   = (float) $menuItem->precio;

                    $detalles[] = [
                        'pedido_id'       => $pedido->id,
                        'menu_item_id'    => $menuItem->id,
                        'restaurant_id'   => $pedido->restaurant_id,
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $precio,
                        'importe'         => $cantidad * $precio,
                        'nota'            => $item['nota'] ?? null,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];

                    $itemNames[] = "{$cantidad}x {$menuItem->nombre}";
                    $total      += $cantidad * $precio;
                }

                DB::table('pedido_detalles')->insert($detalles);
                $pedido->total = $total;
                $pedido->save();

                // Registrar en historial de modificaciones
                \App\Models\AjusteComprobante::create([
                    'comprobante_id'      => null,
                    'pedido_id'           => $pedido->id,
                    'restaurant_id'       => $pedido->restaurant_id,
                    'admin_id'            => $request->user()?->id,
                    'tipo'                => 'edicion_pedido',
                    'item_nombre'         => implode(', ', $itemNames) ?: 'Sin ítems',
                    'item_cantidad'       => count($detalles),
                    'item_precio_unitario'=> $total / max(count($detalles), 1),
                    'monto_anulado'       => max(0, $totalAnterior - $total),
                    'justificacion'       => $request->justificacion,
                    'total_anterior'      => $totalAnterior,
                    'total_nuevo'         => $total,
                ]);
            });

            $pedido->refresh()->load('detalle.menuItem:id,nombre,precio,categoria');

            return response()->json([
                'ok'     => true,
                'pedido' => [
                    'id'     => $pedido->id,
                    'estado' => $pedido->estado,
                    'total'  => (float) $pedido->total,
                    'items'  => $pedido->detalle->map(fn ($d) => [
                        'id'           => $d->id,
                        'menu_item_id' => $d->menu_item_id,
                        'nombre'       => $d->menuItem?->nombre,
                        'cantidad'     => (int) $d->cantidad,
                        'precio'       => (float) ($d->menuItem?->precio ?? 0),
                        'importe'      => (float) $d->importe,
                        'nota'         => $d->nota,
                    ])->values(),
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validación: ' . implode(', ', array_merge(...array_values($e->errors())))], 422);
        } catch (\Throwable $e) {
            Log::error('dashboardEditarPedido error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function dashboardData(Request $request): JsonResponse
    {
        $range = $this->resolveDateRange($request);
        $payload = $this->buildDashboardPayload($range['start'], $range['end'], $range['preset']);

        return response()->json($payload);
    }

    private function buildDashboardPayload(Carbon $start, Carbon $end, string $preset): array
    {
        $ordersQuery = Pedido::query()->whereBetween('pedidos.created_at', [$start, $end]);

        $totalRevenue = (float) $ordersQuery->sum('total');
        $ordersCount = (int) (clone $ordersQuery)->count();
        $averageTicket = $ordersCount > 0 ? $totalRevenue / $ordersCount : 0;

        $tablesServed = (int) (clone $ordersQuery)
            ->whereNotNull('mesa_id')
            ->distinct('mesa_id')
            ->count('mesa_id');

        $avgOrderMinutes = (float) (clone $ordersQuery)
            ->whereNotNull('updated_at')
            ->whereRaw('updated_at > created_at')
            ->avg(DB::raw("EXTRACT(EPOCH FROM (updated_at - COALESCE(released_to_kitchen_at, created_at))) / 60"));

        $cancelledOrRetained = (int) (clone $ordersQuery)
            ->whereIn('estado', ['cancelado', 'retenido', 'modificacion_solicitada'])
            ->count();

        $dailyRows = (clone $ordersQuery)
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dayMap = $this->mapDays($start, $end, $dailyRows);

        $topProducts = DB::table('pedido_detalles as pd')
    ->join('pedidos as p', 'p.id', '=', 'pd.pedido_id')
    ->leftJoin('menu_items as mi', 'mi.id', '=', 'pd.menu_item_id')
    ->whereBetween('p.created_at', [$start, $end])
    ->selectRaw("
        COALESCE(mi.nombre, CONCAT('Producto #', pd.menu_item_id), 'Producto') as nombre
    ")
    ->selectRaw('SUM(pd.cantidad) as cantidad')
    ->selectRaw('SUM(pd.importe) as ingresos')
    ->groupBy('mi.nombre', 'pd.menu_item_id') // 🔥 FIX AQUÍ
    ->orderByDesc('cantidad')
    ->limit(5)
    ->get();

        $peakHours = (clone $ordersQuery)
            ->selectRaw('EXTRACT(HOUR FROM created_at) as hour, COUNT(*) as orders')
            ->groupBy('hour')
            ->orderByDesc('orders')
            ->limit(6)
            ->get();

        $mostUsedTables = (clone $ordersQuery)
            ->whereNotNull('mesa_id')
            ->join('mesas', 'pedidos.mesa_id', '=', 'mesas.id')
            ->selectRaw('pedidos.mesa_id, mesas.numero as mesa_numero, COUNT(*) as pedidos')
            ->groupBy('pedidos.mesa_id', 'mesas.numero')
            ->orderByDesc('pedidos')
            ->limit(5)
            ->get();

        $topRevenueTables = (clone $ordersQuery)
            ->whereNotNull('mesa_id')
            ->join('mesas', 'pedidos.mesa_id', '=', 'mesas.id')
            ->selectRaw('pedidos.mesa_id, mesas.numero as mesa_numero, SUM(pedidos.total) as ingresos')
            ->groupBy('pedidos.mesa_id', 'mesas.numero')
            ->orderByDesc('ingresos')
            ->limit(5)
            ->get();

        $avgKitchenMinutes = DB::table('pedido_detalles as pd')
            ->join('pedidos as p', 'p.id', '=', 'pd.pedido_id')
            ->whereBetween('p.created_at', [$start, $end])
            ->whereRaw('pd.updated_at > pd.created_at')
            ->avg(DB::raw("EXTRACT(EPOCH FROM (pd.updated_at - pd.created_at)) / 60"));

        $waiters = Usuario::query()
            ->where('rol', 'mesero')
            ->select('usuarios.id', 'usuarios.nombre', 'usuarios.apellido',
                DB::raw('COUNT(pedidos.id) as total_pedidos'))
            ->leftJoin('pedidos', function($join) use ($start, $end) {
                $join->on('pedidos.mesero_id', '=', 'usuarios.id')
                     ->whereBetween('pedidos.created_at', [$start, $end]);
            })
            ->groupBy('usuarios.id', 'usuarios.nombre', 'usuarios.apellido')
            ->orderByDesc('total_pedidos')
            ->limit(5)
            ->get()
            ->map(fn ($u) => [
                'nombre' => trim(($u->nombre ?? '') . ' ' . ($u->apellido ?? '')) ?: "Mesero #{$u->id}",
                'pedidos' => (int) $u->total_pedidos,
            ]);

        $recentOrders = (clone $ordersQuery)
            ->with(['mesa:id,numero', 'mesero:id,nombre,apellido', 'cliente', 'detalle.menuItem'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function ($order) {
                $client = $order->cliente
                    ? trim(($order->cliente->nombres ?? '') . ' ' . ($order->cliente->apellidos ?? ''))
                    : null;

                $mesero = $order->mesero;
                $meseroNombre = $mesero
                    ? trim(($mesero->nombre ?? '') . ' ' . ($mesero->apellido ?? ''))
                    : null;

                // Buscar comprobante si el pedido está facturado
                // Usa whereRaw para compatibilidad con JSON en PostgreSQL
                $comprobanteToken   = null;
                $comprobanteDetalle = null;
                if ($order->estado === 'facturado') {
                    $orderId = $order->id;
                    $comp = Comprobante::where('restaurant_id', $order->restaurant_id)
                        ->whereRaw("pedidos_ids::jsonb @> ?::jsonb", [json_encode([$orderId])])
                        ->latest()
                        ->first();
                    if ($comp) {
                        $comprobanteToken   = $comp->token;
                        $comprobanteDetalle = $comp->detalle;
                    }
                }

                return [
                    'id' => $order->id,
                    'mesa_id' => $order->mesa_id,
                    'mesa_numero' => $order->mesa?->numero,
                    'cliente' => $client ?: 'Invitado',
                    'mesero' => $meseroNombre ?: '—',
                    'estado' => $order->estado,
                    'total' => (float) $order->total,
                    'created_at' => optional($order->created_at)->toIso8601String(),
                    'bloqueado' => in_array($order->estado, ['facturado', 'cancelado']),
                    'comprobante_token'   => $comprobanteToken,
                    'comprobante_detalle' => $comprobanteDetalle,
                    'detalles' => $order->detalle->take(6)->map(function ($item) {
                        return [
                            'producto' => optional($item->menuItem)->nombre ?: "Ítem #{$item->menu_item_id}",
                            'cantidad' => (int) $item->cantidad,
                            'importe' => (float) $item->importe,
                        ];
                    })->values(),
                ];
            });

        return [
            'meta' => [
                'preset' => $preset,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'generated_at' => now()->toIso8601String(),
            ],
            'kpis' => [
                'revenue' => round($totalRevenue, 2),
                'orders' => $ordersCount,
                'average_ticket' => round($averageTicket, 2),
                'tables_served' => $tablesServed,
                'average_order_minutes' => round($avgOrderMinutes ?: 0, 1),
                'cancelled_or_retained' => $cancelledOrRetained,
            ],
            'charts' => [
                'days' => array_keys($dayMap),
                'daily_revenue' => array_values(array_map(fn ($r) => round((float) $r['revenue'], 2), $dayMap)),
                'daily_orders' => array_values(array_map(fn ($r) => (int) $r['orders'], $dayMap)),
                'top_products' => $topProducts,
                'peak_hours' => $peakHours,
            ],
            'operations' => [
                'most_used_tables' => $mostUsedTables,
                'top_revenue_tables' => $topRevenueTables,
                'top_waiters' => $waiters,
                'avg_kitchen_minutes' => round((float) ($avgKitchenMinutes ?: 0), 1),
            ],
            'insights' => $this->buildInsights($start, $end, $totalRevenue, $peakHours, $topProducts),
            'recent_orders' => $recentOrders,
        ];
    }

    private function mapDays(Carbon $start, Carbon $end, Collection $rows): array
    {
        $mapped = [];
        $indexed = $rows->keyBy('date');

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $date = $day->toDateString();
            $row = $indexed->get($date);
            $mapped[$date] = [
                'revenue' => $row ? (float) $row->revenue : 0,
                'orders' => $row ? (int) $row->orders : 0,
            ];
        }

        return $mapped;
    }

    private function buildInsights(Carbon $start, Carbon $end, float $currentRevenue, Collection $peakHours, Collection $topProducts): array
    {
        $days = max($start->diffInDays($end) + 1, 1);
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $start->copy()->subDays($days);

        $previousRevenue = (float) Pedido::query()
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('total');

        $insights = [];

        if ($previousRevenue > 0) {
            $delta = (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
            if ($delta <= -15) {
                $insights[] = [
                    'type' => 'danger',
                    'text' => 'Las ventas cayeron ' . abs(round($delta, 1)) . '% vs. el período anterior.',
                ];
            } elseif ($delta >= 15) {
                $insights[] = [
                    'type' => 'success',
                    'text' => 'Las ventas crecieron ' . round($delta, 1) . '% vs. el período anterior.',
                ];
            }
        }

        $peak = $peakHours->first();
        if ($peak) {
            $hour = str_pad((string) $peak->hour, 2, '0', STR_PAD_LEFT);
            $next = str_pad((string) (($peak->hour + 1) % 24), 2, '0', STR_PAD_LEFT);
            $insights[] = [
                'type' => 'warning',
                'text' => "Hora pico detectada: {$hour}:00 - {$next}:00 ({$peak->orders} pedidos).",
            ];
        }

        $topProduct = $topProducts->first();
        if ($topProduct) {
            $insights[] = [
                'type' => 'success',
                'text' => "Producto líder: {$topProduct->nombre} ({$topProduct->cantidad} unidades).",
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'type' => 'info',
                'text' => 'Aún no hay suficiente historial para generar alertas automáticas sólidas.',
            ];
        }

        return $insights;
    }

    private function resolveDateRange(Request $request): array
    {
        $preset = $request->string('preset')->toString() ?: 'today';
        $today = now();

        $start = $today->copy()->startOfDay();
        $end = $today->copy()->endOfDay();
        $label = 'Hoy';

        switch ($preset) {
            case 'yesterday':
                $start = $today->copy()->subDay()->startOfDay();
                $end = $today->copy()->subDay()->endOfDay();
                $label = 'Ayer';
                break;
            case 'last_7_days':
                $start = $today->copy()->subDays(6)->startOfDay();
                $end = $today->copy()->endOfDay();
                $label = 'Últimos 7 días';
                break;
            case 'last_30_days':
                $start = $today->copy()->subDays(29)->startOfDay();
                $end = $today->copy()->endOfDay();
                $label = 'Últimos 30 días';
                break;
            case 'custom':
                $startDate = $request->date('start_date');
                $endDate = $request->date('end_date');

                if ($startDate && $endDate) {
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($endDate)->endOfDay();
                    $label = "{$start->format('d/m/Y')} - {$end->format('d/m/Y')}";
                } else {
                    $preset = 'today';
                }
                break;
            default:
                $preset = 'today';
                break;
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return compact('preset', 'start', 'end', 'label');
    }
}