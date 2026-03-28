<?php

use App\Domain\Table\Columns\AvatarColumn;
use Illuminate\Database\Eloquent\Model;

it('resolves avatar colors from strings and closures', function () {
    $record = Mockery::mock(Model::class);

    $static = AvatarColumn::make('avatar')->color('#A855F7');
    $dynamic = AvatarColumn::make('avatar')->color(fn () => '#22C55E');

    expect($static->resolveColor($record))->toBe('#A855F7')
        ->and($dynamic->resolveColor($record))->toBe('#22C55E')
        ->and($static->hasColor())->toBeTrue();
});

it('falls back safely when avatar callbacks return invalid values', function () {
    $record = Mockery::mock(Model::class);

    $column = AvatarColumn::make('avatar')
        ->avatarSrc(fn () => ['invalid'])
        ->initials(fn () => 123)
        ->color(fn () => ['invalid'])
        ->recordUrl(fn () => ['invalid'])
        ->wireNavigate(false);

    expect($column->resolveAvatarSrc($record))->toBeNull()
        ->and($column->resolveInitials($record))->toBeNull()
        ->and($column->resolveColor($record))->toBeNull()
        ->and($column->resolveRecordUrl($record))->toBeNull()
        ->and($column->shouldWireNavigate())->toBeFalse();
});

it('returns avatar class mappings for supported sizes and default branch', function () {
    expect(AvatarColumn::hexAvatarClasses('xl')['container'])->toContain('size-16')
        ->and(AvatarColumn::hexAvatarClasses('lg')['container'])->toContain('size-12')
        ->and(AvatarColumn::hexAvatarClasses('sm')['container'])->toContain('size-8')
        ->and(AvatarColumn::hexAvatarClasses('xs')['container'])->toContain('size-6')
        ->and(AvatarColumn::hexAvatarClasses('unknown')['container'])->toContain('size-10');
});
