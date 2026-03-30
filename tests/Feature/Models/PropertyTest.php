<?php

use App\Models\BathRoomType;
use App\Models\Bedroom;
use App\Models\Country;
use App\Models\Property;
use App\Models\User;

it('belongs to a country', function () {
    $country = Country::factory()->create();
    $property = Property::factory()->create(['country_id' => $country->id]);

    expect($property->country)
        ->not->toBeNull()
        ->id->toBe($country->id);
});

it('filters only active properties with scopeActive', function () {
    $activeProperty = Property::factory()->create(['is_active' => true]);
    Property::factory()->inactive()->create();

    $active = Property::query()->active()->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->is($activeProperty))->toBeTrue();
});

it('searches properties by slug, name, city, address, and country names', function () {
    $colombia = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);
    $peru = Country::factory()->create(['en_name' => 'Peru', 'es_name' => 'Peru']);

    Property::factory()->create([
        'slug' => 'beachhouse',
        'name' => 'Beach House',
        'city' => 'Cartagena',
        'address' => 'Centro Historico 101',
        'country_id' => $colombia->id,
    ]);

    Property::factory()->create([
        'slug' => 'mountain_cabin',
        'name' => 'Mountain Cabin',
        'city' => 'Cusco',
        'address' => 'Valle Sagrado 202',
        'country_id' => $peru->id,
    ]);

    expect(Property::query()->search('beachhouse')->pluck('name')->all())->toBe(['Beach House'])
        ->and(Property::query()->search('Beach')->pluck('name')->all())->toBe(['Beach House'])
        ->and(Property::query()->search('Cartagena')->pluck('name')->all())->toBe(['Beach House'])
        ->and(Property::query()->search('Sagrado')->pluck('name')->all())->toBe(['Mountain Cabin'])
        ->and(Property::query()->search('Peru')->pluck('name')->all())->toBe(['Mountain Cabin']);
});

it('escapes special SQL wildcard characters in scopeSearch', function () {
    Property::factory()->create([
        'slug' => 'alphahome',
        'name' => 'Alpha Home',
        'city' => 'Alpha City',
        'address' => 'Alpha Address',
    ]);

    Property::factory()->create([
        'slug' => 'betahome',
        'name' => 'Beta Home',
        'city' => 'Beta City',
        'address' => 'Beta Address',
    ]);

    expect(Property::query()->search('%')->count())->toBe(0)
        ->and(Property::query()->search('_')->count())->toBe(0)
        ->and(Property::query()->search('!')->count())->toBe(0);
});

it('casts is_active to boolean', function () {
    $property = Property::factory()->create(['is_active' => true]);

    expect($property->fresh()?->is_active)->toBeTrue()->toBeBool();
});

it('belongs to a user', function () {
    $user = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $user->id]);

    expect($property->user)
        ->not->toBeNull()
        ->id->toBe($user->id);
});

it('filters properties owned by a specific user with scopeOwnedBy', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $propertyA = Property::factory()->create(['user_id' => $userA->id]);
    Property::factory()->create(['user_id' => $userB->id]);

    $owned = Property::query()->ownedBy($userA)->get();

    expect($owned)->toHaveCount(1)
        ->and($owned->first()->is($propertyA))->toBeTrue();
});

it('has many bedrooms', function () {
    $property = Property::factory()->create();
    $bedroomA = Bedroom::factory()->create(['property_id' => $property->id]);
    $bedroomB = Bedroom::factory()->create(['property_id' => $property->id]);

    expect($property->bedrooms)
        ->toHaveCount(2)
        ->and($property->bedrooms->pluck('id')->all())
        ->toContain($bedroomA->id, $bedroomB->id);
});

it('returns related shared bathroom types through bathRoomTypes relationship', function () {
    $property = Property::factory()->create();
    $private = BathRoomType::factory()->create(['en_name' => 'Private Bathroom', 'sort_order' => 20]);
    $shared = BathRoomType::factory()->create(['en_name' => 'Shared Bathroom', 'sort_order' => 10]);

    $property->bathRoomTypes()->attach([
        $private->id => ['quantity' => 1],
        $shared->id => ['quantity' => 2],
    ]);

    $related = $property->bathRoomTypes()->get();

    expect($related)->toHaveCount(2)
        ->and($related->pluck('id')->all())->toBe([$shared->id, $private->id])
        ->and($related->first()?->pivot->quantity)->toBe(2)
        ->and($related->last()?->pivot->quantity)->toBe(1);
});
