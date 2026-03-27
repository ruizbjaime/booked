<?php

namespace Database\Seeders;

use App\Models\HolidayDefinition;
use Illuminate\Database\Seeder;

class HolidayDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        HolidayDefinition::upsert(
            $this->definitions(),
            ['name'],
            ['en_name', 'es_name', 'group', 'month', 'day', 'easter_offset', 'moves_to_monday', 'base_impact_weights', 'special_overrides', 'sort_order'],
        );
    }

    /**
     * @return list<array{name: string, en_name: string, es_name: string, group: string, month: int|null, day: int|null, easter_offset: int|null, moves_to_monday: bool, base_impact_weights: string, special_overrides: string|null, sort_order: int}>
     */
    private function definitions(): array
    {
        $fixedWeights = json_encode([
            'monday' => 10, 'tuesday' => 4, 'wednesday' => 4,
            'thursday' => 4, 'friday' => 10, 'saturday' => 2, 'sunday' => 2,
        ]);

        $boyacaWeights = json_encode([
            'monday' => 10, 'tuesday' => 7, 'wednesday' => 4,
            'thursday' => 7, 'friday' => 10, 'saturday' => 2, 'sunday' => 2,
        ]);

        $emiliani = json_encode(['default' => 10]);
        $easterHigh = json_encode(['default' => 10]);
        $easterMoved = json_encode(['default' => 10]);

        return [
            // Group A: Fixed (Inamovibles)
            ['name' => 'new_year', 'en_name' => 'New Year\'s Day', 'es_name' => 'Año Nuevo', 'group' => 'fixed', 'month' => 1, 'day' => 1, 'easter_offset' => null, 'moves_to_monday' => false, 'base_impact_weights' => $fixedWeights, 'special_overrides' => null, 'sort_order' => 1],
            ['name' => 'labor_day', 'en_name' => 'Labor Day', 'es_name' => 'Día del Trabajo', 'group' => 'fixed', 'month' => 5, 'day' => 1, 'easter_offset' => null, 'moves_to_monday' => false, 'base_impact_weights' => $fixedWeights, 'special_overrides' => null, 'sort_order' => 2],
            ['name' => 'independence_day', 'en_name' => 'Independence Day', 'es_name' => 'Día de la Independencia', 'group' => 'fixed', 'month' => 7, 'day' => 20, 'easter_offset' => null, 'moves_to_monday' => false, 'base_impact_weights' => $fixedWeights, 'special_overrides' => null, 'sort_order' => 3],
            ['name' => 'battle_of_boyaca', 'en_name' => 'Battle of Boyacá', 'es_name' => 'Batalla de Boyacá', 'group' => 'fixed', 'month' => 8, 'day' => 7, 'easter_offset' => null, 'moves_to_monday' => false, 'base_impact_weights' => $boyacaWeights, 'special_overrides' => null, 'sort_order' => 4],
            ['name' => 'immaculate_conception', 'en_name' => 'Immaculate Conception', 'es_name' => 'Inmaculada Concepción', 'group' => 'fixed', 'month' => 12, 'day' => 8, 'easter_offset' => null, 'moves_to_monday' => false, 'base_impact_weights' => $fixedWeights, 'special_overrides' => json_encode([['location' => 'villa_de_leyva', 'dates' => ['12-07', '12-08'], 'impact' => 10]]), 'sort_order' => 5],
            ['name' => 'christmas', 'en_name' => 'Christmas Day', 'es_name' => 'Navidad', 'group' => 'fixed', 'month' => 12, 'day' => 25, 'easter_offset' => null, 'moves_to_monday' => false, 'base_impact_weights' => $fixedWeights, 'special_overrides' => null, 'sort_order' => 6],

            // Group B: Emiliani (Movibles)
            ['name' => 'epiphany', 'en_name' => 'Epiphany', 'es_name' => 'Día de los Reyes Magos', 'group' => 'emiliani', 'month' => 1, 'day' => 6, 'easter_offset' => null, 'moves_to_monday' => true, 'base_impact_weights' => $emiliani, 'special_overrides' => null, 'sort_order' => 7],
            ['name' => 'saint_joseph', 'en_name' => 'Saint Joseph\'s Day', 'es_name' => 'Día de San José', 'group' => 'emiliani', 'month' => 3, 'day' => 19, 'easter_offset' => null, 'moves_to_monday' => true, 'base_impact_weights' => $emiliani, 'special_overrides' => null, 'sort_order' => 8],
            ['name' => 'saints_peter_and_paul', 'en_name' => 'Saints Peter and Paul', 'es_name' => 'San Pedro y San Pablo', 'group' => 'emiliani', 'month' => 6, 'day' => 29, 'easter_offset' => null, 'moves_to_monday' => true, 'base_impact_weights' => $emiliani, 'special_overrides' => null, 'sort_order' => 9],
            ['name' => 'assumption_of_mary', 'en_name' => 'Assumption of Mary', 'es_name' => 'Asunción de la Virgen', 'group' => 'emiliani', 'month' => 8, 'day' => 15, 'easter_offset' => null, 'moves_to_monday' => true, 'base_impact_weights' => $emiliani, 'special_overrides' => null, 'sort_order' => 10],
            ['name' => 'columbus_day', 'en_name' => 'Columbus Day', 'es_name' => 'Día de la Raza', 'group' => 'emiliani', 'month' => 10, 'day' => 12, 'easter_offset' => null, 'moves_to_monday' => true, 'base_impact_weights' => $emiliani, 'special_overrides' => null, 'sort_order' => 11],
            ['name' => 'all_saints', 'en_name' => 'All Saints\' Day', 'es_name' => 'Día de Todos los Santos', 'group' => 'emiliani', 'month' => 11, 'day' => 1, 'easter_offset' => null, 'moves_to_monday' => true, 'base_impact_weights' => $emiliani, 'special_overrides' => null, 'sort_order' => 12],
            ['name' => 'independence_of_cartagena', 'en_name' => 'Independence of Cartagena', 'es_name' => 'Independencia de Cartagena', 'group' => 'emiliani', 'month' => 11, 'day' => 11, 'easter_offset' => null, 'moves_to_monday' => true, 'base_impact_weights' => $emiliani, 'special_overrides' => null, 'sort_order' => 13],

            // Group C: Easter-based (Variables)
            ['name' => 'holy_thursday', 'en_name' => 'Holy Thursday', 'es_name' => 'Jueves Santo', 'group' => 'easter_based', 'month' => null, 'day' => null, 'easter_offset' => -3, 'moves_to_monday' => false, 'base_impact_weights' => $easterHigh, 'special_overrides' => null, 'sort_order' => 14],
            ['name' => 'good_friday', 'en_name' => 'Good Friday', 'es_name' => 'Viernes Santo', 'group' => 'easter_based', 'month' => null, 'day' => null, 'easter_offset' => -2, 'moves_to_monday' => false, 'base_impact_weights' => $easterHigh, 'special_overrides' => null, 'sort_order' => 15],
            ['name' => 'ascension', 'en_name' => 'Ascension of Jesus', 'es_name' => 'Ascensión del Señor', 'group' => 'easter_based', 'month' => null, 'day' => null, 'easter_offset' => 39, 'moves_to_monday' => true, 'base_impact_weights' => $easterMoved, 'special_overrides' => null, 'sort_order' => 16],
            ['name' => 'corpus_christi', 'en_name' => 'Corpus Christi', 'es_name' => 'Corpus Christi', 'group' => 'easter_based', 'month' => null, 'day' => null, 'easter_offset' => 60, 'moves_to_monday' => true, 'base_impact_weights' => $easterMoved, 'special_overrides' => null, 'sort_order' => 17],
            ['name' => 'sacred_heart', 'en_name' => 'Sacred Heart', 'es_name' => 'Sagrado Corazón', 'group' => 'easter_based', 'month' => null, 'day' => null, 'easter_offset' => 68, 'moves_to_monday' => true, 'base_impact_weights' => $easterMoved, 'special_overrides' => null, 'sort_order' => 18],
        ];
    }
}
