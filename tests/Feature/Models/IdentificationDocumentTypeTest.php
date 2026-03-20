<?php

use App\Models\IdentificationDocumentType;
use App\Models\User;

it('has a users relationship', function () {
    $docType = IdentificationDocumentType::factory()->create();
    $user = User::factory()->create(['document_type_id' => $docType->id]);

    expect($docType->users)
        ->toHaveCount(1)
        ->first()->id->toBe($user->id);
});

it('returns localized name in english by default', function () {
    app()->setLocale('en');

    $docType = IdentificationDocumentType::factory()->create([
        'en_name' => 'Citizenship ID',
        'es_name' => 'Cédula de Ciudadanía',
    ]);

    expect($docType->localizedName())->toBe('Citizenship ID');
});

it('returns localized name in spanish when locale is es', function () {
    app()->setLocale('es');

    $docType = IdentificationDocumentType::factory()->create([
        'en_name' => 'Citizenship ID',
        'es_name' => 'Cédula de Ciudadanía',
    ]);

    expect($docType->localizedName())->toBe('Cédula de Ciudadanía');
});
