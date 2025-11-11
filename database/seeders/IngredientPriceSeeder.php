<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\IngredientPrice;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class IngredientPriceSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = Ingredient::all();
        $suppliers = Supplier::all();

        // Preços base por categoria de ingrediente
        $priceMap = [
            'Carne moída' => [45.00, 48.00, 50.00],
            'Peito de frango' => [18.00, 19.50, 20.00],
            'Bacon' => [35.00, 37.00, 38.00],
            'Pão de hambúrguer' => [0.50, 0.55, 0.60],
            'Massa de pizza' => [12.00, 13.00, 14.00],
            'Queijo mussarela' => [28.00, 30.00, 32.00],
            'Queijo cheddar' => [32.00, 34.00, 36.00],
            'Manteiga' => [25.00, 27.00, 28.00],
            'Alface' => [8.00, 9.00, 10.00],
            'Tomate' => [6.00, 7.00, 8.00],
            'Cebola' => [4.00, 4.50, 5.00],
            'Pepino' => [7.00, 8.00, 9.00],
            'Ketchup' => [15.00, 16.00, 17.00],
            'Maionese' => [18.00, 19.00, 20.00],
            'Mostarda' => [16.00, 17.00, 18.00],
            'Óleo de soja' => [8.00, 8.50, 9.00],
            'Azeite de oliva' => [35.00, 38.00, 40.00],
            'Arroz' => [6.00, 6.50, 7.00],
            'Feijão' => [8.00, 8.50, 9.00],
            'Farinha de trigo' => [5.00, 5.50, 6.00],
            'Refrigerante cola' => [4.50, 5.00, 5.50],
            'Água mineral' => [2.00, 2.20, 2.50],
        ];

        $validFrom = now()->subMonths(2);
        
        foreach ($ingredients as $ingredient) {
            $basePrices = $priceMap[$ingredient->name] ?? [10.00, 12.00, 15.00];
            
            // Cria preços para diferentes fornecedores
            foreach ($suppliers as $index => $supplier) {
                $price = $basePrices[$index % count($basePrices)];
                
                IngredientPrice::create([
                    'ingredient_id' => $ingredient->id,
                    'supplier_id' => $supplier->id,
                    'price' => $price,
                    'purchase_unit_quantity' => 1.0,
                    'purchase_unit' => $ingredient->unit,
                    'valid_from' => $validFrom->copy()->addDays($index * 7),
                    'valid_to' => null,
                ]);
            }
        }
    }
}

