<?php

namespace Database\Seeders;

use App\Models\BedType;
use Illuminate\Database\Seeder;

class BedTypeSeeder extends Seeder
{
    public function run(): void
    {
        BedType::query()->where('name', 'sofa-bed')->delete();

        BedType::upsert(
            $this->types(),
            ['name'],
            ['en_name', 'es_name', 'bed_capacity', 'sort_order'],
        );
    }

    /**
     * @return list<array{name: string, en_name: string, es_name: string, bed_capacity: int, sort_order: int}>
     */
    private function types(): array
    {
        return [
            ['name' => 'single-bed', 'en_name' => 'Single Bed', 'es_name' => 'Cama sencilla', 'bed_capacity' => 1, 'sort_order' => 1],
            ['name' => 'twin-bed', 'en_name' => 'Twin Bed', 'es_name' => 'Cama semidoble', 'bed_capacity' => 2, 'sort_order' => 2],
            ['name' => 'double-bed', 'en_name' => 'Double Bed', 'es_name' => 'Cama doble', 'bed_capacity' => 2, 'sort_order' => 3],
            ['name' => 'full-bed', 'en_name' => 'Full Bed', 'es_name' => 'Cama matrimonial', 'bed_capacity' => 2, 'sort_order' => 4],
            ['name' => 'queen-bed', 'en_name' => 'Queen Bed', 'es_name' => 'Cama queen', 'bed_capacity' => 2, 'sort_order' => 5],
            ['name' => 'king-bed', 'en_name' => 'King Bed', 'es_name' => 'Cama king', 'bed_capacity' => 2, 'sort_order' => 6],
            ['name' => 'california-king-bed', 'en_name' => 'California King Bed', 'es_name' => 'Cama king californiana', 'bed_capacity' => 2, 'sort_order' => 7],
            ['name' => 'sofa-bed-single', 'en_name' => 'Single Sofa Bed', 'es_name' => 'Sofá cama sencillo', 'bed_capacity' => 1, 'sort_order' => 8],
            ['name' => 'sofa-bed-double', 'en_name' => 'Double Sofa Bed', 'es_name' => 'Sofá cama doble', 'bed_capacity' => 2, 'sort_order' => 9],
            ['name' => 'bunk-bed', 'en_name' => 'Bunk Bed', 'es_name' => 'Camarote', 'bed_capacity' => 2, 'sort_order' => 10],
            ['name' => 'trundle-bed', 'en_name' => 'Trundle Bed', 'es_name' => 'Cama nido', 'bed_capacity' => 2, 'sort_order' => 11],
            ['name' => 'daybed', 'en_name' => 'Daybed', 'es_name' => 'Cama tipo diván', 'bed_capacity' => 1, 'sort_order' => 12],
            ['name' => 'murphy-bed', 'en_name' => 'Murphy Bed', 'es_name' => 'Cama abatible', 'bed_capacity' => 2, 'sort_order' => 13],
            ['name' => 'futon', 'en_name' => 'Futon', 'es_name' => 'Futón', 'bed_capacity' => 2, 'sort_order' => 14],
            ['name' => 'crib', 'en_name' => 'Crib', 'es_name' => 'Cuna', 'bed_capacity' => 1, 'sort_order' => 15],
            ['name' => 'rollaway-bed', 'en_name' => 'Rollaway Bed', 'es_name' => 'Cama auxiliar plegable', 'bed_capacity' => 1, 'sort_order' => 16],
            ['name' => 'cot', 'en_name' => 'Cot', 'es_name' => 'Cama de campaña', 'bed_capacity' => 1, 'sort_order' => 17],
        ];
    }
}
