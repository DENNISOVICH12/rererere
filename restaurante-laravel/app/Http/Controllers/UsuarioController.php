<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Hash; // ✅ ESTA LÍNEA


class UsuarioController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/usuarios",
     *   tags={"Usuarios"},
     *   summary="Listar usuarios (paginado)",
     *   @OA\Response(response=200, description="OK",
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="data", type="array",
     *         @OA\Items(ref="#/components/schemas/Usuario")
     *       ),
     *       @OA\Property(property="current_page", type="integer"),
     *       @OA\Property(property="per_page", type="integer"),
     *       @OA\Property(property="total", type="integer")
     *     )
     *   )
     * )
     */
    public function index() 
{
    $usuarios = Usuario::paginate(20);
    return view('usuarios', compact('usuarios'));
}


    /**
     * @OA\Get(
     *   path="/api/usuarios/{id}",
     *   tags={"Usuarios"},
     *   summary="Ver usuario por ID",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/Usuario")),
     *   @OA\Response(response=404, description="Usuario no encontrado")
     * )
     */
    public function show($id)
    {
        return response()->json(Usuario::findOrFail($id));
    }

    /**
     * @OA\Post(
     *   path="/api/usuarios",
     *   tags={"Usuarios"},
     *   summary="Crear un nuevo usuario",
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       required={"usuario","password","nombre","correo","rol"},
     *       @OA\Property(property="usuario", type="string", example="admin"),
     *       @OA\Property(property="password", type="string", example="secreto123"),
     *       @OA\Property(property="nombre", type="string", example="Admin"),
     *       @OA\Property(property="apellido", type="string", nullable=true, example="Demo"),
     *       @OA\Property(property="correo", type="string", format="email", example="admin@example.com"),
     *       @OA\Property(property="rol", type="string", example="admin",
     *         description="admin|mesero|cocinero|cliente|empleado"),
     *       @OA\Property(property="activo", type="boolean", example=true),
     *       @OA\Property(property="restaurant_id", type="integer", example=1)
     *     )
     *   ),
     *   @OA\Response(response=201, description="Usuario creado", @OA\JsonContent(ref="#/components/schemas/Usuario")),
     *   @OA\Response(response=422, description="Validación fallida")
     * )
     */
    
    public function store(Request $request)
{
    $request->validate([
        'usuario' => 'required|string|unique:usuarios,usuario',
        'correo' => 'required|email|unique:usuarios,correo',
        'password' => 'required|min:6',
        'rol' => 'required|in:admin,mesero,cocinero,cliente',
    ]);

    $usuario = Usuario::create([
        'usuario' => $request->usuario,
        'correo' => $request->correo,
        'password' => Hash::make($request->password),
        'rol' => $request->rol,

        // ✅ Valores por defecto para evitar el error
        'nombre' => $request->nombre ?? ucfirst($request->usuario),
        'apellido' => $request->apellido ?? '',
        'activo' => $request->activo ?? true,
    ]);

    return response()->json([
        'message' => 'Usuario creado correctamente',
        'usuario' => $usuario
    ], 201);
}




    /**
     * @OA\Put(
     *   path="/api/usuarios/{id}",
     *   tags={"Usuarios"},
     *   summary="Actualizar usuario existente",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="usuario", type="string", example="admin2"),
     *       @OA\Property(property="password", type="string", example="nuevo123"),
     *       @OA\Property(property="nombre", type="string", example="Admin"),
     *       @OA\Property(property="apellido", type="string", nullable=true, example="Demo"),
     *       @OA\Property(property="correo", type="string", format="email", example="admin2@example.com"),
     *       @OA\Property(property="rol", type="string", example="gerente"),
     *       @OA\Property(property="activo", type="boolean", example=false),
     *       @OA\Property(property="restaurant_id", type="integer", example=1)
     *     )
     *   ),
     *   @OA\Response(response=200, description="Usuario actualizado", @OA\JsonContent(ref="#/components/schemas/Usuario")),
     *   @OA\Response(response=404, description="Usuario no encontrado"),
     *   @OA\Response(response=422, description="Validación fallida")
     * )
     */
    public function update(Request $request, $id)
    {
        $u = Usuario::findOrFail($id);

        $data = $request->validate([
            'usuario'       => ['sometimes','string','max:50', Rule::unique('usuarios','usuario')->ignore($u->id)],
            'password'      => ['sometimes','string','min:6'],
            'nombre'        => ['sometimes','string','max:120'],
            'apellido'      => ['sometimes','nullable','string','max:120'],
            'correo'        => ['sometimes','email','max:180', Rule::unique('usuarios','correo')->ignore($u->id)],
            'rol'           => ['sometimes','string'],
            'activo'        => ['sometimes','boolean'],
            'restaurant_id' => ['sometimes','integer'],
        ]);

        $u->update($data);
        return response()->json($u);
    }

    /**
     * @OA\Delete(
     *   path="/api/usuarios/{id}",
     *   tags={"Usuarios"},
     *   summary="Eliminar usuario",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Usuario eliminado"),
     *   @OA\Response(response=404, description="Usuario no encontrado")
     * )
     */
    public function destroy($id)
    {
        Usuario::findOrFail($id)->delete();
        return response()->json(['deleted' => true]);
    }
}
