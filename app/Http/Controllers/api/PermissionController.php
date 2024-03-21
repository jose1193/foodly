<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Super Admin')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $permissions = Permission::orderBy('id', 'DESC')->get();
        return response()->json(['permissions' => $permissions], 200);
    }

    public function create()
    {
        // Implementar lógica para la creación de un nuevo permiso (opcional)
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        $permission = Permission::create([
            'name' => $request->input('name'),
        ]);

        return response()->json(['message' => 'Permission created successfully'], 200);
    }

    public function show($id)
    {
        $permission = Permission::findOrFail($id);
        return response()->json(['permission' => $permission], 200);
    }

    public function edit($id)
    {
        // Implementar lógica para editar un permiso (opcional)
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $id,
        ]);

        $permission = Permission::findOrFail($id);
        $permission->name = $request->input('name');
        $permission->save();

        return response()->json(['message' => 'Permission updated successfully'], 200);
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json(['message' => 'Permission deleted successfully'], 200);
    }
}
