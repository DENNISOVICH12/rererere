<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RolMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $rol)
    {
        // Validar que el usuario está autenticado
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // Comparar roles (admin, mesero, cocinero)
        if ($user->rol !== $rol) {
            return response()->json([
                'error' => 'Acceso denegado: se requiere rol ' . $rol
            ], 403);
        }

        return $next($request);
    }
}
    