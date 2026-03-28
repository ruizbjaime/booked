<?php

use App\Actions\Calendar\BuildSeasonBlockPayload;
use App\Domain\Calendar\Enums\SeasonStrategy;
use Illuminate\Validation\ValidationException;

function validSeasonBlockPayload(array $overrides = []): array
{
    return array_merge([
        'name' => ' mid_year_break ',
        'en_name' => ' Mid-year Break ',
        'es_name' => ' Receso de Mitad de Año ',
        'calculation_strategy' => SeasonStrategy::FixedRange->value,
        'fixed_start_month' => '6',
        'fixed_start_day' => '1',
        'fixed_end_month' => '6',
        'fixed_end_day' => '30',
        'priority' => '8',
        'sort_order' => '8',
        'is_active' => true,
    ], $overrides);
}

it('normalizes a valid fixed range season block payload', function () {
    $payload = app(BuildSeasonBlockPayload::class)->handle(validSeasonBlockPayload());

    expect($payload)->toMatchArray([
        'name' => 'mid_year_break',
        'en_name' => 'Mid-year Break',
        'es_name' => 'Receso de Mitad de Año',
        'calculation_strategy' => SeasonStrategy::FixedRange->value,
        'fixed_start_month' => 6,
        'fixed_start_day' => 1,
        'fixed_end_month' => 6,
        'fixed_end_day' => 30,
        'priority' => 8,
        'sort_order' => 8,
        'is_active' => true,
    ]);
});

it('requires fixed range dates for fixed range season blocks', function () {
    expect(fn () => app(BuildSeasonBlockPayload::class)->handle(validSeasonBlockPayload([
        'fixed_start_month' => null,
        'fixed_start_day' => null,
    ])))->toThrow(ValidationException::class);
});

it('rejects invalid fixed range calendar dates', function () {
    expect(fn () => app(BuildSeasonBlockPayload::class)->handle(validSeasonBlockPayload([
        'fixed_start_month' => 2,
        'fixed_start_day' => 31,
    ])))->toThrow(ValidationException::class);
});

it('rejects fixed range season blocks whose end date is before the start date', function () {
    expect(fn () => app(BuildSeasonBlockPayload::class)->handle(validSeasonBlockPayload([
        'fixed_start_month' => 7,
        'fixed_start_day' => 10,
        'fixed_end_month' => 7,
        'fixed_end_day' => 5,
    ])))->toThrow(ValidationException::class);
});

it('allows managed season strategies without fixed dates', function () {
    $payload = app(BuildSeasonBlockPayload::class)->handle(validSeasonBlockPayload([
        'calculation_strategy' => SeasonStrategy::HolyWeek->value,
        'fixed_start_month' => null,
        'fixed_start_day' => null,
        'fixed_end_month' => null,
        'fixed_end_day' => null,
    ]));

    expect($payload['calculation_strategy'])->toBe(SeasonStrategy::HolyWeek->value)
        ->and($payload['fixed_start_month'])->toBeNull()
        ->and($payload['fixed_end_day'])->toBeNull();
});
