<?php

namespace Database\Seeders;

use App\Models\FeeType;
use Illuminate\Database\Seeder;

class FeeTypeSeeder extends Seeder
{
    public function run(): void
    {
        FeeType::upsert(
            $this->types(),
            ['slug'],
            ['en_name', 'es_name', 'order'],
        );
    }

    /**
     * @return list<array{slug: string, en_name: string, es_name: string, order: int}>
     */
    private function types(): array
    {
        return [
            ['slug' => 'cleaning-fee', 'en_name' => 'Cleaning Fee', 'es_name' => 'Tarifa de limpieza', 'order' => 1],
            ['slug' => 'extra-guest-fee', 'en_name' => 'Extra Guest Fee', 'es_name' => 'Tarifa por huesped adicional', 'order' => 2],
            ['slug' => 'pet-fee', 'en_name' => 'Pet Fee', 'es_name' => 'Tarifa por mascota', 'order' => 3],
            ['slug' => 'credit-card-fee', 'en_name' => 'Credit Card Fee', 'es_name' => 'Tarifa por pago con tarjeta', 'order' => 4],
            ['slug' => 'early-check-in-fee', 'en_name' => 'Early Check-in Fee', 'es_name' => 'Tarifa por entrada anticipada', 'order' => 5],
            ['slug' => 'late-check-out-fee', 'en_name' => 'Late Check-out Fee', 'es_name' => 'Tarifa por salida tardia', 'order' => 6],
            ['slug' => 'parking-fee', 'en_name' => 'Parking Fee', 'es_name' => 'Tarifa de estacionamiento', 'order' => 7],
        ];
    }
}
