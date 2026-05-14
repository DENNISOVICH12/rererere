<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::orderByDesc('id')->paginate(20);
        $todos    = Usuario::orderBy('fecha_ingreso')->get();

        $roles = [
            'admin'    => 'Administrador',
            'mesero'   => 'Mesero',
            'cocinero' => 'Cocinero',
            'barra'    => 'Barra',
        ];

        $todosJson = $todos->map(fn($u) => [
    'id' => $u->id,
    'usuario' => $u->usuario,
    'nombre' => trim(($u->nombre ?? '').' '.($u->apellido ?? '')),
    'rol' => $u->rol,
    'activo' => (bool) $u->activo,
    'fecha_ingreso' => $u->fecha_ingreso ? (string) $u->fecha_ingreso : null,
    'fecha_salida' => $u->fecha_salida ? (string) $u->fecha_salida : null,
]);

return view('usuarios', compact('usuarios', 'todos', 'roles', 'todosJson'));
    }

    public function show($id)
    {
        return response()->json(Usuario::findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'usuario'       => 'required|string|max:50|unique:usuarios,usuario',
            'correo'        => 'required|email|max:180|unique:usuarios,correo',
            'password'      => 'required|min:6',
            'rol'           => 'required|in:admin,cocinero,mesero,barra',
            'nombre'        => 'nullable|string|max:120',
            'apellido'      => 'nullable|string|max:120',
            'activo'        => 'nullable|boolean',
            'fecha_ingreso' => 'nullable|date',
        ]);

        Usuario::create([
            'usuario'       => $data['usuario'],
            'correo'        => $data['correo'],
            'password'      => Hash::make($data['password']),
            'rol'           => $data['rol'],
            'nombre'        => $data['nombre'] ?? ucfirst($data['usuario']),
            'apellido'      => $data['apellido'] ?? '',
            'activo'        => $request->boolean('activo', true),
            'fecha_ingreso' => $data['fecha_ingreso'] ?? now()->toDateString(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Usuario creado correctamente'], 201);
        }

        return redirect()->route('usuarios.panel')->with('status', 'Empleado creado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $u = Usuario::findOrFail($id);

        $data = $request->validate([
            'usuario'       => ['sometimes', 'string', 'max:50', Rule::unique('usuarios', 'usuario')->ignore($u->id)],
            'password'      => ['sometimes', 'nullable', 'string', 'min:6'],
            'nombre'        => ['sometimes', 'nullable', 'string', 'max:120'],
            'apellido'      => ['sometimes', 'nullable', 'string', 'max:120'],
            'correo'        => ['sometimes', 'email', 'max:180', Rule::unique('usuarios', 'correo')->ignore($u->id)],
            'rol'           => ['sometimes', 'string', 'in:admin,cocinero,mesero,barra'],
            'activo'        => ['sometimes', 'boolean'],
            'restaurant_id' => ['sometimes', 'integer'],
            'fecha_ingreso' => ['sometimes', 'nullable', 'date'],
            'fecha_salida'  => ['sometimes', 'nullable', 'date'],
        ]);

        if (isset($data['password'])) {
            $data['password'] = $data['password'] ? Hash::make($data['password']) : $u->password;
        }

        if ($request->has('activo')) {
            $data['activo'] = $request->boolean('activo');
        }

        // Si se reactiva, limpiar fecha_salida si viene vacía
        if (isset($data['fecha_salida']) && $data['fecha_salida'] === '') {
            $data['fecha_salida'] = null;
        }

        $u->update($data);

        if ($request->wantsJson()) {
            return response()->json($u);
        }

        return redirect()->route('usuarios.panel')->with('status', 'Empleado actualizado correctamente.');
    }

    public function destroy(Request $request, $id)
    {
        Usuario::findOrFail($id)->delete();

        if ($request->wantsJson()) {
            return response()->json(['deleted' => true]);
        }

        return redirect()->route('usuarios.panel')->with('status', 'Usuario eliminado.');
    }
}