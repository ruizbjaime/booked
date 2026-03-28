<?php

use App\Models\Property;
use Illuminate\Support\Facades\Schema;

it('creates the final properties table schema', function () {
    expect(Schema::hasColumns('properties', [
        'id',
        'slug',
        'name',
        'city',
        'address',
        'country_id',
        'is_active',
        'created_at',
        'updated_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumn('properties', 'label'))->toBeFalse()
        ->and(Schema::hasColumn('properties', 'country'))->toBeFalse()
        ->and(Schema::hasColumn('properties', 'en_name'))->toBeFalse()
        ->and(Schema::hasColumn('properties', 'es_name'))->toBeFalse();
});

it('persists properties with the final schema defaults', function () {
    $property = Property::factory()->create();

    expect($property->fresh())
        ->not->toBeNull()
        ->name->toBe($property->name)
        ->slug->toBe($property->slug)
        ->address->toBe($property->address)
        ->country_id->toBe($property->country_id)
        ->is_active->toBeTrue();
});
