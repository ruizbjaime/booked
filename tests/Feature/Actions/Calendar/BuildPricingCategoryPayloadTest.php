<?php

use App\Actions\Calendar\BuildPricingCategoryPayload;
use App\Models\PricingCategory;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

function validPricingCategoryPayload(array $overrides = []): array
{
    return array_merge([
        'name' => ' cat_5_peak ',
        'en_name' => ' Peak ',
        'es_name' => ' Pico ',
        'level' => '5',
        'color' => ' #A855F7 ',
        'multiplier' => '2.80',
        'sort_order' => '5',
        'is_active' => 'yes',
    ], $overrides);
}

it('normalizes pricing category payload values', function () {
    $payload = app(BuildPricingCategoryPayload::class)->handle(validPricingCategoryPayload());

    expect($payload)->toMatchArray([
        'name' => 'cat_5_peak',
        'en_name' => 'Peak',
        'es_name' => 'Pico',
        'level' => 5,
        'color' => '#A855F7',
        'multiplier' => 2.8,
        'sort_order' => 5,
        'is_active' => true,
    ]);
});

it('allows keeping the current unique name and level when editing', function () {
    $category = PricingCategory::factory()->create([
        'name' => 'cat_5_peak',
        'level' => 5,
    ]);

    $payload = app(BuildPricingCategoryPayload::class)->handle(validPricingCategoryPayload(), $category);

    expect($payload['name'])->toBe('cat_5_peak')
        ->and($payload['level'])->toBe(5);
});

it('normalizes individual pricing category fields', function () {
    $builder = app(BuildPricingCategoryPayload::class);

    expect($builder->normalizeField('name', ' CAT_5_PEAK '))->toBe('cat_5_peak')
        ->and($builder->normalizeField('en_name', ' Peak '))->toBe('Peak')
        ->and($builder->normalizeField('level', '4'))->toBe(4)
        ->and($builder->normalizeField('multiplier', '1.75'))->toBe(1.75)
        ->and($builder->normalizeField('is_active', 'off'))->toBeFalse();
});

it('rejects malformed pricing category boolean states', function () {
    expect(fn () => app(BuildPricingCategoryPayload::class)->handle(validPricingCategoryPayload([
        'is_active' => 'definitely-not-a-bool',
    ])))->toThrow(ValidationException::class);
});

it('validates individual pricing category fields', function () {
    $builder = app(BuildPricingCategoryPayload::class);
    $category = PricingCategory::factory()->create();

    expect(fn () => $builder->validateField($category, 'color', '#22C55E'))->not->toThrow(Exception::class);
    expect(fn () => $builder->validateField($category, 'color', 'green'))->toThrow(ValidationException::class);
});

it('aborts with 422 for an unknown pricing category field', function () {
    $builder = app(BuildPricingCategoryPayload::class);
    $category = PricingCategory::factory()->create();

    expect(fn () => $builder->validateField($category, 'unknown_field', 'value'))
        ->toThrow(HttpException::class);
});

it('rejects duplicate pricing category names and levels from other records', function () {
    PricingCategory::factory()->create([
        'name' => 'cat_9_peak',
        'level' => 9,
    ]);

    expect(fn () => app(BuildPricingCategoryPayload::class)->handle(validPricingCategoryPayload([
        'name' => 'cat_9_peak',
        'level' => 9,
    ])))->toThrow(ValidationException::class);
});

it('defaults pricing category active state to false when omitted', function () {
    $payload = app(BuildPricingCategoryPayload::class)->handle(
        Arr::except(validPricingCategoryPayload(), 'is_active'),
    );

    expect($payload['is_active'])->toBeFalse();
});

it('normalizes additional pricing category fields individually', function () {
    $builder = app(BuildPricingCategoryPayload::class);

    expect($builder->normalizeField('es_name', ' Pico '))->toBe('Pico')
        ->and($builder->normalizeField('color', ' #A855F7 '))->toBe('#A855F7')
        ->and($builder->normalizeField('sort_order', '8'))->toBe(8)
        ->and($builder->normalizeField('unknown_field', 'value'))->toBe('value');
});

it('normalizes accepted scalar boolean values for pricing category active state', function (mixed $value, bool $expected) {
    expect(app(BuildPricingCategoryPayload::class)->normalizeField('is_active', $value))->toBe($expected);
})->with([
    ['true', true],
    ['false', false],
    ['1', true],
    ['0', false],
    [1, true],
    [0, false],
]);

it('passes through invalid field types during single field normalization', function () {
    $builder = app(BuildPricingCategoryPayload::class);
    $object = new stdClass;

    expect($builder->normalizeField('name', ['bad']))->toBe(['bad'])
        ->and($builder->normalizeField('en_name', $object))->toBe($object)
        ->and($builder->normalizeField('level', 'abc'))->toBe('abc')
        ->and($builder->normalizeField('multiplier', ['bad']))->toBe(['bad'])
        ->and($builder->normalizeField('is_active', 2))->toBeNull()
        ->and($builder->normalizeField('is_active', ['bad']))->toBeNull();
});

it('rejects invalid normalized payload fallback values', function () {
    expect(fn () => app(BuildPricingCategoryPayload::class)->handle([
        'name' => null,
        'en_name' => ['bad'],
        'es_name' => null,
        'level' => 'not-a-number',
        'color' => null,
        'multiplier' => 'not-a-float',
        'sort_order' => null,
        'is_active' => true,
    ]))->toThrow(ValidationException::class);
});
