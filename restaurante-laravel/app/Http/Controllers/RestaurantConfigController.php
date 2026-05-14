<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantConfigController extends Controller
{
    public function index()
    {
        $restaurant = Restaurant::findOrFail(1);
        return view('admin.config', compact('restaurant'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:255',
            'direccion'     => 'nullable|string|max:255',
            'telefono'      => 'nullable|string|max:50',
            'wifi_ssid'     => 'nullable|string|max:255',
            'wifi_password' => 'nullable|string|max:255',
            'wifi_security' => 'nullable|in:WPA,WEP,nopass',
        ]);

        Restaurant::where('id', 1)->update($data);

        return redirect()->route('admin.config')->with('status', 'Configuración guardada correctamente.');
    }
}