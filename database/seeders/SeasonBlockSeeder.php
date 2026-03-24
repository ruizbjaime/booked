<?php

namespace Database\Seeders;

use App\Models\SeasonBlock;
use Illuminate\Database\Seeder;

class SeasonBlockSeeder extends Seeder
{
    public function run(): void
    {
        SeasonBlock::upsert(
            $this->blocks(),
            ['name'],
            ['en_name', 'es_name', 'calculation_strategy', 'fixed_start_month', 'fixed_start_day', 'fixed_end_month', 'fixed_end_day', 'priority', 'sort_order'],
        );
    }

    /**
     * @return list<array{name: string, en_name: string, es_name: string, calculation_strategy: string, fixed_start_month: int|null, fixed_start_day: int|null, fixed_end_month: int|null, fixed_end_day: int|null, priority: int, sort_order: int}>
     */
    private function blocks(): array
    {
        return [
            ['name' => 'holy_week', 'en_name' => 'Holy Week', 'es_name' => 'Semana Santa', 'calculation_strategy' => 'holy_week', 'fixed_start_month' => null, 'fixed_start_day' => null, 'fixed_end_month' => null, 'fixed_end_day' => null, 'priority' => 1, 'sort_order' => 1],
            ['name' => 'year_end', 'en_name' => 'Year-End Season', 'es_name' => 'Temporada de Fin de Año', 'calculation_strategy' => 'year_end', 'fixed_start_month' => null, 'fixed_start_day' => null, 'fixed_end_month' => null, 'fixed_end_day' => null, 'priority' => 2, 'sort_order' => 2],
            ['name' => 'october_recess', 'en_name' => 'October Recess', 'es_name' => 'Receso de Octubre', 'calculation_strategy' => 'october_recess', 'fixed_start_month' => null, 'fixed_start_day' => null, 'fixed_end_month' => null, 'fixed_end_day' => null, 'priority' => 3, 'sort_order' => 3],
            ['name' => 'foreign_tourist', 'en_name' => 'Foreign Tourist Season', 'es_name' => 'Temporada de Turista Extranjero', 'calculation_strategy' => 'foreign_tourist', 'fixed_start_month' => null, 'fixed_start_day' => null, 'fixed_end_month' => null, 'fixed_end_day' => null, 'priority' => 4, 'sort_order' => 4],
        ];
    }
}
