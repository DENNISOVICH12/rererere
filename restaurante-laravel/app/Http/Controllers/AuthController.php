<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Cliente;
use Illuminate\Support\Facades\Hash;

use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ✅ Mostrar formulario de login (panel administrativo)
    public function showLogin()
    {
        return view('auth.login');
    }

    // ✅ Login para panel administrativo (admin / mesero / cocinero)
    public function authenticate(Request $request)
    {
        $request->validate([
            'usuario' => 'required',
            'password' => 'required'
        ]);

        if (!auth('web')->attempt([
            'usuario' => $request->usuario,
            'password' => $request->password
        ])) {
            return back()->withErrors(['usuario' => 'Credenciales incorrectas']);
        }

        $request->session()->regenerate();
        $user = auth('web')->user();

        return match ($user->rol) {
            'admin'    => redirect('/admin'),
            'cocinero' => redirect('/cocina'),
            'mesero'   => redirect('/meseros'),
            default    => redirect('/dashboard'),
        };
    }


    // ✅ *** LOGIN PARA CARTA DIGITAL (APP WEB DE CLIENTES) ***
    // ✅ LOGIN DE CLIENTES (CARTA DIGITAL)
public function loginCliente(Request $request)
{
    $request->validate([
        'usuario' => 'required|string',
        'password' => 'required|string',
    ]);

    $user = Usuario::where('usuario', $request->usuario)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Credenciales incorrectas'], 422);
    }

    return response()->json([
        'message' => 'Inicio de sesión exitoso',
        'cliente' => $user
    ], 200);
}




    // ✅ Registro de cliente desde carta digital

    public function registerCliente(Request $request)
{
    $validated = $request->validate([
        'usuario'   => 'required|string|unique:usuarios,usuario',
        'correo'    => 'required|email|unique:usuarios,correo',
        'password'  => 'required|string|min:4',
        'nombres'   => 'required|string',
        'apellidos' => 'required|string',
    ]);

    $user = Usuario::create([
        'usuario'      => $validated['usuario'],
        'correo'       => $validated['correo'],
        'password'     => Hash::make($validated['password']),
        'nombre'       => $validated['nombres'],   // ✅ MAPEADO CORRECTO
        'apellido'     => $validated['apellidos'], // ✅ MAPEADO CORRECTO
        'restaurant_id'=> $request->restaurant_id ?? 1   // ✅ AQUÍ ESTÁ E
    ]);

    return response()->json([
        'message' => 'Cliente registrado correctamente'
    ], 201);
}


    // ✅ Logout (solo para apps que usan tokens)
    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }


    // ✅ Crear usuarios del restaurante (admin)
    public function crearUsuario(Request $request)
    {
        $request->validate([
            'usuario' => 'required|unique:usuarios,usuario',
            'correo' => 'required|email|unique:usuarios,correo',
            'password' => 'required|min:6',
            'rol' => 'required|in:admin,mesero,cocinero',
            'nombre' => 'required|string',
            'apellido' => 'required|string',
            'restaurant_id' => 'required|integer|exists:restaurants,id'
        ]);

        $user = Usuario::create([
            'usuario' => $request->usuario,
            'correo' => $request->correo,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'restaurant_id' => $request->restaurant_id,
            'activo' => true,
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'data' => $user
        ], 201);
    }
}
