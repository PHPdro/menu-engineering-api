<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        return $query->paginate(10);
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
            // Campos opcionais para criar receita junto
            'recipe' => 'nullable|array',
            'recipe.version' => 'nullable|string|max:255',
            'recipe.items' => 'nullable|array',
            'recipe.items.*.ingredient_id' => 'required_with:recipe.items|exists:ingredients,id',
            'recipe.items.*.quantity' => 'required_with:recipe.items|numeric|min:0.001',
            'recipe.items.*.notes' => 'nullable|string|max:512',
        ]);

        return DB::transaction(function () use ($data) {
            // Criar o prato
            $dish = Dish::create([
                'name' => $data['name'],
                'sku' => $data['sku'] ?? null,
                'price' => $data['price'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Se receita foi fornecida, criar junto
            if (isset($data['recipe'])) {
                $recipe = Recipe::create([
                    'dish_id' => $dish->id,
                    'version' => $data['recipe']['version'] ?? 'v1',
                    'is_active' => true,
                ]);

                // Criar itens da receita
                if (isset($data['recipe']['items']) && is_array($data['recipe']['items'])) {
                    foreach ($data['recipe']['items'] as $item) {
                        RecipeItem::create([
                            'recipe_id' => $recipe->id,
                            'ingredient_id' => $item['ingredient_id'],
                            'quantity' => $item['quantity'],
                            'notes' => $item['notes'] ?? null,
                        ]);
                    }
                }
            }

            return response()->json($dish->load('recipe.items.ingredient'), 201);
        });
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
            // Campos opcionais para atualizar receita junto
            'recipe' => 'nullable|array',
            'recipe.version' => 'nullable|string|max:255',
            'recipe.items' => 'nullable|array',
            'recipe.items.*.ingredient_id' => 'required_with:recipe.items|exists:ingredients,id',
            'recipe.items.*.quantity' => 'required_with:recipe.items|numeric|min:0.001',
            'recipe.items.*.notes' => 'nullable|string|max:512',
        ]);

        return DB::transaction(function () use ($data, $dish) {
            // Atualizar dados do prato
            $updateData = [];
            if (isset($data['name'])) $updateData['name'] = $data['name'];
            if (isset($data['sku'])) $updateData['sku'] = $data['sku'];
            if (isset($data['price'])) $updateData['price'] = $data['price'];
            if (isset($data['is_active'])) $updateData['is_active'] = $data['is_active'];
            
            if (!empty($updateData)) {
                $dish->update($updateData);
            }

            // Se receita foi fornecida, atualizar ou criar
            if (isset($data['recipe'])) {
                $recipe = $dish->recipe ?? Recipe::create([
                    'dish_id' => $dish->id,
                    'version' => $data['recipe']['version'] ?? 'v1',
                    'is_active' => true,
                ]);

                // Atualizar versão se fornecida
                if (isset($data['recipe']['version'])) {
                    $recipe->update(['version' => $data['recipe']['version']]);
                }

                // Se itens foram fornecidos, substituir todos
                if (isset($data['recipe']['items']) && is_array($data['recipe']['items'])) {
                    // Deletar itens antigos
                    $recipe->items()->delete();

                    // Criar novos itens
                    foreach ($data['recipe']['items'] as $item) {
                        RecipeItem::create([
                            'recipe_id' => $recipe->id,
                            'ingredient_id' => $item['ingredient_id'],
                            'quantity' => $item['quantity'],
                            'notes' => $item['notes'] ?? null,
                        ]);
                    }
                }
            }

            return $dish->fresh()->load('recipe.items.ingredient');
        });
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


