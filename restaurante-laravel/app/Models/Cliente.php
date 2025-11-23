<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Cliente extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'clientes';

    /**
     * Campos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'nombre_cliente',
        'telefono',
        'direccion',
        'fecha_registro'
    ];
        public $timestamps = false;


    /**
     * Relación con el restaurante.
     * Un cliente pertenece a un restaurante.
     */
    public function restaurante()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    /**
     * Relación con pedidos (si existe esa tabla).
     * Un cliente puede tener muchos pedidos.
     */
    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'cliente_id');
        return $this->hasMany(\App\Models\Pedido::class);
    }
    public function registerCliente(Request $request)
{
    $validated = $request->validate([
        'usuario'   => 'required|string|unique:usuarios,usuario',
        'correo'    => 'required|email|unique:usuarios,correo',
        'password'  => 'required|string|min:6',
        'nombre'    => 'required|string',
        'apellido'  => 'required|string',
        'dni'       => 'required|numeric|unique:clientes,dni',
        'edad'      => 'required|integer|min:1',
        'restaurant_id' => 'required|exists:restaurants,id'
    ]);

    // Crear usuario en tabla "usuarios"
    $usuario = \App\Models\Usuario::create([
        'usuario'       => $validated['usuario'],
        'correo'        => $validated['correo'],
        'password'      => bcrypt($validated['password']),
        'rol'           => 'cliente',
        'nombre'        => $validated['nombre'],
        'apellido'      => $validated['apellido'],
        'restaurant_id' => $validated['restaurant_id'],
        'activo'        => true
    ]);

    // Crear cliente en tabla "clientes"
    $cliente = \App\Models\Cliente::create([
        'nombres'       => $validated['nombre'],
        'apellidos'     => $validated['apellido'],
        'dni'           => $validated['dni'],
        'edad'          => $validated['edad'],
        'restaurant_id' => $validated['restaurant_id'],
    ]);

    // Generar token de sesión Sanctum
    $token = $usuario->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Cliente registrado correctamente',
        'usuario' => $usuario,
        'cliente' => $cliente,
        'token'   => $token
    ], 201);
}

}
