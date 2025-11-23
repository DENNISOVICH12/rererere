<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
    | ✅ API: LISTAR ITEMS (PAGINADO)
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        return response()->json(MenuItem::paginate(20));
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
]);

    if ($request->hasFile('imagen')) {
        $path = $request->file('imagen')->store('menu_items', 'public');
        $data['imagen'] = asset('storage/' . $path);
    }

    $data['restaurant_id'] = 1;

    MenuItem::create($data);

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
            'items'                 => ['required','array','min:1'],
            'items.*.nombre'        => ['required','string','max:120'],
            'items.*.descripcion'   => ['nullable','string'],
            'items.*.categoria'     => ['required','string'],
            'items.*.precio'        => ['numeric','min:0'],
            'items.*.imagen'        => ['nullable','string'],
            'items.*.disponible'    => ['boolean'],
            'items.*.restaurant_id' => ['nullable','integer'],
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
            'nombre'        => ['sometimes','string','max:120'],
            'descripcion'   => ['sometimes','nullable','string'],
            'categoria'     => ['sometimes','string'],
            'precio'        => ['sometimes','numeric','min:1'],
            'imagen'        => ['sometimes','nullable','string'],
            'disponible'    => ['sometimes','boolean'],
            'restaurant_id' => ['sometimes','integer'],
        ]);

        $item->update($data);
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

public function edit($id)
{
    $item = MenuItem::findOrFail($id);
    return view('admin.menu_edit', compact('item'));
}

}

