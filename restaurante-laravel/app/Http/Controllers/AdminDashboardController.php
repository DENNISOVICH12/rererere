<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function dashboardData(Request $request): JsonResponse
    {
        $range = $this->resolveDateRange($request);
        $payload = $this->buildDashboardPayload($range['start'], $range['end'], $range['preset']);

        return response()->json($payload);
    }

    private function buildDashboardPayload(Carbon $start, Carbon $end, string $preset): array
    {
        $ordersQuery = Pedido::query()->whereBetween('created_at', [$start, $end]);

        $totalRevenue = (float) $ordersQuery->sum('total');
        $ordersCount = (int) (clone $ordersQuery)->count();
        $averageTicket = $ordersCount > 0 ? $totalRevenue / $ordersCount : 0;

        $tablesServed = (int) (clone $ordersQuery)
            ->whereNotNull('mesa')
            ->distinct('mesa')
            ->count('mesa');

        $avgOrderMinutes = (float) (clone $ordersQuery)
            ->whereNotNull('updated_at')
            ->whereRaw('updated_at > created_at')
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, COALESCE(released_to_kitchen_at, created_at), updated_at)'));

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
            ->selectRaw("COALESCE(mi.nombre, CONCAT('Producto #', pd.menu_item_id), 'Producto') as nombre")
            ->selectRaw('SUM(pd.cantidad) as cantidad')
            ->selectRaw('SUM(pd.importe) as ingresos')
            ->groupBy('nombre')
            ->orderByDesc('cantidad')
            ->limit(5)
            ->get();

        $peakHours = (clone $ordersQuery)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as orders')
            ->groupBy('hour')
            ->orderByDesc('orders')
            ->limit(6)
            ->get();

        $mostUsedTables = (clone $ordersQuery)
            ->whereNotNull('mesa')
            ->selectRaw('mesa, COUNT(*) as pedidos')
            ->groupBy('mesa')
            ->orderByDesc('pedidos')
            ->limit(5)
            ->get();

        $topRevenueTables = (clone $ordersQuery)
            ->whereNotNull('mesa')
            ->selectRaw('mesa, SUM(total) as ingresos')
            ->groupBy('mesa')
            ->orderByDesc('ingresos')
            ->limit(5)
            ->get();

        $avgKitchenMinutes = DB::table('pedido_detalles as pd')
            ->join('pedidos as p', 'p.id', '=', 'pd.pedido_id')
            ->whereBetween('p.created_at', [$start, $end])
            ->whereRaw('pd.updated_at > pd.created_at')
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, pd.created_at, pd.updated_at)'));

        $waiters = Usuario::query()
            ->where('rol', 'mesero')
            ->select('id', 'nombre', 'apellido')
            ->orderBy('nombre')
            ->limit(5)
            ->get()
            ->map(fn ($u) => [
                'nombre' => trim(($u->nombre ?? '') . ' ' . ($u->apellido ?? '')) ?: "Mesero #{$u->id}",
                'pedidos' => null,
            ]);

        $recentOrders = (clone $ordersQuery)
            ->with(['cliente', 'detalle.menuItem'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function ($order) {
                $client = $order->cliente
                    ? trim(($order->cliente->nombres ?? '') . ' ' . ($order->cliente->apellidos ?? ''))
                    : null;

                return [
                    'id' => $order->id,
                    'mesa' => $order->mesa,
                    'cliente' => $client ?: 'Invitado',
                    'estado' => $order->estado,
                    'total' => (float) $order->total,
                    'created_at' => optional($order->created_at)->toIso8601String(),
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
