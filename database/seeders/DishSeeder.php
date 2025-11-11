<?php

namespace Database\Seeders;

use App\Models\Dish;
use Illuminate\Database\Seeder;

class DishSeeder extends Seeder
{
    public function run(): void
    {
        $dishes = [
            [
                'name' => 'Hambúrguer Clássico',
                'sku' => 'HAMB-001',
                'price' => 29.90,
                'is_active' => true,
            ],
            [
                'name' => 'Hambúrguer com Bacon',
                'sku' => 'HAMB-002',
                'price' => 34.90,
                'is_active' => true,
            ],
            [
                'name' => 'Hambúrguer Duplo',
                'sku' => 'HAMB-003',
                'price' => 39.90,
                'is_active' => true,
            ],
            [
                'name' => 'Pizza Margherita',
                'sku' => 'PIZZ-001',
                'price' => 45.00,
                'is_active' => true,
            ],
            [
                'name' => 'Pizza 4 Queijos',
                'sku' => 'PIZZ-002',
                'price' => 52.00,
                'is_active' => true,
            ],
            [
                'name' => 'Frango Grelhado',
                'sku' => 'PRAT-001',
                'price' => 38.00,
                'is_active' => true,
            ],
            [
                'name' => 'Refrigerante',
                'sku' => 'BEB-001',
                'price' => 6.50,
                'is_active' => true,
            ],
            [
                'name' => 'Água Mineral',
                'sku' => 'BEB-002',
                'price' => 3.50,
                'is_active' => true,
            ],
            [
                'name' => 'Prato do Dia (Desativado)',
                'sku' => 'PRAT-999',
                'price' => 25.00,
                'is_active' => false,
            ],
        ];

        foreach ($dishes as $dish) {
            Dish::create($dish);
        }
    }
}

