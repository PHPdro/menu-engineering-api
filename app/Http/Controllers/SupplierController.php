<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * @OA\Get(path="/api/suppliers", tags={"Suppliers"}, @OA\Response(response=200, description="Lista"))
     */
    public function index()
    {
        return Supplier::query()->orderBy('name')->paginate(50);
    }

    /**
     * @OA\Post(path="/api/suppliers", tags={"Suppliers"}, @OA\RequestBody(required=true), @OA\Response(response=201, description="Criado"))
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1024',
        ]);
        $supplier = Supplier::create($data);
        return response()->json($supplier, 201);
    }

    /**
     * @OA\Get(path="/api/suppliers/{id}", tags={"Suppliers"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Detalhe"))
     */
    public function show(Supplier $supplier)
    {
        return $supplier->load('ingredientPrices.ingredient');
    }

    /**
     * @OA\Put(path="/api/suppliers/{id}", tags={"Suppliers"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\RequestBody(required=true), @OA\Response(response=200, description="Atualizado"))
     */
    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:suppliers,name,' . $supplier->id,
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1024',
        ]);
        $supplier->update($data);
        return $supplier;
    }

    /**
     * @OA\Delete(path="/api/suppliers/{id}", tags={"Suppliers"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=204, description="Removido"))
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->noContent();
    }
}


