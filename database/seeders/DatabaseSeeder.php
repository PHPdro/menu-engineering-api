<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seeders na ordem correta (respeitando dependências)
        $this->call([
            IngredientSeeder::class,
            SupplierSeeder::class,
            IngredientPriceSeeder::class,
            BatchSeeder::class,
            DishSeeder::class,
            RecipeSeeder::class,
            RecipeItemSeeder::class,
            SaleSeeder::class, // Opcional - pode ser comentado se não quiser dados de vendas
        ]);
    }
}
