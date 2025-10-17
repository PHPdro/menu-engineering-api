<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use Illuminate\Http\Request;

class DishController extends Controller
{
    /**
     * @OA\Get(path="/api/dishes", tags={"Dishes"}, @OA\Response(response=200, description="Lista"))
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
     * @OA\Post(path="/api/dishes", tags={"Dishes"}, @OA\RequestBody(required=true), @OA\Response(response=201, description="Criado"))
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
     * @OA\Get(path="/api/dishes/{id}", tags={"Dishes"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Detalhe"))
     */
    public function show(Dish $dish)
    {
        return $dish->load('recipes.items.ingredient');
    }

    /**
     * @OA\Put(path="/api/dishes/{id}", tags={"Dishes"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\RequestBody(required=true), @OA\Response(response=200, description="Atualizado"))
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
     * @OA\Delete(path="/api/dishes/{id}", tags={"Dishes"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=204, description="Removido"))
     */
    public function destroy(Dish $dish)
    {
        $dish->delete();
        return response()->noContent();
    }
}


