<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    /**
     * @OA\Get(path="/api/recipes", tags={"Recipes"}, @OA\Response(response=200, description="Lista"))
     */
    public function index(Request $request)
    {
        $query = Recipe::with('dish');
        if ($request->filled('dish_id')) {
            $query->where('dish_id', $request->integer('dish_id'));
        }
        return $query->paginate(50);
    }

    /**
     * @OA\Post(path="/api/recipes", tags={"Recipes"}, @OA\RequestBody(required=true), @OA\Response(response=201, description="Criado"))
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'dish_id' => 'required|exists:dishes,id',
            'version' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);
        // Optionally ensure only one active recipe per dish
        if (($data['is_active'] ?? false) === true) {
            Recipe::where('dish_id', $data['dish_id'])->update(['is_active' => false]);
        }
        $recipe = Recipe::create($data);
        return response()->json($recipe->load('dish'), 201);
    }

    /**
     * @OA\Get(path="/api/recipes/{id}", tags={"Recipes"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Detalhe"))
     */
    public function show(Recipe $recipe)
    {
        return $recipe->load('items.ingredient');
    }

    /**
     * @OA\Put(path="/api/recipes/{id}", tags={"Recipes"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\RequestBody(required=true), @OA\Response(response=200, description="Atualizado"))
     */
    public function update(Request $request, Recipe $recipe)
    {
        $data = $request->validate([
            'version' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);
        if (array_key_exists('is_active', $data) && $data['is_active'] === true) {
            Recipe::where('dish_id', $recipe->dish_id)->where('id', '!=', $recipe->id)->update(['is_active' => false]);
        }
        $recipe->update($data);
        return $recipe->load('items.ingredient');
    }

    /**
     * @OA\Delete(path="/api/recipes/{id}", tags={"Recipes"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=204, description="Removido"))
     */
    public function destroy(Recipe $recipe)
    {
        $recipe->delete();
        return response()->noContent();
    }
}


