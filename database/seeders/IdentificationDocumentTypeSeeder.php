<?php

namespace Database\Seeders;

use App\Models\IdentificationDocumentType;
use Illuminate\Database\Seeder;

class IdentificationDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        IdentificationDocumentType::upsert(
            $this->types(),
            ['code'],
            ['en_name', 'es_name', 'sort_order'],
        );
    }

    /**
     * @return list<array{code: string, en_name: string, es_name: string, sort_order: int}>
     */
    private function types(): array
    {
        return [
            ['code' => 'C.C.', 'en_name' => 'Citizenship ID', 'es_name' => 'Cédula de Ciudadanía', 'sort_order' => 1],
            ['code' => 'T.I.', 'en_name' => 'Identity Card', 'es_name' => 'Tarjeta de Identidad', 'sort_order' => 2],
            ['code' => 'R.C.', 'en_name' => 'Birth Certificate', 'es_name' => 'Registro Civil de Nacimiento', 'sort_order' => 3],
            ['code' => 'NIT', 'en_name' => 'Tax ID', 'es_name' => 'NIT', 'sort_order' => 4],
            ['code' => 'PA', 'en_name' => 'Passport', 'es_name' => 'Pasaporte', 'sort_order' => 5],
            ['code' => 'C.E.', 'en_name' => 'Foreign ID', 'es_name' => 'Cédula de Extranjería', 'sort_order' => 6],
            ['code' => 'PEP', 'en_name' => 'Special Stay Permit', 'es_name' => 'Permiso Especial de Permanencia', 'sort_order' => 7],
            ['code' => 'PPT', 'en_name' => 'Temporary Protection Permit', 'es_name' => 'Permiso por Protección Temporal', 'sort_order' => 8],
        ];
    }
}
