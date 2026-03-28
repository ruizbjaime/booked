<?php

use App\Concerns\FormatsLocalizedDates;
use Carbon\CarbonImmutable;
use Tests\TestCase;

pest()->extend(TestCase::class);

it('returns empty strings when formatting null dates', function () {
    $formatter = new class
    {
        use FormatsLocalizedDates;
    };

    expect($formatter->formatDate(null))->toBe('')
        ->and($formatter->formatDateTooltip(null))->toBe('');
});

it('formats localized dates without mutating the original instance', function () {
    $formatter = new class
    {
        use FormatsLocalizedDates;
    };

    $date = CarbonImmutable::parse('2026-08-15 14:30:00')->locale('en');

    $this->app->setLocale('en');
    $englishDate = $formatter->formatDate($date);
    $englishTooltip = $formatter->formatDateTooltip($date);

    $this->app->setLocale('es');
    $spanishDate = $formatter->formatDate($date);
    $spanishTooltip = $formatter->formatDateTooltip($date);

    expect($englishDate)->not->toBe('')
        ->and($englishTooltip)->not->toBe('')
        ->and($spanishDate)->not->toBe('')
        ->and($spanishTooltip)->not->toBe('')
        ->and($englishDate)->not->toBe($spanishDate)
        ->and($englishTooltip)->not->toBe($spanishTooltip)
        ->and($date->locale)->toBe('en');
});
