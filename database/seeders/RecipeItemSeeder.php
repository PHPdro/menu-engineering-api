<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Database\Seeder;

class RecipeItemSeeder extends Seeder
{
    public function run(): void
    {
        $recipes = Recipe::with('dish')->get();
        $ingredients = Ingredient::all()->keyBy('name');

        foreach ($recipes as $recipe) {
            $dishName = $recipe->dish->name;
            
            // Define receitas baseadas no nome do prato
            $recipeItems = $this->getRecipeItemsForDish($dishName, $ingredients);
            
            foreach ($recipeItems as $item) {
                RecipeItem::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }
        }
    }

    private function getRecipeItemsForDish(string $dishName, $ingredients): array
    {
        $recipes = [
            'Hambúrguer Clássico' => [
                ['ingredient' => 'Pão de hambúrguer', 'quantity' => 1.0, 'notes' => 'Pão superior e inferior'],
                ['ingredient' => 'Carne moída', 'quantity' => 0.15, 'notes' => 'Hambúrguer de 150g'],
                ['ingredient' => 'Queijo mussarela', 'quantity' => 0.05],
                ['ingredient' => 'Alface', 'quantity' => 0.03],
                ['ingredient' => 'Tomate', 'quantity' => 0.04],
                ['ingredient' => 'Cebola', 'quantity' => 0.02],
                ['ingredient' => 'Ketchup', 'quantity' => 0.02],
                ['ingredient' => 'Maionese', 'quantity' => 0.02],
            ],
            'Hambúrguer com Bacon' => [
                ['ingredient' => 'Pão de hambúrguer', 'quantity' => 1.0],
                ['ingredient' => 'Carne moída', 'quantity' => 0.15],
                ['ingredient' => 'Bacon', 'quantity' => 0.05],
                ['ingredient' => 'Queijo cheddar', 'quantity' => 0.05],
                ['ingredient' => 'Alface', 'quantity' => 0.03],
                ['ingredient' => 'Tomate', 'quantity' => 0.04],
                ['ingredient' => 'Ketchup', 'quantity' => 0.02],
                ['ingredient' => 'Maionese', 'quantity' => 0.02],
            ],
            'Hambúrguer Duplo' => [
                ['ingredient' => 'Pão de hambúrguer', 'quantity' => 1.0],
                ['ingredient' => 'Carne moída', 'quantity' => 0.30, 'notes' => 'Dois hambúrgueres de 150g'],
                ['ingredient' => 'Queijo mussarela', 'quantity' => 0.10, 'notes' => 'Duas fatias'],
                ['ingredient' => 'Alface', 'quantity' => 0.03],
                ['ingredient' => 'Tomate', 'quantity' => 0.04],
                ['ingredient' => 'Cebola', 'quantity' => 0.02],
                ['ingredient' => 'Ketchup', 'quantity' => 0.03],
                ['ingredient' => 'Maionese', 'quantity' => 0.03],
            ],
            'Pizza Margherita' => [
                ['ingredient' => 'Massa de pizza', 'quantity' => 0.25, 'notes' => 'Massa para pizza média'],
                ['ingredient' => 'Queijo mussarela', 'quantity' => 0.15],
                ['ingredient' => 'Tomate', 'quantity' => 0.10, 'notes' => 'Molho de tomate'],
                ['ingredient' => 'Azeite de oliva', 'quantity' => 0.01],
            ],
            'Pizza 4 Queijos' => [
                ['ingredient' => 'Massa de pizza', 'quantity' => 0.25],
                ['ingredient' => 'Queijo mussarela', 'quantity' => 0.10],
                ['ingredient' => 'Queijo cheddar', 'quantity' => 0.08],
                ['ingredient' => 'Tomate', 'quantity' => 0.10],
                ['ingredient' => 'Azeite de oliva', 'quantity' => 0.01],
            ],
            'Frango Grelhado' => [
                ['ingredient' => 'Peito de frango', 'quantity' => 0.20, 'notes' => '200g de frango'],
                ['ingredient' => 'Arroz', 'quantity' => 0.15],
                ['ingredient' => 'Feijão', 'quantity' => 0.10],
                ['ingredient' => 'Alface', 'quantity' => 0.05],
                ['ingredient' => 'Tomate', 'quantity' => 0.05],
                ['ingredient' => 'Óleo de soja', 'quantity' => 0.01],
            ],
            'Refrigerante' => [
                ['ingredient' => 'Refrigerante cola', 'quantity' => 0.35, 'notes' => '350ml'],
            ],
            'Água Mineral' => [
                ['ingredient' => 'Água mineral', 'quantity' => 0.50, 'notes' => '500ml'],
            ],
        ];

        $items = $recipes[$dishName] ?? [];
        $result = [];

        foreach ($items as $item) {
            $ingredient = $ingredients[$item['ingredient']] ?? null;
            if ($ingredient) {
                $result[] = [
                    'ingredient_id' => $ingredient->id,
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ];
            }
        }

        return $result;
    }
}

