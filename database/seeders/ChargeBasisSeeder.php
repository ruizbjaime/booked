<?php

namespace Database\Seeders;

use App\Models\ChargeBasis;
use Illuminate\Database\Seeder;

class ChargeBasisSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();

        ChargeBasis::upsert(
            array_map(fn (array $basis): array => [
                ...$basis,
                'metadata' => json_encode($basis['metadata'], JSON_THROW_ON_ERROR),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $this->bases()),
            ['name'],
            ['en_name', 'es_name', 'en_description', 'es_description', 'order', 'is_active', 'metadata', 'updated_at'],
        );
    }

    /**
     * @return list<array{name: string, en_name: string, es_name: string, en_description: string, es_description: string, order: int, is_active: bool, metadata: array<string, mixed>}>
     */
    private function bases(): array
    {
        return [
            ['name' => 'per_stay', 'en_name' => 'Per Stay', 'es_name' => 'Por estadía', 'en_description' => 'Applied once for the full stay.', 'es_description' => 'Se aplica una vez por toda la estadía.', 'order' => 1, 'is_active' => true, 'metadata' => ['requires_quantity' => false, 'quantity_subject' => null]],
            ['name' => 'per_night', 'en_name' => 'Per Night', 'es_name' => 'Por noche', 'en_description' => 'Applied for each booked night.', 'es_description' => 'Se aplica por cada noche reservada.', 'order' => 2, 'is_active' => true, 'metadata' => ['requires_quantity' => false, 'quantity_subject' => null]],
            ['name' => 'per_request', 'en_name' => 'Per Request', 'es_name' => 'Por solicitud', 'en_description' => 'Applied when the guest requests the service.', 'es_description' => 'Se aplica cuando el huésped solicita el servicio.', 'order' => 3, 'is_active' => true, 'metadata' => ['requires_quantity' => false, 'quantity_subject' => null]],
            ['name' => 'per_use', 'en_name' => 'Per Use', 'es_name' => 'Por uso', 'en_description' => 'Applied every time the service or facility is used.', 'es_description' => 'Se aplica cada vez que se usa el servicio o instalación.', 'order' => 4, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'use']],
            ['name' => 'per_guest', 'en_name' => 'Per Guest', 'es_name' => 'Por huésped', 'en_description' => 'Applied for each guest in the reservation.', 'es_description' => 'Se aplica por cada huésped en la reservación.', 'order' => 5, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'guest']],
            ['name' => 'per_guest_per_night', 'en_name' => 'Per Guest Per Night', 'es_name' => 'Por huésped por noche', 'en_description' => 'Applied for each guest and each night.', 'es_description' => 'Se aplica por cada huésped y cada noche.', 'order' => 6, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'guest']],
            ['name' => 'per_pet', 'en_name' => 'Per Pet', 'es_name' => 'Por mascota', 'en_description' => 'Applied for each approved pet.', 'es_description' => 'Se aplica por cada mascota aprobada.', 'order' => 7, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'pet']],
            ['name' => 'per_pet_per_night', 'en_name' => 'Per Pet Per Night', 'es_name' => 'Por mascota por noche', 'en_description' => 'Applied for each pet and each night.', 'es_description' => 'Se aplica por cada mascota y cada noche.', 'order' => 8, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'pet']],
            ['name' => 'per_vehicle', 'en_name' => 'Per Vehicle', 'es_name' => 'Por vehículo', 'en_description' => 'Applied for each registered vehicle.', 'es_description' => 'Se aplica por cada vehículo registrado.', 'order' => 9, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'vehicle']],
        ];
    }
}
