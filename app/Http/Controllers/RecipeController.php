<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/recipes",
     *   tags={"Recipes"},
     *   summary="Listar receitas",
     *   @OA\Parameter(
     *     name="dish_id",
     *     in="query",
     *     required=false,
     *     description="Filtrar por ID do prato",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Lista paginada de receitas",
     *     @OA\JsonContent(
     *       allOf={
     *         @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *         @OA\Schema(
     *           @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Recipe"))
     *         )
     *       }
     *     )
     *   )
     * )
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
     * @OA\Post(
     *   path="/api/recipes",
     *   tags={"Recipes"},
     *   summary="Criar receita",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/RecipeRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Receita criada com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/Recipe")
     *   ),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
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
     * @OA\Get(
     *   path="/api/recipes/{id}",
     *   tags={"Recipes"},
     *   summary="Obter detalhes da receita",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID da receita",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalhes da receita",
     *     @OA\JsonContent(ref="#/components/schemas/Recipe")
     *   ),
     *   @OA\Response(response=404, description="Receita não encontrada")
     * )
     */
    public function show(Recipe $recipe)
    {
        return $recipe->load('items.ingredient');
    }

    /**
     * @OA\Put(
     *   path="/api/recipes/{id}",
     *   tags={"Recipes"},
     *   summary="Atualizar receita",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID da receita",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/RecipeRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Receita atualizada com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/Recipe")
     *   ),
     *   @OA\Response(response=404, description="Receita não encontrada"),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
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
     * @OA\Delete(
     *   path="/api/recipes/{id}",
     *   tags={"Recipes"},
     *   summary="Excluir receita",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID da receita",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=204, description="Receita excluída com sucesso"),
     *   @OA\Response(response=404, description="Receita não encontrada")
     * )
     */
    public function destroy(Recipe $recipe)
    {
        $recipe->delete();
        return response()->noContent();
    }
}


