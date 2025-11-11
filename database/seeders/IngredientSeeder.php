<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            // Carnes
            ['name' => 'Carne moída', 'unit' => 'kg', 'is_perishable' => true, 'shelf_life_days' => 2, 'min_stock' => 5.0],
            ['name' => 'Peito de frango', 'unit' => 'kg', 'is_perishable' => true, 'shelf_life_days' => 2, 'min_stock' => 3.0],
            ['name' => 'Bacon', 'unit' => 'kg', 'is_perishable' => true, 'shelf_life_days' => 5, 'min_stock' => 2.0],
            
            // Pães e Massas
            ['name' => 'Pão de hambúrguer', 'unit' => 'un', 'is_perishable' => true, 'shelf_life_days' => 3, 'min_stock' => 50.0],
            ['name' => 'Massa de pizza', 'unit' => 'kg', 'is_perishable' => true, 'shelf_life_days' => 2, 'min_stock' => 5.0],
            
            // Laticínios
            ['name' => 'Queijo mussarela', 'unit' => 'kg', 'is_perishable' => true, 'shelf_life_days' => 7, 'min_stock' => 3.0],
            ['name' => 'Queijo cheddar', 'unit' => 'kg', 'is_perishable' => true, 'shelf_life_days' => 7, 'min_stock' => 2.0],
            ['name' => 'Manteiga', 'unit' => 'kg', 'is_perishable' => true, 'shelf_life_days' => 30, 'min_stock' => 2.0],
            
            // Vegetais
            ['name' => 'Alface', 'unit' => 'kg', 'is_perishable' => true, 'shelf_life_days' => 3, 'min_stock' => 2.0],
            ['name' => 'Tomate', 'unit' => 'kg', 'is_perishable' => true, 'shelf_life_days' => 5, 'min_stock' => 3.0],
            ['name' => 'Cebola', 'unit' => 'kg', 'is_perishable' => true, 'shelf_life_days' => 14, 'min_stock' => 5.0],
            ['name' => 'Pepino', 'unit' => 'kg', 'is_perishable' => true, 'shelf_life_days' => 5, 'min_stock' => 2.0],
            
            // Condimentos e Molhos
            ['name' => 'Ketchup', 'unit' => 'kg', 'is_perishable' => false, 'shelf_life_days' => null, 'min_stock' => 5.0],
            ['name' => 'Maionese', 'unit' => 'kg', 'is_perishable' => false, 'shelf_life_days' => 60, 'min_stock' => 3.0],
            ['name' => 'Mostarda', 'unit' => 'kg', 'is_perishable' => false, 'shelf_life_days' => null, 'min_stock' => 2.0],
            
            // Óleos e Gorduras
            ['name' => 'Óleo de soja', 'unit' => 'L', 'is_perishable' => false, 'shelf_life_days' => null, 'min_stock' => 10.0],
            ['name' => 'Azeite de oliva', 'unit' => 'L', 'is_perishable' => false, 'shelf_life_days' => null, 'min_stock' => 5.0],
            
            // Grãos e Cereais
            ['name' => 'Arroz', 'unit' => 'kg', 'is_perishable' => false, 'shelf_life_days' => null, 'min_stock' => 20.0],
            ['name' => 'Feijão', 'unit' => 'kg', 'is_perishable' => false, 'shelf_life_days' => null, 'min_stock' => 10.0],
            ['name' => 'Farinha de trigo', 'unit' => 'kg', 'is_perishable' => false, 'shelf_life_days' => null, 'min_stock' => 15.0],
            
            // Bebidas
            ['name' => 'Refrigerante cola', 'unit' => 'L', 'is_perishable' => false, 'shelf_life_days' => null, 'min_stock' => 20.0],
            ['name' => 'Água mineral', 'unit' => 'L', 'is_perishable' => false, 'shelf_life_days' => null, 'min_stock' => 30.0],
        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::create($ingredient);
        }
    }
}

