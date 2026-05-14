<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'usuario',
        'password',
        'nombre',
        'apellido',
        'correo',
        'rol',
        'activo',
        'restaurant_id',
        'fecha_ingreso',
        'fecha_salida',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'activo'        => 'boolean',
        'fecha_ingreso' => 'date',
        'fecha_salida'  => 'date',
    ];
}