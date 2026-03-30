<?php

use App\Models\Bedroom;
use App\Models\BedType;
use App\Models\Property;

it('belongs to a property', function () {
    $property = Property::factory()->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);

    expect($bedroom->property)
        ->not->toBeNull()
        ->id->toBe($property->id);
});

it('searches bedrooms by slug, names, and descriptions', function () {
    Bedroom::factory()->create([
        'slug' => 'main-bedroom',
        'en_name' => 'Main Bedroom',
        'es_name' => 'Habitación principal',
        'en_description' => 'Ocean view and private balcony.',
        'es_description' => 'Vista al mar y balcón privado.',
    ]);

    Bedroom::factory()->create([
        'slug' => 'guest-bedroom',
        'en_name' => 'Guest Bedroom',
        'es_name' => 'Habitación de invitados',
        'en_description' => 'Garden access.',
        'es_description' => 'Acceso al jardín.',
    ]);

    expect(Bedroom::query()->search('main-bedroom')->pluck('slug')->all())->toBe(['main-bedroom'])
        ->and(Bedroom::query()->search('Main Bedroom')->pluck('slug')->all())->toBe(['main-bedroom'])
        ->and(Bedroom::query()->search('principal')->pluck('slug')->all())->toBe(['main-bedroom'])
        ->and(Bedroom::query()->search('balcony')->pluck('slug')->all())->toBe(['main-bedroom'])
        ->and(Bedroom::query()->search('jardín')->pluck('slug')->all())->toBe(['guest-bedroom']);
});

it('generates slug from en_name', function () {
    $bedroom = Bedroom::factory()->create([
        'en_name' => 'Ocean View Bedroom',
    ]);

    expect($bedroom->slug)->toBe('ocean-view-bedroom');
});

it('generates a unique slug when en_name collides', function () {
    $first = Bedroom::factory()->create([
        'en_name' => 'Ocean View Bedroom',
    ]);

    $second = Bedroom::factory()->create([
        'en_name' => 'Ocean View Bedroom',
    ]);

    expect($first->slug)->toBe('ocean-view-bedroom')
        ->and($second->slug)->toStartWith('ocean-view-bedroom-')
        ->and($second->slug)->not->toBe($first->slug);
});

it('returns related bed types through bedTypes relationship', function () {
    $bedroom = Bedroom::factory()->create();
    $king = BedType::factory()->create(['en_name' => 'King Bed', 'sort_order' => 20]);
    $queen = BedType::factory()->create(['en_name' => 'Queen Bed', 'sort_order' => 10]);

    $bedroom->bedTypes()->attach([
        $king->id => ['quantity' => 1],
        $queen->id => ['quantity' => 2],
    ]);

    $related = $bedroom->bedTypes()->get();

    expect($related)->toHaveCount(2)
        ->and($related->pluck('id')->all())->toBe([$queen->id, $king->id])
        ->and($related->first()?->pivot->quantity)->toBe(2)
        ->and($related->last()?->pivot->quantity)->toBe(1);
});
