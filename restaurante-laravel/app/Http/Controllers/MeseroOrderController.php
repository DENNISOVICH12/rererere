<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Pedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class MeseroOrderController extends Controller
{
    private const BLOCKED_STATE = 'entregado';
    private const STRONG_CONFIRMATION_STATES = ['preparando', 'listo'];

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Pedido::query()
            ->select(['id', 'estado', 'created_at', 'updated_at', 'mesa', 'cliente_id'])
            ->whereIn('estado', ['pendiente', 'preparando', 'listo'])
            ->with([
                'cliente:id,nombre',
                'detalle' => fn ($detalleQuery) => $detalleQuery
                    ->select(['id', 'pedido_id', 'menu_item_id', 'cantidad', 'nota'])
                    ->with(['menuItem:id,nombre,categoria,precio']),
            ])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('estado', $status);
        }

        return response()->json([
            'data' => $query->get()->map(fn (Pedido $pedido) => $this->transformOrder($pedido)),
        ]);
    }

    public function show(Pedido $pedido): JsonResponse
    {
        $pedido->loadMissing([
            'cliente:id,nombre',
            'detalle.menuItem:id,nombre,categoria,precio',
        ]);

        return response()->json([
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function update(Request $request, Pedido $pedido): JsonResponse
    {
        if ($pedido->estado === self::BLOCKED_STATE) {
            return response()->json([
                'message' => 'El pedido entregado no se puede editar.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $force = $request->boolean('force_confirmation');

        if (in_array($pedido->estado, self::STRONG_CONFIRMATION_STATES, true) && !$force) {
            return response()->json([
                'message' => 'Este pedido ya está en preparación o listo. Confirma nuevamente para editarlo.',
                'requires_confirmation' => true,
            ], Response::HTTP_CONFLICT);
        }

        $validated = $request->validate([
            'mesa' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', Rule::exists('menu_items', 'id')],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.nota' => ['nullable', 'string', 'max:500'],
        ]);

        $menuItems = MenuItem::query()
            ->whereIn('id', collect($validated['items'])->pluck('menu_item_id')->all())
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($pedido, $validated, $menuItems) {
            $pedido->mesa = $validated['mesa'] ?? $pedido->mesa;
            $pedido->save();

            $pedido->detalle()->delete();

            $total = 0;

            foreach ($validated['items'] as $itemPayload) {
                $menuItem = $menuItems->get($itemPayload['menu_item_id']);
                if (!$menuItem) {
                    continue;
                }

                $cantidad = (int) $itemPayload['cantidad'];
                $precio = (float) $menuItem->precio;
                $importe = $cantidad * $precio;
                $total += $importe;

                $pedido->detalle()->create([
                    'restaurant_id' => $pedido->restaurant_id,
                    'menu_item_id' => $menuItem->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'importe' => $importe,
                    'nota' => $itemPayload['nota'] ?? null,
                ]);
            }

            $pedido->total = $total;
            $pedido->save();
        });

        $pedido->load(['cliente:id,nombre', 'detalle.menuItem:id,nombre,categoria,precio']);

        return response()->json([
            'message' => 'Pedido actualizado correctamente.',
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function destroy(Request $request, Pedido $pedido): JsonResponse
    {
        if ($pedido->estado === self::BLOCKED_STATE) {
            return response()->json([
                'message' => 'El pedido entregado no se puede cancelar.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $force = $request->boolean('force_confirmation');
        if (in_array($pedido->estado, self::STRONG_CONFIRMATION_STATES, true) && !$force) {
            return response()->json([
                'message' => 'Este pedido ya está en preparación o listo. Confirma nuevamente para cancelarlo.',
                'requires_confirmation' => true,
            ], Response::HTTP_CONFLICT);
        }

        $pedido->delete();

        return response()->json([
            'message' => 'Pedido cancelado correctamente.',
        ]);
    }

    public function menuItems(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $items = MenuItem::query()
            ->select(['id', 'nombre', 'categoria', 'precio'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nombre', 'like', "%{$search}%")
                        ->orWhere('categoria', 'like', "%{$search}%");
                });
            })
            ->where('disponible', true)
            ->orderBy('nombre')
            ->limit(25)
            ->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    private function transformOrder(Pedido $pedido): array
    {
        $items = $pedido->detalle->map(function ($detalle) {
            return [
                'id' => $detalle->id,
                'menu_item_id' => $detalle->menu_item_id,
                'cantidad' => (int) $detalle->cantidad,
                'nota' => $detalle->nota,
                'nombre' => $detalle->menuItem?->nombre,
                'categoria' => $detalle->menuItem?->categoria,
                'precio' => (float) ($detalle->menuItem?->precio ?? $detalle->precio_unitario ?? 0),
            ];
        })->values();

        return [
            'id' => $pedido->id,
            'estado' => $pedido->estado,
            'created_at' => optional($pedido->created_at)->toIso8601String(),
            'updated_at' => optional($pedido->updated_at)->toIso8601String(),
            'mesa' => $pedido->mesa,
            'cliente' => [
                'id' => $pedido->cliente?->id,
                'nombre' => $pedido->cliente?->nombre,
            ],
            'items_count' => $items->sum('cantidad'),
            'items' => $items,
        ];
    }
}
