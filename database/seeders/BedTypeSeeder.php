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
            $this->bedTypes(),
            ['name'],
            ['name_en', 'name_es', 'bed_capacity', 'sort_order'],
        );
    }

    /**
     * @return list<array{name: string, name_en: string, name_es: string, bed_capacity: int, sort_order: int}>
     */
    private function bedTypes(): array
    {
        return [
            ['name' => 'single-bed', 'name_en' => 'Single Bed', 'name_es' => 'Cama sencilla', 'bed_capacity' => 1, 'sort_order' => 1],
            ['name' => 'twin-bed', 'name_en' => 'Twin Bed', 'name_es' => 'Cama semidoble', 'bed_capacity' => 2, 'sort_order' => 2],
            ['name' => 'double-bed', 'name_en' => 'Double Bed', 'name_es' => 'Cama doble', 'bed_capacity' => 2, 'sort_order' => 3],
            ['name' => 'full-bed', 'name_en' => 'Full Bed', 'name_es' => 'Cama matrimonial', 'bed_capacity' => 2, 'sort_order' => 4],
            ['name' => 'queen-bed', 'name_en' => 'Queen Bed', 'name_es' => 'Cama queen', 'bed_capacity' => 2, 'sort_order' => 5],
            ['name' => 'king-bed', 'name_en' => 'King Bed', 'name_es' => 'Cama king', 'bed_capacity' => 2, 'sort_order' => 6],
            ['name' => 'california-king-bed', 'name_en' => 'California King Bed', 'name_es' => 'Cama king californiana', 'bed_capacity' => 2, 'sort_order' => 7],
            ['name' => 'sofa-bed-single', 'name_en' => 'Single Sofa Bed', 'name_es' => 'Sofá cama sencillo', 'bed_capacity' => 1, 'sort_order' => 8],
            ['name' => 'sofa-bed-double', 'name_en' => 'Double Sofa Bed', 'name_es' => 'Sofá cama doble', 'bed_capacity' => 2, 'sort_order' => 9],
            ['name' => 'bunk-bed', 'name_en' => 'Bunk Bed', 'name_es' => 'Camarote', 'bed_capacity' => 2, 'sort_order' => 10],
            ['name' => 'trundle-bed', 'name_en' => 'Trundle Bed', 'name_es' => 'Cama nido', 'bed_capacity' => 2, 'sort_order' => 11],
            ['name' => 'daybed', 'name_en' => 'Daybed', 'name_es' => 'Cama tipo diván', 'bed_capacity' => 1, 'sort_order' => 12],
            ['name' => 'murphy-bed', 'name_en' => 'Murphy Bed', 'name_es' => 'Cama abatible', 'bed_capacity' => 2, 'sort_order' => 13],
            ['name' => 'futon', 'name_en' => 'Futon', 'name_es' => 'Futón', 'bed_capacity' => 2, 'sort_order' => 14],
            ['name' => 'crib', 'name_en' => 'Crib', 'name_es' => 'Cuna', 'bed_capacity' => 1, 'sort_order' => 15],
            ['name' => 'rollaway-bed', 'name_en' => 'Rollaway Bed', 'name_es' => 'Cama auxiliar plegable', 'bed_capacity' => 1, 'sort_order' => 16],
            ['name' => 'cot', 'name_en' => 'Cot', 'name_es' => 'Cama de campaña', 'bed_capacity' => 1, 'sort_order' => 17],
        ];
    }
}
