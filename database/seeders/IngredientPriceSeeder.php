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

        // Preços base por categoria de ingrediente (mínimo, médio, máximo)
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

        // Cria histórico de preços dos últimos 3 meses
        $startDate = now()->subMonths(3);
        
        foreach ($ingredients as $ingredient) {
            $basePrices = $priceMap[$ingredient->name] ?? [10.00, 12.00, 15.00];
            $minPrice = $basePrices[0];
            $maxPrice = $basePrices[2];
            
            // Para cada fornecedor, cria histórico de preços
            foreach ($suppliers as $supplierIndex => $supplier) {
                // Preço base varia por fornecedor (alguns mais baratos, outros mais caros)
                $supplierBasePrice = $basePrices[$supplierIndex % count($basePrices)];
                
                // Cria 6-8 preços históricos (um a cada 2 semanas aproximadamente)
                $priceCount = rand(6, 8);
                $currentDate = $startDate->copy();
                
                for ($i = 0; $i < $priceCount; $i++) {
                    // Varia o preço ao longo do tempo (±10% do preço base)
                    $variation = (rand(-100, 100) / 100) * ($supplierBasePrice * 0.1);
                    $price = max($minPrice * 0.8, min($maxPrice * 1.2, $supplierBasePrice + $variation));
                    $price = round($price, 2);
                    
                    // Define valid_from (data de início)
                    $validFrom = $currentDate->copy();
                    
                    // Define valid_to (data de fim) - próxima entrada ou null se for a última
                    $validTo = null;
                    if ($i < $priceCount - 1) {
                        // Próxima entrada em 10-20 dias
                        $daysUntilNext = rand(10, 20);
                        $validTo = $currentDate->copy()->addDays($daysUntilNext)->subDay();
                    }
                    
                    IngredientPrice::create([
                        'ingredient_id' => $ingredient->id,
                        'supplier_id' => $supplier->id,
                        'price' => $price,
                        'purchase_unit_quantity' => 1.0,
                        'purchase_unit' => $ingredient->unit,
                        'valid_from' => $validFrom,
                        'valid_to' => $validTo,
                    ]);
                    
                    // Avança para próxima data
                    $daysUntilNext = rand(10, 20);
                    $currentDate->addDays($daysUntilNext);
                }
            }
        }
    }
}

