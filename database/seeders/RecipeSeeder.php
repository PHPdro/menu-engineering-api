<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\Recipe;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $dishes = Dish::all();

        foreach ($dishes as $dish) {
            // Cria receita ativa para pratos ativos
            if ($dish->is_active) {
                Recipe::create([
                    'dish_id' => $dish->id,
                    'version' => 'v1.0',
                    'is_active' => true,
                ]);
            }
        }
    }
}

