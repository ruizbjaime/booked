<?php

namespace Database\Seeders;

use App\Models\PricingCategory;
use Illuminate\Database\Seeder;

class PricingCategorySeeder extends Seeder
{
    public function run(): void
    {
        PricingCategory::upsert(
            $this->categories(),
            ['name'],
            ['en_name', 'es_name', 'level', 'color', 'multiplier', 'sort_order'],
        );
    }

    /**
     * @return list<array{name: string, en_name: string, es_name: string, level: int, color: string, multiplier: float, sort_order: int}>
     */
    private function categories(): array
    {
        return [
            ['name' => 'cat_1_premium', 'en_name' => 'Premium', 'es_name' => 'Premium', 'level' => 1, 'color' => '#DC2626', 'multiplier' => 2.50, 'sort_order' => 1],
            ['name' => 'cat_2_high', 'en_name' => 'High', 'es_name' => 'Alta', 'level' => 2, 'color' => '#F59E0B', 'multiplier' => 1.75, 'sort_order' => 2],
            ['name' => 'cat_3_weekend_std', 'en_name' => 'Standard Weekend', 'es_name' => 'Fin de Semana Estándar', 'level' => 3, 'color' => '#3B82F6', 'multiplier' => 1.25, 'sort_order' => 3],
            ['name' => 'cat_4_economy', 'en_name' => 'Economy', 'es_name' => 'Económica', 'level' => 4, 'color' => '#10B981', 'multiplier' => 1.00, 'sort_order' => 4],
        ];
    }
}
