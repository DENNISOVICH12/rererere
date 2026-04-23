<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ClienteAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'correo' => 'required|email|unique:clientes,correo',
            'password' => 'required|min:6',
            'restaurant_id' => 'required|exists:restaurants,id',
            'telefono' => 'nullable|string|max:20',
            'dni' => 'nullable|string|max:255',
            'edad' => 'nullable|integer|min:1',
        ]);

        $cliente = Cliente::create([
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
            'correo' => $validated['correo'],
            'password' => Hash::make($validated['password']),
            'telefono' => $validated['telefono'] ?? null,
            'dni' => $validated['dni'] ?? null,
            'edad' => $validated['edad'] ?? null,
            'restaurant_id' => $validated['restaurant_id'],
            'activo' => true,
        ]);

        return response()->json([
            'message' => 'Cliente registrado correctamente.',
            'cliente' => $cliente,
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'correo' => 'required|email',
            'password' => 'required|string',
        ]);

        $cliente = Cliente::where('correo', $validated['correo'])->first();

        if (!$cliente || !Hash::check($validated['password'], $cliente->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = $cliente->createToken('carta-digital')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'cliente' => $cliente,
            'token' => $token,
        ]);
    }
}