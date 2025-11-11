<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use Illuminate\Http\Request;

class DishController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/dishes",
     *   tags={"Dishes"},
     *   summary="Listar pratos",
     *   @OA\Parameter(
     *     name="active_only",
     *     in="query",
     *     required=false,
     *     description="Filtrar apenas pratos ativos",
     *     @OA\Schema(type="boolean")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Lista paginada de pratos",
     *     @OA\JsonContent(
     *       allOf={
     *         @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *         @OA\Schema(
     *           @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Dish"))
     *         )
     *       }
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {
        $query = Dish::query()->with('recipe.items.ingredient')->orderBy('name');
        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }
        return $query->paginate(50);
    }

    /**
     * @OA\Post(
     *   path="/api/dishes",
     *   tags={"Dishes"},
     *   summary="Criar prato",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/DishRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Prato criado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/Dish")
     *   ),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:dishes,name',
            'sku' => 'nullable|string|max:255|unique:dishes,sku',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $dish = Dish::create($data);
        return response()->json($dish, 201);
    }

    /**
     * @OA\Get(
     *   path="/api/dishes/{id}",
     *   tags={"Dishes"},
     *   summary="Obter detalhes do prato",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do prato",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalhes do prato",
     *     @OA\JsonContent(ref="#/components/schemas/Dish")
     *   ),
     *   @OA\Response(response=404, description="Prato não encontrado")
     * )
     */
    public function show(Dish $dish)
    {
        return $dish->load('recipes.items.ingredient');
    }

    /**
     * @OA\Put(
     *   path="/api/dishes/{id}",
     *   tags={"Dishes"},
     *   summary="Atualizar prato",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do prato",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/DishRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Prato atualizado com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/Dish")
     *   ),
     *   @OA\Response(response=404, description="Prato não encontrado"),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function update(Request $request, Dish $dish)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:dishes,name,' . $dish->id,
            'sku' => 'nullable|string|max:255|unique:dishes,sku,' . $dish->id,
            'price' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);
        $dish->update($data);
        return $dish->load('recipe.items.ingredient');
    }

    /**
     * @OA\Delete(
     *   path="/api/dishes/{id}",
     *   tags={"Dishes"},
     *   summary="Excluir prato",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do prato",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=204, description="Prato excluído com sucesso"),
     *   @OA\Response(response=404, description="Prato não encontrado")
     * )
     */
    public function destroy(Dish $dish)
    {
        $dish->delete();
        return response()->noContent();
    }
}


