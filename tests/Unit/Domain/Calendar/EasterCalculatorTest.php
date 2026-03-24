<?php

use App\Domain\Calendar\Services\EasterCalculator;

it('calculates correct Easter dates for known years', function (int $year, string $expected) {
    $easter = EasterCalculator::forYear($year);

    expect($easter->toDateString())->toBe($expected);
})->with([
    '2020' => [2020, '2020-04-12'],
    '2021' => [2021, '2021-04-04'],
    '2022' => [2022, '2022-04-17'],
    '2023' => [2023, '2023-04-09'],
    '2024' => [2024, '2024-03-31'],
    '2025' => [2025, '2025-04-20'],
    '2026' => [2026, '2026-04-05'],
    '2027' => [2027, '2027-03-28'],
    '2028' => [2028, '2028-04-16'],
    '2029' => [2029, '2029-04-01'],
    '2030' => [2030, '2030-04-21'],
]);

it('always returns a Sunday', function () {
    for ($year = 2020; $year <= 2030; $year++) {
        expect(EasterCalculator::forYear($year)->isSunday())->toBeTrue();
    }
});
