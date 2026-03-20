<?php

use App\Models\IdentificationDocumentType;
use Database\Seeders\IdentificationDocumentTypeSeeder;

it('seeds document types into the database', function () {
    $this->seed(IdentificationDocumentTypeSeeder::class);

    expect(IdentificationDocumentType::query()->count())->toBe(8);
});

it('includes cedula de ciudadania', function () {
    $this->seed(IdentificationDocumentTypeSeeder::class);

    $cc = IdentificationDocumentType::query()->where('code', 'C.C.')->first();

    expect($cc)
        ->not->toBeNull()
        ->en_name->toBe('Citizenship ID')
        ->sort_order->toBe(1);
});

it('is idempotent', function () {
    $this->seed(IdentificationDocumentTypeSeeder::class);
    $firstCount = IdentificationDocumentType::query()->count();

    $this->seed(IdentificationDocumentTypeSeeder::class);
    $secondCount = IdentificationDocumentType::query()->count();

    expect($firstCount)->toBe($secondCount);
});
