<?php

use App\Domain\Table\CardZone;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\MailtoColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Model;

it('has correct type for text column', function () {
    expect(TextColumn::make('name')->type())->toBe('text');
});

it('has correct type for id column', function () {
    expect(IdColumn::make('id')->type())->toBe('id');
});

it('uses footer card zone for id column', function () {
    $column = IdColumn::make('id');

    expect($column->headerClass())->toBe('w-16')
        ->and($column->cardZone())->toBe(CardZone::Footer);
});

it('has correct type for mailto column', function () {
    expect(MailtoColumn::make('email')->type())->toBe('mailto');
});

it('has correct type for toggle column', function () {
    expect(ToggleColumn::make('is_active')->type())->toBe('toggle');
});

it('has w-20 header class for toggle column', function () {
    expect(ToggleColumn::make('is_active')->headerClass())->toBe('w-20');
});

it('stores wireChange method for toggle column', function () {
    $column = ToggleColumn::make('is_active')->wireChange('toggleActive');

    expect($column->wireChange())->toBe('toggleActive');
});

it('supports disabled condition as boolean for toggle column', function () {
    $record = Mockery::mock(Model::class);
    $column = ToggleColumn::make('is_active')->disabled(true);

    expect($column->isDisabled($record))->toBeTrue();
});

it('supports disabled condition as closure for toggle column', function () {
    $record = Mockery::mock(Model::class);
    $column = ToggleColumn::make('is_active')->disabled(fn () => true);

    expect($column->isDisabled($record))->toBeTrue();
});

it('stores and retrieves id prefix for toggle column', function () {
    $column = ToggleColumn::make('is_active')->idPrefix('user');

    expect($column->idPrefix())->toBe('user');
});
