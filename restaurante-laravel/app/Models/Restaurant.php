<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $table = 'restaurants';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'slug',
        'wifi_ssid',
        'wifi_password',
        'wifi_security',
    ];
}