<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class MenuItemController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ✅ VISTA PANEL ADMINISTRADOR
    |--------------------------------------------------------------------------
    */
    public function adminIndex(Request $request)
    {
        $items = MenuItem::orderBy('id', 'desc')->get();
        return view('admin.menu', compact('items'));
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ API: LISTAR ITEMS — carta digital (con caché Redis 5 min)
    |--------------------------------------------------------------------------
    | Antes: MenuItem::all() — SELECT * sin caché, cada QR = query completa
    | Ahora: caché 5 min + solo columnas necesarias = respuesta instantánea
    */
    public function index()
    {
        $data = MenuItem::select([
            'id', 'nombre', 'descripcion', 'categoria',
            'precio', 'image_path', 'imagen', 'disponible',
        ])
        ->where('disponible', true)
        ->orderBy('categoria')
        ->orderBy('nombre')
        ->get();

        return response()->json(['data' => $data]);
    }

    public function meseroMenuItems()
    {
        $data = (function() {
            return MenuItem::select([
                'id',
                'nombre',
                'descripcion',
                'categoria',
                'precio',
                'image_path',
                'imagen',
                'disponible',
            ])
            ->where('restaurant_id', 1)
            ->where('disponible', true)
            ->orderBy('categoria')
            ->get();
        });

        return response()->json(['data' => $data]);
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ API: CREAR ITEM
    |--------------------------------------------------------------------------
    */
    public function adminStore(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:120',
            'descripcion' => 'nullable|string|max:255',
            'categoria'   => 'required|in:plato,bebida',
            'precio'      => 'required|numeric|min:1',
            'imagen'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_path'  => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('menu_items', 'public');
            $data['image_path'] = $path;
            $data['imagen'] = asset('storage/' . $path);
        }

        $data['restaurant_id'] = 1;
        MenuItem::create($data);

        // Limpiar caché al crear un item nuevo
        Cache::forget('menu_items_public');
        Cache::forget('menu_items_mesero');

        return redirect()->back()->with('success', '✅ Plato creado exitosamente');
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ API: CREAR MULTIPLES ITEMS
    |--------------------------------------------------------------------------
    */
    public function storeBulk(Request $request)
    {
        $payload = $request->input('items') ?? $request->json()->all();

        if (!is_array($payload)) {
            return response()->json(['message' => 'Formato JSON inválido.'], 422);
        }

        $validator = Validator::make(['items' => $payload], [
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.nombre'        => ['required', 'string', 'max:120'],
            'items.*.descripcion'   => ['nullable', 'string'],
            'items.*.categoria'     => ['required', 'string'],
            'items.*.precio'        => ['numeric', 'min:0'],
            'items.*.imagen'        => ['nullable', 'string'],
            'items.*.image_path'    => ['nullable', 'string'],
            'items.*.disponible'    => ['boolean'],
            'items.*.restaurant_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $created = [];
        foreach ($payload as $row) {
            $row['disponible']    = $row['disponible'] ?? true;
            $row['restaurant_id'] = $row['restaurant_id'] ?? 1;
            $created[] = MenuItem::create($row);
        }

        Cache::forget('menu_items_public');
        Cache::forget('menu_items_mesero');

        return response()->json([
            'message' => 'Ítems creados correctamente',
            'count'   => count($created),
            'data'    => $created,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ API: VER ITEM
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        return response()->json(MenuItem::findOrFail($id));
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ API: ACTUALIZAR ITEM
    |--------------------------------------------------------------------------
    */
    public function adminupdate(Request $request, $id)
    {
        $item = MenuItem::findOrFail($id);

        $data = $request->validate([
            'nombre'        => ['sometimes', 'string', 'max:120'],
            'descripcion'   => ['sometimes', 'nullable', 'string'],
            'categoria'     => ['sometimes', 'string'],
            'precio'        => ['sometimes', 'numeric', 'min:1'],
            'imagen'        => ['sometimes', 'nullable', 'string'],
            'image_path'    => ['sometimes', 'nullable', 'string'],
            'disponible'    => ['sometimes', 'boolean'],
            'restaurant_id' => ['sometimes', 'integer'],
        ]);

        $item->update($data);

        // Limpiar caché al editar
        Cache::forget('menu_items_public');
        Cache::forget('menu_items_mesero');

        return response()->json($item);
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ API: ELIMINAR ITEM
    |--------------------------------------------------------------------------
    */
    public function adminDestroy($id)
    {
        $item = MenuItem::findOrFail($id);
        $item->delete();

        Cache::forget('menu_items_public');
        Cache::forget('menu_items_mesero');

        return redirect()->route('admin.menu')
            ->with('success', 'Plato eliminado correctamente');
    }

    public function panel(Request $request)
    {
        $query = MenuItem::query()->where('restaurant_id', 1);

        if ($request->filled('buscar')) {
            $query->where('nombre', 'LIKE', '%' . $request->buscar . '%');
        }

        $items = $query->paginate(10);

        return view('admin.menu', compact('items'));
    }


    /*
    |--------------------------------------------------------------------------
    | ✅ API: TOGGLE DISPONIBLE
    |--------------------------------------------------------------------------
    */
    public function toggleDisponible($id)
    {
        $item = MenuItem::findOrFail($id);
        $item->disponible = !$item->disponible;
        $item->save();

        Cache::forget('menu_items_public');
        Cache::forget('menu_items_mesero');

        return response()->json([
            'ok' => true,
            'disponible' => $item->disponible,
            'mensaje' => $item->disponible ? 'Producto habilitado' : 'Producto inhabilitado',
        ]);
    }

    public function edit($id)
    {
        $item = MenuItem::findOrFail($id);
        return view('admin.menu_edit', compact('item'));
    }
}