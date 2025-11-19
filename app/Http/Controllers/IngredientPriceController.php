<?php

namespace App\Http\Controllers;

use App\Models\IngredientPrice;
use Illuminate\Http\Request;

class IngredientPriceController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/ingredient-prices",
     *   tags={"Suppliers"},
     *   summary="Listar preços de ingredientes",
     *   @OA\Parameter(
     *     name="ingredient_id",
     *     in="query",
     *     required=false,
     *     description="Filtrar por ID do ingrediente",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="supplier_id",
     *     in="query",
     *     required=false,
     *     description="Filtrar por ID do fornecedor",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Lista paginada de preços de ingredientes",
     *     @OA\JsonContent(
     *       allOf={
     *         @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *         @OA\Schema(
     *           @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/IngredientPrice"))
     *         )
     *       }
     *     )
     *   )
     * )
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
        return $query->paginate(10);
    }

    /**
     * @OA\Post(
     *   path="/api/ingredient-prices",
     *   tags={"Suppliers"},
     *   summary="Criar preço de ingrediente",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/IngredientPriceRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Preço de ingrediente criado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/IngredientPrice")
     *   ),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
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
     * @OA\Get(
     *   path="/api/ingredient-prices/{id}",
     *   tags={"Suppliers"},
     *   summary="Obter detalhes do preço de ingrediente",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do preço de ingrediente",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalhes do preço de ingrediente",
     *     @OA\JsonContent(ref="#/components/schemas/IngredientPrice")
     *   ),
     *   @OA\Response(response=404, description="Preço de ingrediente não encontrado")
     * )
     */
    public function show(IngredientPrice $ingredientPrice)
    {
        return $ingredientPrice->load(['ingredient','supplier']);
    }

    /**
     * @OA\Put(
     *   path="/api/ingredient-prices/{id}",
     *   tags={"Suppliers"},
     *   summary="Atualizar preço de ingrediente",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do preço de ingrediente",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/IngredientPriceRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Preço de ingrediente atualizado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/IngredientPrice")
     *   ),
     *   @OA\Response(response=404, description="Preço de ingrediente não encontrado"),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
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
     * @OA\Delete(
     *   path="/api/ingredient-prices/{id}",
     *   tags={"Suppliers"},
     *   summary="Excluir preço de ingrediente",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do preço de ingrediente",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=204, description="Preço de ingrediente excluído com sucesso"),
     *   @OA\Response(response=404, description="Preço de ingrediente não encontrado")
     * )
     */
    public function destroy(IngredientPrice $ingredientPrice)
    {
        $ingredientPrice->delete();
        return response()->noContent();
    }
}


