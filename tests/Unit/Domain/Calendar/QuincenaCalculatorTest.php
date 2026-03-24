<?php

use App\Domain\Calendar\Services\QuincenaCalculator;
use Carbon\CarbonImmutable;

it('detects dates adjacent to the 15th', function (int $day, bool $expected) {
    $date = CarbonImmutable::createStrict(2026, 3, $day);

    expect(QuincenaCalculator::isQuincenaAdjacent($date))->toBe($expected);
})->with([
    'day 12 (3 days before 15th)' => [12, false],
    'day 13 (2 days before 15th)' => [13, true],
    'day 14 (1 day before 15th)' => [14, true],
    'day 15 (quincena day)' => [15, true],
    'day 16 (1 day after 15th)' => [16, true],
    'day 17 (2 days after 15th)' => [17, true],
    'day 18 (3 days after 15th)' => [18, false],
]);

it('detects dates adjacent to last day of month', function (int $day, bool $expected) {
    $date = CarbonImmutable::createStrict(2026, 3, $day);

    expect(QuincenaCalculator::isQuincenaAdjacent($date))->toBe($expected);
})->with([
    'day 28 (3 days before 31st)' => [28, false],
    'day 29 (2 days before 31st)' => [29, true],
    'day 30 (1 day before 31st)' => [30, true],
    'day 31 (last day)' => [31, true],
]);

it('handles February correctly', function () {
    $feb26 = CarbonImmutable::createStrict(2026, 2, 26);
    $feb28 = CarbonImmutable::createStrict(2026, 2, 28);

    expect(QuincenaCalculator::isQuincenaAdjacent($feb26))->toBeTrue()
        ->and(QuincenaCalculator::isQuincenaAdjacent($feb28))->toBeTrue();
});

it('handles leap year February', function () {
    $feb29 = CarbonImmutable::createStrict(2028, 2, 29);

    expect(QuincenaCalculator::isQuincenaAdjacent($feb29))->toBeTrue();
});

it('returns false for mid-month non-adjacent dates', function () {
    $date = CarbonImmutable::createStrict(2026, 6, 10);

    expect(QuincenaCalculator::isQuincenaAdjacent($date))->toBeFalse();
});
