<?php

namespace App\Http\Controllers;

use App\Models\RecipeItem;
use Illuminate\Http\Request;

class RecipeItemController extends Controller
{
    /**
     * @OA\Get(path="/api/recipe-items", tags={"Recipes"}, @OA\Response(response=200, description="Lista"))
     */
    public function index(Request $request)
    {
        $query = RecipeItem::with(['recipe.dish','ingredient']);
        if ($request->filled('recipe_id')) {
            $query->where('recipe_id', $request->integer('recipe_id'));
        }
        return $query->paginate(100);
    }

    /**
     * @OA\Post(path="/api/recipe-items", tags={"Recipes"}, @OA\RequestBody(required=true), @OA\Response(response=201, description="Criado"))
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string|max:512',
        ]);
        $item = RecipeItem::create($data);
        return response()->json($item->load(['recipe.dish','ingredient']), 201);
    }

    /**
     * @OA\Get(path="/api/recipe-items/{id}", tags={"Recipes"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Detalhe"))
     */
    public function show(RecipeItem $recipeItem)
    {
        return $recipeItem->load(['recipe.dish','ingredient']);
    }

    /**
     * @OA\Put(path="/api/recipe-items/{id}", tags={"Recipes"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\RequestBody(required=true), @OA\Response(response=200, description="Atualizado"))
     */
    public function update(Request $request, RecipeItem $recipeItem)
    {
        $data = $request->validate([
            'quantity' => 'sometimes|numeric|min:0.001',
            'notes' => 'nullable|string|max:512',
        ]);
        $recipeItem->update($data);
        return $recipeItem->load(['recipe.dish','ingredient']);
    }

    /**
     * @OA\Delete(path="/api/recipe-items/{id}", tags={"Recipes"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=204, description="Removido"))
     */
    public function destroy(RecipeItem $recipeItem)
    {
        $recipeItem->delete();
        return response()->noContent();
    }
}


