<?php

namespace App\Http\Controllers;

use App\Models\IngredientPrice;
use Illuminate\Http\Request;

class IngredientPriceController extends Controller
{
    /**
     * @OA\Get(path="/api/ingredient-prices", tags={"Suppliers"}, @OA\Response(response=200, description="Lista"))
     */
    public function index(Request $request)
    {
        $query = IngredientPrice::with(['ingredient', 'supplier'])->orderByDesc('valid_from');
        if ($request->filled('ingredient_id')) {
            $query->where('ingredient_id', $request->integer('ingredient_id'));
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }
        return $query->paginate(50);
    }

    /**
     * @OA\Post(path="/api/ingredient-prices", tags={"Suppliers"}, @OA\RequestBody(required=true), @OA\Response(response=201, description="Criado"))
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'price' => 'required|numeric|min:0',
            'purchase_unit_quantity' => 'required|numeric|min:0.001',
            'purchase_unit' => 'nullable|string|max:32',
            'valid_from' => 'required|date',
            'valid_to' => 'nullable|date|after:valid_from',
        ]);
        $price = IngredientPrice::create($data);
        return response()->json($price->load(['ingredient','supplier']), 201);
    }

    /**
     * @OA\Get(path="/api/ingredient-prices/{id}", tags={"Suppliers"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Detalhe"))
     */
    public function show(IngredientPrice $ingredientPrice)
    {
        return $ingredientPrice->load(['ingredient','supplier']);
    }

    /**
     * @OA\Put(path="/api/ingredient-prices/{id}", tags={"Suppliers"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\RequestBody(required=true), @OA\Response(response=200, description="Atualizado"))
     */
    public function update(Request $request, IngredientPrice $ingredientPrice)
    {
        $data = $request->validate([
            'price' => 'sometimes|numeric|min:0',
            'purchase_unit_quantity' => 'sometimes|numeric|min:0.001',
            'purchase_unit' => 'nullable|string|max:32',
            'valid_from' => 'sometimes|date',
            'valid_to' => 'nullable|date|after:valid_from',
        ]);
        $ingredientPrice->update($data);
        return $ingredientPrice->load(['ingredient','supplier']);
    }

    /**
     * @OA\Delete(path="/api/ingredient-prices/{id}", tags={"Suppliers"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=204, description="Removido"))
     */
    public function destroy(IngredientPrice $ingredientPrice)
    {
        $ingredientPrice->delete();
        return response()->noContent();
    }
}


