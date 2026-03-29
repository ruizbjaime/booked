<?php

namespace Database\Seeders;

use App\Models\BathRoomType;
use Illuminate\Database\Seeder;

class BathRoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        BathRoomType::upsert(
            $this->types(),
            ['name'],
            ['en_name', 'es_name', 'description', 'sort_order'],
        );
    }

    /**
     * @return list<array{name: string, en_name: string, es_name: string, description: string, sort_order: int}>
     */
    private function types(): array
    {
        return [
            ['name' => 'full-bathroom', 'en_name' => 'Full Bathroom', 'es_name' => 'Baño completo', 'description' => 'Incluye ducha.', 'sort_order' => 1],
            ['name' => 'half-bathroom', 'en_name' => 'Half Bathroom', 'es_name' => 'Medio baño', 'description' => 'No incluye ducha.', 'sort_order' => 2],
        ];
    }
}
