<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use Illuminate\Http\Request;

class ComprobanteController extends Controller
{
    /**
     * Muestra el comprobante público por token.
     * No requiere login — accesible para el cliente.
     */
    public function show(string $token)
    {
        $comprobante = Comprobante::where('token', $token)
            ->with(['cliente', 'restaurant'])
            ->firstOrFail();

        return view('comprobante', compact('comprobante'));
    }

    /**
     * API: devuelve la URL del comprobante para el mesero.
     */
    public function url(int $clienteId)
    {
        $comprobante = Comprobante::where('cliente_id', $clienteId)
            ->latest()
            ->first();

        if (!$comprobante) {
            return response()->json(['ok' => false, 'message' => 'No hay comprobante para este cliente.'], 404);
        }

        return response()->json([
            'ok'  => true,
            'url' => route('comprobante.show', $comprobante->token),
        ]);
    }
}