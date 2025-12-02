<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Ingredient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = Ingredient::where('is_perishable', true)->get();
        $nonPerishableIngredients = Ingredient::where('is_perishable', false)->get();
        $now = now();

        // Para ingredientes perecíveis: garante batches expirando
        foreach ($ingredients as $ingredient) {
            if (!$ingredient->shelf_life_days) {
                continue;
            }

            // Cria 2-4 lotes por ingrediente perecível
            $batchCount = rand(2, 4);
            
            // Garante que pelo menos 1-2 lotes expirem nas próximas 48h
            $expiringSoonCount = min(2, $batchCount);
            
            for ($i = 0; $i < $batchCount; $i++) {
                $quantity = $this->getQuantityForIngredient($ingredient);
                $unitCost = $this->getUnitCostForIngredient($ingredient);
                
                if ($i < $expiringSoonCount) {
                    // Lotes que expiram nas próximas 48h (para alertas)
                    $hoursUntilExpiry = rand(6, 48);
                    $expiresAt = $now->copy()->addHours($hoursUntilExpiry);
                    $receivedAt = $expiresAt->copy()->subDays($ingredient->shelf_life_days);
                } else {
                    // Outros lotes: distribuição aleatória (alguns já expirados, outros futuros)
                    $daysAgo = rand(0, min($ingredient->shelf_life_days * 2, 60));
                    $receivedAt = $now->copy()->subDays($daysAgo);
                    $expiresAt = $receivedAt->copy()->addDays($ingredient->shelf_life_days);
                    
                    // Se expirou há mais de 7 dias, não criar (ou criar com quantidade 0)
                    if ($expiresAt->lt($now->copy()->subDays(7))) {
                        $quantity = 0; // Lote já consumido/expirado
                    }
                }
                
                // Só cria se tiver quantidade
                if ($quantity > 0) {
                    Batch::create([
                        'ingredient_id' => $ingredient->id,
                        'quantity' => $quantity,
                        'received_at' => $receivedAt,
                        'expires_at' => $expiresAt,
                        'unit_cost' => $unitCost,
                    ]);
                }
            }
        }

        // Para ingredientes não perecíveis: cria 1-2 lotes
        foreach ($nonPerishableIngredients as $ingredient) {
            $batchCount = rand(1, 2);
            
            for ($i = 0; $i < $batchCount; $i++) {
                $quantity = $this->getQuantityForIngredient($ingredient);
                $unitCost = $this->getUnitCostForIngredient($ingredient);
                $receivedAt = $now->copy()->subDays(rand(0, 60));
                
                Batch::create([
                    'ingredient_id' => $ingredient->id,
                    'quantity' => $quantity,
                    'received_at' => $receivedAt,
                    'expires_at' => null,
                    'unit_cost' => $unitCost,
                ]);
            }
        }
    }

    private function getQuantityForIngredient(Ingredient $ingredient): float
    {
        $quantities = [
            'Carne moída' => [10.0, 15.0, 20.0],
            'Peito de frango' => [5.0, 8.0, 12.0],
            'Bacon' => [3.0, 5.0, 8.0],
            'Pão de hambúrguer' => [100.0, 150.0, 200.0],
            'Massa de pizza' => [5.0, 8.0, 10.0],
            'Queijo mussarela' => [5.0, 8.0, 10.0],
            'Queijo cheddar' => [3.0, 5.0, 8.0],
            'Manteiga' => [2.0, 3.0, 5.0],
            'Alface' => [5.0, 8.0, 10.0],
            'Tomate' => [10.0, 15.0, 20.0],
            'Cebola' => [10.0, 15.0, 20.0],
            'Pepino' => [5.0, 8.0, 10.0],
            'Ketchup' => [10.0, 15.0, 20.0],
            'Maionese' => [8.0, 12.0, 15.0],
            'Mostarda' => [5.0, 8.0, 10.0],
            'Óleo de soja' => [20.0, 30.0, 40.0],
            'Azeite de oliva' => [10.0, 15.0, 20.0],
            'Arroz' => [25.0, 30.0, 40.0],
            'Feijão' => [15.0, 20.0, 25.0],
            'Farinha de trigo' => [20.0, 25.0, 30.0],
            'Refrigerante cola' => [50.0, 75.0, 100.0],
            'Água mineral' => [100.0, 150.0, 200.0],
        ];

        $range = $quantities[$ingredient->name] ?? [5.0, 10.0, 15.0];
        return round(rand($range[0] * 100, $range[2] * 100) / 100, 2);
    }

    private function getUnitCostForIngredient(Ingredient $ingredient): float
    {
        $costs = [
            'Carne moída' => 45.00,
            'Peito de frango' => 18.00,
            'Bacon' => 35.00,
            'Pão de hambúrguer' => 0.50,
            'Massa de pizza' => 12.00,
            'Queijo mussarela' => 28.00,
            'Queijo cheddar' => 32.00,
            'Manteiga' => 25.00,
            'Alface' => 8.00,
            'Tomate' => 6.00,
            'Cebola' => 4.00,
            'Pepino' => 7.00,
            'Ketchup' => 15.00,
            'Maionese' => 18.00,
            'Mostarda' => 16.00,
            'Óleo de soja' => 8.00,
            'Azeite de oliva' => 35.00,
            'Arroz' => 6.00,
            'Feijão' => 8.00,
            'Farinha de trigo' => 5.00,
            'Refrigerante cola' => 4.50,
            'Água mineral' => 2.00,
        ];

        $baseCost = $costs[$ingredient->name] ?? 10.00;
        // Adiciona variação de ±10%
        $variation = $baseCost * 0.1;
        return round($baseCost + (rand(-100, 100) / 100 * $variation), 2);
    }
}

