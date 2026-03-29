<?php

use App\Actions\Properties\GeneratePropertySlug;
use App\Models\Property;

it('generates a slug from a simple name', function () {
    $slug = app(GeneratePropertySlug::class)->handle('Beach House');

    expect($slug)->toBe('beach_house');
});

it('transliterates accented characters', function () {
    $slug = app(GeneratePropertySlug::class)->handle('Cabaña del Lago');

    expect($slug)->toBe('cabana_del_lago');
});

it('strips non-alphanumeric characters', function () {
    $slug = app(GeneratePropertySlug::class)->handle('Beach & Pool Resort!!!');

    expect($slug)->toBe('beach_pool_resort');
});

it('collapses consecutive underscores', function () {
    $slug = app(GeneratePropertySlug::class)->handle('Beach   House___Test');

    expect($slug)->toBe('beach_house_test');
});

it('falls back to property for empty input after sanitization', function () {
    $slug = app(GeneratePropertySlug::class)->handle('!!!@@@');

    expect($slug)->toBe('property');
});

it('generates a unique suffix when slug already exists', function () {
    Property::factory()->create(['slug' => 'beach_house']);

    $slug = app(GeneratePropertySlug::class)->handle('Beach House');

    expect($slug)->toStartWith('beach_house_')
        ->and($slug)->not->toBe('beach_house')
        ->and(strlen($slug))->toBe(strlen('beach_house_') + 4);
});

it('ignores the specified property when checking for duplicates', function () {
    $property = Property::factory()->create(['slug' => 'beach_house']);

    $slug = app(GeneratePropertySlug::class)->handle('Beach House', $property);

    expect($slug)->toBe('beach_house');
});

it('handles very long names within max length', function () {
    $longName = str_repeat('a', 255);

    $slug = app(GeneratePropertySlug::class)->handle($longName);

    expect($slug)->toBe($longName);
});

it('preserves hyphens in the slug', function () {
    $slug = app(GeneratePropertySlug::class)->handle('Beach-House Resort');

    expect($slug)->toBe('beach-house_resort');
});

it('throws RuntimeException when all slug attempts collide', function () {
    $generator = Mockery::mock(GeneratePropertySlug::class)->makePartial();

    $generator->shouldAllowMockingProtectedMethods()
        ->shouldReceive('slugExists')
        ->andReturn(true);

    $generator->handle('test');
})->throws(RuntimeException::class, 'Failed to generate unique slug after 10 attempts.');
