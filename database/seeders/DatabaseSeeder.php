<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Cria usuário diretamente (evita problema com Faker em produção)
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

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
