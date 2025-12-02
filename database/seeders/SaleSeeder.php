<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $dishes = Dish::where('is_active', true)->get();
        $channels = ['pos', 'delivery', 'balcão', 'mesa'];

        // Padrões de tráfego por hora (probabilidade de venda)
        $hourlyPattern = [
            8 => 0.1,  9 => 0.2,  10 => 0.3,  // Manhã - baixo
            11 => 0.8, 12 => 1.0, 13 => 1.0, 14 => 0.9, // Almoço - pico
            15 => 0.4, 16 => 0.5, 17 => 0.6, // Tarde - médio
            18 => 0.9, 19 => 1.0, 20 => 1.0, 21 => 0.8, // Jantar - pico
            22 => 0.3, 23 => 0.2, // Noite - baixo
        ];

        // Padrões por dia da semana (multiplicador)
        $weekdayMultiplier = [
            0 => 1.2, // Domingo - mais vendas
            1 => 0.8, // Segunda - menos vendas
            2 => 0.9, // Terça
            3 => 1.0, // Quarta
            4 => 1.1, // Quinta
            5 => 1.3, // Sexta - mais vendas
            6 => 1.4, // Sábado - mais vendas
        ];

        // Cria vendas dos últimos 60 dias
        for ($day = 0; $day < 60; $day++) {
            $date = now()->subDays($day);
            $weekday = (int) $date->format('w'); // 0 = domingo, 6 = sábado
            $multiplier = $weekdayMultiplier[$weekday] ?? 1.0;
            
            // Base de vendas por dia (ajustado pelo dia da semana)
            $baseSalesCount = (int) (10 * $multiplier);
            $salesCount = max(3, rand($baseSalesCount - 3, $baseSalesCount + 5));
            
            for ($i = 0; $i < $salesCount; $i++) {
                // Escolhe hora baseada no padrão
                $hour = $this->selectHourByPattern($hourlyPattern);
                $soldAt = $date->copy()
                    ->setHour($hour)
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));
                
                $channel = $channels[array_rand($channels)];
                
                // Cria a venda
                $sale = Sale::create([
                    'sold_at' => $soldAt,
                    'channel' => $channel,
                    'subtotal' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => 0,
                ]);
                
                // Adiciona 1-4 itens por venda
                // Alguns pratos são mais populares (mais vendidos)
                $itemsCount = rand(1, 4);
                $subtotal = 0;
                
                // Pratos mais populares (primeiros 3)
                $popularDishes = $dishes->take(3);
                $otherDishes = $dishes->skip(3);
                
                for ($j = 0; $j < $itemsCount; $j++) {
                    // 60% de chance de escolher prato popular
                    $dish = (rand(1, 100) <= 60 && $popularDishes->isNotEmpty())
                        ? $popularDishes->random()
                        : $dishes->random();
                    
                    $quantity = rand(1, 3);
                    $unitPrice = (float) $dish->price;
                    $totalPrice = $unitPrice * $quantity;
                    $subtotal += $totalPrice;
                    
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'dish_id' => $dish->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]);
                }
                
                // Atualiza totais da venda
                $sale->subtotal = $subtotal;
                $sale->total = $subtotal; // Simplificado, sem taxas/descontos
                $sale->save();
            }
        }
    }

    /**
     * Seleciona uma hora baseada no padrão de probabilidade
     */
    private function selectHourByPattern(array $pattern): int
    {
        // Normaliza probabilidades para somar 1.0
        $total = array_sum($pattern);
        $normalized = array_map(fn($p) => $p / $total, $pattern);
        
        // Gera número aleatório
        $rand = mt_rand() / mt_getrandmax();
        $cumulative = 0;
        
        foreach ($normalized as $hour => $prob) {
            $cumulative += $prob;
            if ($rand <= $cumulative) {
                return $hour;
            }
        }
        
        // Fallback: retorna hora do almoço
        return 12;
    }
}

