<?php

namespace Database\Seeders;

use App\Models\BathRoomType;
use Illuminate\Database\Seeder;

class BathRoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        BathRoomType::upsert(
            $this->bathRoomTypes(),
            ['name'],
            ['name_en', 'name_es', 'description', 'sort_order'],
        );
    }

    /**
     * @return list<array{name: string, name_en: string, name_es: string, description: string, sort_order: int}>
     */
    private function bathRoomTypes(): array
    {
        return [
            [
                'name' => 'full-bathroom',
                'name_en' => 'Full Bathroom',
                'name_es' => 'Baño completo',
                'description' => 'Incluye ducha.',
                'sort_order' => 1,
            ],
            [
                'name' => 'half-bathroom',
                'name_en' => 'Half Bathroom',
                'name_es' => 'Medio baño',
                'description' => 'No incluye ducha.',
                'sort_order' => 2,
            ],
        ];
    }
}
