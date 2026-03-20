<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        Platform::upsert(
            $this->platforms(),
            ['name'],
            ['en_name', 'es_name', 'color', 'sort_order', 'commission', 'commission_tax'],
        );
    }

    /**
     * @return list<array{name: string, en_name: string, es_name: string, color: string, sort_order: int, commission: float, commission_tax: float}>
     */
    private function platforms(): array
    {
        return [
            ['name' => 'direct', 'en_name' => 'Direct', 'es_name' => 'Directo', 'color' => 'purple', 'sort_order' => 1, 'commission' => 0, 'commission_tax' => 0],
            ['name' => 'airbnb', 'en_name' => 'Airbnb', 'es_name' => 'Airbnb', 'color' => '#ff5a5f', 'sort_order' => 2, 'commission' => 0.155, 'commission_tax' => 0.19],
            ['name' => 'booking', 'en_name' => 'Booking', 'es_name' => 'Booking', 'color' => '#003580', 'sort_order' => 3, 'commission' => 0.15, 'commission_tax' => 0.19],
            ['name' => 'fincas_de_la_villa', 'en_name' => 'Fincas de la Villa', 'es_name' => 'Fincas de la Villa', 'color' => 'green', 'sort_order' => 4, 'commission' => 0.10, 'commission_tax' => 0],
        ];
    }
}
