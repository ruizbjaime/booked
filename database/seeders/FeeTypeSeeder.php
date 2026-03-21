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
            ['name'],
            ['en_name', 'es_name', 'order'],
        );
    }

    /**
     * @return list<array{name: string, en_name: string, es_name: string, order: int}>
     */
    private function types(): array
    {
        return [
            ['name' => 'cleaning-fee', 'en_name' => 'Cleaning Fee', 'es_name' => 'Tarifa de limpieza', 'order' => 1],
            ['name' => 'short-stay-cleaning-fee', 'en_name' => 'Short-Stay Cleaning Fee', 'es_name' => 'Tarifa de limpieza para estancia corta', 'order' => 2],
            ['name' => 'pet-fee', 'en_name' => 'Pet Fee', 'es_name' => 'Tarifa por mascota', 'order' => 3],
            ['name' => 'extra-guest-fee', 'en_name' => 'Extra Guest Fee', 'es_name' => 'Tarifa por huesped adicional', 'order' => 4],
            ['name' => 'resort-fee', 'en_name' => 'Resort Fee', 'es_name' => 'Tarifa de resort', 'order' => 5],
            ['name' => 'linen-fee', 'en_name' => 'Linen Fee', 'es_name' => 'Tarifa de ropa de cama', 'order' => 6],
            ['name' => 'towel-fee', 'en_name' => 'Towel Fee', 'es_name' => 'Tarifa de toallas', 'order' => 7],
            ['name' => 'management-fee', 'en_name' => 'Management Fee', 'es_name' => 'Tarifa de gestion', 'order' => 8],
            ['name' => 'community-fee', 'en_name' => 'Community Fee', 'es_name' => 'Tarifa comunitaria', 'order' => 9],
            ['name' => 'service-charge', 'en_name' => 'Service Charge', 'es_name' => 'Cargo por servicio', 'order' => 10],
            ['name' => 'destination-charge', 'en_name' => 'Destination Charge', 'es_name' => 'Cargo de destino', 'order' => 11],
            ['name' => 'destination-tax', 'en_name' => 'Destination Tax', 'es_name' => 'Impuesto de destino', 'order' => 12],
            ['name' => 'tourism-fee', 'en_name' => 'Tourism Fee', 'es_name' => 'Tasa turistica', 'order' => 13],
            ['name' => 'city-tax', 'en_name' => 'City Tax', 'es_name' => 'Impuesto municipal', 'order' => 14],
            ['name' => 'municipality-fee', 'en_name' => 'Municipality Fee', 'es_name' => 'Tasa municipal', 'order' => 15],
            ['name' => 'government-tax', 'en_name' => 'Government Tax', 'es_name' => 'Impuesto gubernamental', 'order' => 16],
            ['name' => 'vat-sales-tax', 'en_name' => 'VAT / Sales Tax', 'es_name' => 'IVA / impuesto sobre ventas', 'order' => 17],
            ['name' => 'environment-fee', 'en_name' => 'Environment Fee', 'es_name' => 'Tasa ambiental', 'order' => 18],
            ['name' => 'sustainability-fee', 'en_name' => 'Sustainability Fee', 'es_name' => 'Tasa de sostenibilidad', 'order' => 19],
            ['name' => 'heritage-tax', 'en_name' => 'Heritage Tax', 'es_name' => 'Impuesto patrimonial', 'order' => 20],
            ['name' => 'local-conservation-fee', 'en_name' => 'Local Conservation Fee', 'es_name' => 'Tasa local de conservacion', 'order' => 21],
            ['name' => 'city-ticket-fee', 'en_name' => 'City Ticket Fee', 'es_name' => 'Tasa de city ticket', 'order' => 22],
            ['name' => 'hot-spring-tax', 'en_name' => 'Hot Spring Tax', 'es_name' => 'Impuesto de aguas termales', 'order' => 23],
            ['name' => 'spa-tax', 'en_name' => 'Spa Tax', 'es_name' => 'Impuesto de spa', 'order' => 24],
            ['name' => 'parking-fee', 'en_name' => 'Parking Fee', 'es_name' => 'Tarifa de estacionamiento', 'order' => 25],
            ['name' => 'internet-wifi-fee', 'en_name' => 'Internet / Wi-Fi Fee', 'es_name' => 'Tarifa de internet / Wi-Fi', 'order' => 26],
            ['name' => 'credit-card-fee', 'en_name' => 'Credit Card Fee', 'es_name' => 'Tarifa por pago con tarjeta', 'order' => 27],
            ['name' => 'smoking-fee', 'en_name' => 'Smoking Fee', 'es_name' => 'Cargo por fumar', 'order' => 28],
            ['name' => 'early-check-in-fee', 'en_name' => 'Early Check-in Fee', 'es_name' => 'Tarifa por entrada anticipada', 'order' => 29],
            ['name' => 'late-check-out-fee', 'en_name' => 'Late Check-out Fee', 'es_name' => 'Tarifa por salida tardia', 'order' => 30],
            ['name' => 'facility-usage-fee', 'en_name' => 'Facility Usage Fee', 'es_name' => 'Tarifa por uso de instalaciones', 'order' => 31],
        ];
    }
}
