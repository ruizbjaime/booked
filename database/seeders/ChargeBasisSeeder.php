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
            ['en_name', 'es_name', 'description', 'order', 'is_active', 'metadata', 'updated_at'],
        );
    }

    /**
     * @return list<array{name: string, en_name: string, es_name: string, description: string, order: int, is_active: bool, metadata: array<string, mixed>}>
     */
    private function bases(): array
    {
        return [
            ['name' => 'one_time', 'en_name' => 'One-Time', 'es_name' => 'Cobro único', 'description' => 'Applied once regardless of stay length or quantity.', 'order' => 1, 'is_active' => true, 'metadata' => ['requires_quantity' => false, 'quantity_subject' => null]],
            ['name' => 'per_stay', 'en_name' => 'Per Stay', 'es_name' => 'Por estadía', 'description' => 'Applied once for the full stay.', 'order' => 2, 'is_active' => true, 'metadata' => ['requires_quantity' => false, 'quantity_subject' => null]],
            ['name' => 'per_night', 'en_name' => 'Per Night', 'es_name' => 'Por noche', 'description' => 'Applied for each booked night.', 'order' => 3, 'is_active' => true, 'metadata' => ['requires_quantity' => false, 'quantity_subject' => null]],
            ['name' => 'per_request', 'en_name' => 'Per Request', 'es_name' => 'Por solicitud', 'description' => 'Applied when the guest requests the service.', 'order' => 4, 'is_active' => true, 'metadata' => ['requires_quantity' => false, 'quantity_subject' => null]],
            ['name' => 'per_use', 'en_name' => 'Per Use', 'es_name' => 'Por uso', 'description' => 'Applied every time the service or facility is used.', 'order' => 5, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'use']],
            ['name' => 'per_guest', 'en_name' => 'Per Guest', 'es_name' => 'Por huésped', 'description' => 'Applied for each guest in the reservation.', 'order' => 6, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'guest']],
            ['name' => 'per_guest_per_night', 'en_name' => 'Per Guest Per Night', 'es_name' => 'Por huésped por noche', 'description' => 'Applied for each guest and each night.', 'order' => 7, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'guest']],
            ['name' => 'per_pet', 'en_name' => 'Per Pet', 'es_name' => 'Por mascota', 'description' => 'Applied for each approved pet.', 'order' => 8, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'pet']],
            ['name' => 'per_pet_per_night', 'en_name' => 'Per Pet Per Night', 'es_name' => 'Por mascota por noche', 'description' => 'Applied for each pet and each night.', 'order' => 9, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'pet']],
            ['name' => 'per_vehicle', 'en_name' => 'Per Vehicle', 'es_name' => 'Por vehículo', 'description' => 'Applied for each registered vehicle.', 'order' => 10, 'is_active' => true, 'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'vehicle']],
        ];
    }
}
