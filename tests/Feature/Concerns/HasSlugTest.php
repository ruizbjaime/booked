<?php

use App\Models\Platform;

it('generates slug from en_name on creation', function () {
    $platform = Platform::factory()->create(['en_name' => 'Direct Booking']);

    expect($platform->slug)->toBe('direct-booking');
});

it('preserves an explicitly set slug on creation', function () {
    $platform = Platform::factory()->create([
        'slug' => 'my-custom-slug',
        'en_name' => 'Direct Booking',
    ]);

    expect($platform->slug)->toBe('my-custom-slug');
});

it('regenerates slug when slugSourceField changes on update', function () {
    $platform = Platform::factory()->create(['en_name' => 'Old Name']);

    $platform->update(['en_name' => 'New Platform Name']);

    expect($platform->fresh()->slug)->toBe('new-platform-name');
});

it('does not regenerate slug when slugSourceField is unchanged on update', function () {
    $platform = Platform::factory()->create([
        'slug' => 'my-fixed-slug',
        'en_name' => 'Some Name',
    ]);

    $platform->update(['es_name' => 'Otro Nombre']);

    expect($platform->fresh()->slug)->toBe('my-fixed-slug');
});

it('appends a random 4-char suffix on slug collision', function () {
    Platform::factory()->create([
        'slug' => 'booking-com',
        'en_name' => 'Booking Com One',
    ]);

    $second = Platform::factory()->create(['en_name' => 'Booking Com']);

    expect($second->slug)->toStartWith('booking-com-')
        ->and(strlen($second->slug))->toBe(strlen('booking-com-') + 4);
});

it('generates slug from class basename when source field produces empty string', function () {
    $platform = Platform::factory()->create(['en_name' => '--- ---']);

    expect($platform->slug)->toStartWith('platform');
});

it('generateUniqueSlug returns base slug when no collision exists', function () {
    $slug = Platform::generateUniqueSlug('My Platform');

    expect($slug)->toBe('my-platform');
});

it('generateUniqueSlug excludes the ignored model from uniqueness check on update', function () {
    $platform = Platform::factory()->create([
        'slug' => 'booking-com',
        'en_name' => 'Booking Com',
    ]);

    $result = Platform::generateUniqueSlug('Booking Com', $platform);

    expect($result)->toBe('booking-com');
});
