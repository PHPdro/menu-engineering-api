<?php

namespace App\Http\Controllers;

use App\Models\RecipeItem;
use Illuminate\Http\Request;

class RecipeItemController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/recipe-items",
     *   tags={"Recipes"},
     *   summary="Listar itens de receita",
     *   @OA\Parameter(
     *     name="recipe_id",
     *     in="query",
     *     required=false,
     *     description="Filtrar por ID da receita",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Lista paginada de itens de receita",
     *     @OA\JsonContent(
     *       allOf={
     *         @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *         @OA\Schema(
     *           @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RecipeItem"))
     *         )
     *       }
     *     )
     *   )
     * )
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
     * @OA\Post(
     *   path="/api/recipe-items",
     *   tags={"Recipes"},
     *   summary="Criar item de receita",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/RecipeItemRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Item de receita criado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/RecipeItem")
     *   ),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
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
     * @OA\Get(
     *   path="/api/recipe-items/{id}",
     *   tags={"Recipes"},
     *   summary="Obter detalhes do item de receita",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do item de receita",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalhes do item de receita",
     *     @OA\JsonContent(ref="#/components/schemas/RecipeItem")
     *   ),
     *   @OA\Response(response=404, description="Item de receita não encontrado")
     * )
     */
    public function show(RecipeItem $recipeItem)
    {
        return $recipeItem->load(['recipe.dish','ingredient']);
    }

    /**
     * @OA\Put(
     *   path="/api/recipe-items/{id}",
     *   tags={"Recipes"},
     *   summary="Atualizar item de receita",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do item de receita",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/RecipeItemRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Item de receita atualizado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/RecipeItem")
     *   ),
     *   @OA\Response(response=404, description="Item de receita não encontrado"),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
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
     * @OA\Delete(
     *   path="/api/recipe-items/{id}",
     *   tags={"Recipes"},
     *   summary="Excluir item de receita",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do item de receita",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=204, description="Item de receita excluído com sucesso"),
     *   @OA\Response(response=404, description="Item de receita não encontrado")
     * )
     */
    public function destroy(RecipeItem $recipeItem)
    {
        $recipeItem->delete();
        return response()->noContent();
    }
}


