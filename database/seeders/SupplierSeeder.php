<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Distribuidora Central',
                'contact_name' => 'João Silva',
                'email' => 'joao@distribuidoracentral.com.br',
                'phone' => '(11) 3456-7890',
                'notes' => 'Fornecedor principal de carnes e laticínios. Entrega às terças e quintas.',
            ],
            [
                'name' => 'Hortifruti Verde Vida',
                'contact_name' => 'Maria Santos',
                'email' => 'vendas@verdevida.com.br',
                'phone' => '(11) 3234-5678',
                'notes' => 'Especializado em frutas e verduras frescas. Entrega diária.',
            ],
            [
                'name' => 'Atacadão Alimentos',
                'contact_name' => 'Pedro Costa',
                'email' => 'pedro@atacadao.com.br',
                'phone' => '(11) 3123-4567',
                'notes' => 'Melhor preço para produtos não perecíveis. Entrega semanal.',
            ],
            [
                'name' => 'Bebidas Express',
                'contact_name' => 'Ana Paula',
                'email' => 'ana@bebidasexpress.com.br',
                'phone' => '(11) 3345-6789',
                'notes' => 'Fornecedor exclusivo de bebidas. Entrega sob demanda.',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}

