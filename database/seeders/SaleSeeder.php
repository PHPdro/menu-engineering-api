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

        // Cria vendas dos últimos 60 dias
        for ($day = 0; $day < 60; $day++) {
            $date = now()->subDays($day);
            
            // 5-15 vendas por dia
            $salesCount = rand(5, 15);
            
            for ($i = 0; $i < $salesCount; $i++) {
                $soldAt = $date->copy()
                    ->setHour(rand(11, 22))
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
                $itemsCount = rand(1, 4);
                $subtotal = 0;
                
                for ($j = 0; $j < $itemsCount; $j++) {
                    $dish = $dishes->random();
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
}

