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
     *   summary="Listar ingredientes",
     *   @OA\Parameter(
     *     name="paginate",
     *     in="query",
     *     required=false,
     *     description="Se deve retornar com paginação (padrão: true). Use false para retornar todos os itens.",
     *     @OA\Schema(type="boolean", default=true)
     *   ),
     *   @OA\Parameter(
     *     name="per_page",
     *     in="query",
     *     required=false,
     *     description="Itens por página quando paginate=true (padrão: 10)",
     *     @OA\Schema(type="integer", default=10)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Lista de ingredientes (paginada ou não)",
     *     @OA\JsonContent(
     *       oneOf={
     *         @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *         @OA\Schema(
     *           type="array",
     *           @OA\Items(ref="#/components/schemas/Ingredient")
     *         )
     *       }
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {
        $query = Ingredient::query()->orderBy('name');
        
        // Se paginate=false ou não fornecido mas queremos todos, retorna sem paginação
        if ($request->boolean('paginate', true) === false) {
            return $query->get();
        }
        
        // Retorna com paginação
        $perPage = $request->integer('per_page', 10);
        return $query->paginate($perPage);
    }

    /**
     * @OA\Post(
     *   path="/api/ingredients",
     *   tags={"Ingredients"},
     *   summary="Criar ingrediente",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/IngredientRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Ingrediente criado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/Ingredient")
     *   ),
     *   @OA\Response(response=422, description="Erro de validação")
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
     *   summary="Obter detalhes do ingrediente",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do ingrediente",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalhes do ingrediente",
     *     @OA\JsonContent(ref="#/components/schemas/Ingredient")
     *   ),
     *   @OA\Response(response=404, description="Ingrediente não encontrado")
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
     *   summary="Atualizar ingrediente",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do ingrediente",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/IngredientRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Ingrediente atualizado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/Ingredient")
     *   ),
     *   @OA\Response(response=404, description="Ingrediente não encontrado"),
     *   @OA\Response(response=422, description="Erro de validação")
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
     *   summary="Excluir ingrediente",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do ingrediente",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=204, description="Ingrediente excluído com sucesso"),
     *   @OA\Response(response=404, description="Ingrediente não encontrado")
     * )
     */
    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();
        return response()->noContent();
    }
}


