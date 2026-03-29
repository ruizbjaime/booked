<?php

use App\Models\BathRoomType;

it('returns localized name in english by default', function () {
    app()->setLocale('en');

    $bathRoomType = BathRoomType::factory()->create([
        'en_name' => 'Private Bathroom',
        'es_name' => 'Bano privado',
    ]);

    expect($bathRoomType->localizedName())->toBe('Private Bathroom');
});

it('returns localized name in spanish when locale is es', function () {
    app()->setLocale('es');

    $bathRoomType = BathRoomType::factory()->create([
        'en_name' => 'Shared Bathroom',
        'es_name' => 'Bano compartido',
    ]);

    expect($bathRoomType->localizedName())->toBe('Bano compartido');
});

it('searches by slug localized labels and description', function () {
    BathRoomType::factory()->create([
        'name' => 'private-bathroom',
        'en_name' => 'Private Bathroom',
        'es_name' => 'Bano privado',
        'description' => 'Exclusive bathroom inside the room.',
    ]);

    BathRoomType::factory()->create([
        'name' => 'shared-bathroom',
        'en_name' => 'Shared Bathroom',
        'es_name' => 'Bano compartido',
        'description' => 'Bathroom shared with other guests.',
    ]);

    expect(BathRoomType::query()->search('private-bathroom')->pluck('name')->all())->toBe(['private-bathroom'])
        ->and(BathRoomType::query()->search('Shared Bathroom')->pluck('name')->all())->toBe(['shared-bathroom'])
        ->and(BathRoomType::query()->search('Exclusive')->pluck('name')->all())->toBe(['private-bathroom']);
});

it('returns the localized name column for each locale', function () {
    app()->setLocale('en');
    expect(BathRoomType::localizedNameColumn())->toBe('en_name');

    app()->setLocale('es');
    expect(BathRoomType::localizedNameColumn())->toBe('es_name');
});

it('exposes localized name as eloquent attribute accessor', function () {
    app()->setLocale('en');

    $bathRoomType = BathRoomType::factory()->create([
        'en_name' => 'Ensuite Bathroom',
        'es_name' => 'Bano en suite',
    ]);

    expect($bathRoomType->localized_name_attribute)->toBe('Ensuite Bathroom');

    app()->setLocale('es');

    expect($bathRoomType->localized_name_attribute)->toBe('Bano en suite');
});
