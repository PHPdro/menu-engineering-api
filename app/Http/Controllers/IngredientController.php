<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/ingredients",
     *   tags={"Ingredients"},
     *   @OA\Response(response=200, description="Listar ingredientes")
     * )
     */
    public function index()
    {
        return Ingredient::query()->orderBy('name')->paginate(50);
    }

    /**
     * @OA\Post(
     *   path="/api/ingredients",
     *   tags={"Ingredients"},
     *   @OA\RequestBody(required=true),
     *   @OA\Response(response=201, description="Criado")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name',
            'unit' => 'required|string|max:32',
            'is_perishable' => 'boolean',
            'shelf_life_days' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|numeric|min:0',
        ]);
        $ingredient = Ingredient::create($data);
        return response()->json($ingredient, 201);
    }

    /**
     * @OA\Get(
     *   path="/api/ingredients/{id}",
     *   tags={"Ingredients"},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Detalhe")
     * )
     */
    public function show(Ingredient $ingredient)
    {
        return $ingredient->load(['prices.supplier', 'batches']);
    }

    /**
     * @OA\Put(
     *   path="/api/ingredients/{id}",
     *   tags={"Ingredients"},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true),
     *   @OA\Response(response=200, description="Atualizado")
     * )
     */
    public function update(Request $request, Ingredient $ingredient)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:ingredients,name,' . $ingredient->id,
            'unit' => 'sometimes|string|max:32',
            'is_perishable' => 'sometimes|boolean',
            'shelf_life_days' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|numeric|min:0',
        ]);
        $ingredient->update($data);
        return $ingredient;
    }

    /**
     * @OA\Delete(
     *   path="/api/ingredients/{id}",
     *   tags={"Ingredients"},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Removido")
     * )
     */
    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();
        return response()->noContent();
    }
}


