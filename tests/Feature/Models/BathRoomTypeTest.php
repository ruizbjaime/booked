<?php

use App\Models\BathRoomType;
use App\Models\Bedroom;
use App\Models\Property;

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
        'slug' => 'private-bathroom',
        'en_name' => 'Private Bathroom',
        'es_name' => 'Bano privado',
        'description' => 'Exclusive bathroom inside the room.',
    ]);

    BathRoomType::factory()->create([
        'slug' => 'shared-bathroom',
        'en_name' => 'Shared Bathroom',
        'es_name' => 'Bano compartido',
        'description' => 'Bathroom shared with other guests.',
    ]);

    expect(BathRoomType::query()->search('private-bathroom')->pluck('slug')->all())->toBe(['private-bathroom'])
        ->and(BathRoomType::query()->search('Shared Bathroom')->pluck('slug')->all())->toBe(['shared-bathroom'])
        ->and(BathRoomType::query()->search('Exclusive')->pluck('slug')->all())->toBe(['private-bathroom']);
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

it('returns related bedrooms through bedrooms relationship', function () {
    $bathRoomType = BathRoomType::factory()->create();
    $main = Bedroom::factory()->create(['en_name' => 'Main Bedroom']);
    $guest = Bedroom::factory()->create(['en_name' => 'Guest Bedroom']);

    $bathRoomType->bedrooms()->attach([
        $main->id => ['quantity' => 2],
        $guest->id => ['quantity' => 1],
    ]);

    $related = $bathRoomType->bedrooms()->get();

    expect($related)->toHaveCount(2)
        ->and($related->pluck('id')->all())->toBe([$guest->id, $main->id])
        ->and($related->first()?->pivot->quantity)->toBe(1)
        ->and($related->last()?->pivot->quantity)->toBe(2);
});

it('returns related properties through properties relationship', function () {
    $bathRoomType = BathRoomType::factory()->create();
    $alpha = Property::factory()->create(['name' => 'Alpha Property']);
    $zulu = Property::factory()->create(['name' => 'Zulu Property']);

    $bathRoomType->properties()->attach([
        $zulu->id => ['quantity' => 2],
        $alpha->id => ['quantity' => 1],
    ]);

    $related = $bathRoomType->properties()->get();

    expect($related)->toHaveCount(2)
        ->and($related->pluck('id')->all())->toBe([$alpha->id, $zulu->id])
        ->and($related->first()?->pivot->quantity)->toBe(1)
        ->and($related->last()?->pivot->quantity)->toBe(2);
});
