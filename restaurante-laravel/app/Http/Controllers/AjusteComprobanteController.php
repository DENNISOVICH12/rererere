<?php

namespace App\Http\Controllers;

use App\Models\AjusteComprobante;
use App\Models\Comprobante;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AjusteComprobanteController extends Controller
{
    /**
     * El mesero confirma que el cliente reconfirmó el comprobante ajustado.
     * Limpia el flag ajuste_pendiente_at en el comprobante y sus pedidos.
     */
    public function confirmarAjuste(Request $request, string $token)
    {
        $comprobante = Comprobante::where('token', $token)
            ->where('restaurant_id', Auth::user()->restaurant_id)
            ->firstOrFail();

        if (!$comprobante->tieneAjustePendiente()) {
            return response()->json(['ok' => true, 'mensaje' => 'No había ajuste pendiente.']);
        }

        DB::transaction(function () use ($comprobante) {
            $comprobante->ajuste_pendiente_at = null;
            $comprobante->save();

            Pedido::whereIn('id', $comprobante->pedidos_ids ?? [])
                ->update(['ajuste_pendiente_at' => null]);
        });

        return response()->json([
            'ok'     => true,
            'mensaje' => 'Ajuste confirmado. Ya puede proceder al pago.',
        ]);
    }

    /**
     * Confirmar ajuste cuando aún no existe comprobante (ajuste pre-pago).
     * Solo limpia ajuste_pendiente_at del pedido.
     */
    public function confirmarAjustePorPedido(Request $request, int $pedidoId)
    {
        try {
            $pedido = Pedido::findOrFail($pedidoId);
            $pedido->ajuste_pendiente_at = null;
            $pedido->save(); // guarda + actualiza updated_at
            return response()->json(['ok' => true, 'mensaje' => 'Ajuste confirmado.']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Listar ajustes de un comprobante específico.
     */
    public function index(string $token)
    {
        $comprobante = Comprobante::where('token', $token)
            ->where('restaurant_id', Auth::user()->restaurant_id)
            ->firstOrFail();

        $ajustes = AjusteComprobante::with('admin:id,nombre,apellido')
            ->where('comprobante_id', $comprobante->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'comprobante' => $comprobante,
            'ajustes'     => $ajustes,
        ]);
    }

    /**
     * Anular un ítem específico del comprobante.
     * El admin debe enviar justificación y el índice del ítem a anular.
     */
    public function anularItem(Request $request, string $token)
    {
        $request->validate([
            'item_index'    => 'required|integer|min:0',
            'justificacion' => 'required|string|min:10|max:500',
        ], [
            'item_index.required'    => 'Debes indicar el ítem a anular.',
            'justificacion.required' => 'La justificación es obligatoria.',
            'justificacion.min'      => 'La justificación debe tener al menos 10 caracteres.',
        ]);

        $admin = Auth::user();

        $comprobante = Comprobante::where('token', $token)
            ->where('restaurant_id', $admin->restaurant_id)
            ->firstOrFail();

        $detalle = $comprobante->detalle;
        $idx     = (int) $request->item_index;

        if (!isset($detalle[$idx])) {
            return response()->json(['error' => 'Ítem no encontrado en el comprobante.'], 404);
        }

        $item = $detalle[$idx];

        // Verificar que el ítem no haya sido ya anulado
        if (!empty($item['anulado'])) {
            return response()->json(['error' => 'Este ítem ya fue anulado anteriormente.'], 422);
        }

        $cantidad      = (int)   ($item['cantidad']        ?? 1);
        $precioUnit    = (float) ($item['precio_unitario'] ?? $item['precio'] ?? 0);
        $montoAnulado  = round($cantidad * $precioUnit, 2);
        $totalAnterior = (float) $comprobante->total;
        $totalNuevo    = round($totalAnterior - $montoAnulado, 2);

        DB::transaction(function () use (
            $comprobante, $detalle, $idx, $item,
            $admin, $request,
            $cantidad, $precioUnit, $montoAnulado,
            $totalAnterior, $totalNuevo
        ) {
            // Marcar ítem como anulado en el snapshot
            $detalle[$idx]['anulado']             = true;
            $detalle[$idx]['anulado_por']         = $admin->nombre . ' ' . $admin->apellido;
            $detalle[$idx]['anulado_at']          = now()->toDateTimeString();
            $detalle[$idx]['justificacion_anulacion'] = $request->justificacion;

            // Actualizar comprobante — marcar ajuste pendiente de reconfirmación
            $comprobante->detalle             = $detalle;
            $comprobante->total               = $totalNuevo;
            $comprobante->save();

            // Propagar el flag a los pedidos vinculados para bloquear "Marcar pagado"
            Pedido::whereIn('id', $comprobante->pedidos_ids ?? [])
                ->update(['ajuste_pendiente_at' => now()]);

            // Registrar en log de auditoría
            AjusteComprobante::create([
                'comprobante_id'      => $comprobante->id,
                'restaurant_id'       => $admin->restaurant_id,
                'admin_id'            => $admin->id,
                'item_nombre'         => $item['nombre'] ?? 'Ítem',
                'item_cantidad'       => $cantidad,
                'item_precio_unitario'=> $precioUnit,
                'monto_anulado'       => $montoAnulado,
                'justificacion'       => $request->justificacion,
                'total_anterior'      => $totalAnterior,
                'total_nuevo'         => $totalNuevo,
            ]);
        });

        return response()->json([
            'ok'            => true,
            'mensaje'       => 'Ítem anulado correctamente.',
            'total_nuevo'   => $totalNuevo,
            'monto_anulado' => $montoAnulado,
        ]);
    }

    /**
     * Historial de todos los ajustes del restaurante (para el dashboard admin).
     */
    public function historial(Request $request)
    {
        $restaurantId = Auth::user()->restaurant_id;

        $ajustes = AjusteComprobante::with([
                'admin:id,nombre,apellido',
                'comprobante:id,token,mesa_numero,pagado_at',
                'pedido:id,mesa_id',
            ])
            ->where('restaurant_id', $restaurantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($ajustes);
    }
}