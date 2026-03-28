<?php

use App\Domain\Table\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Model;

it('returns null src when no src callback is set', function () {
    $column = ImageColumn::make('avatar');
    $model = Mockery::mock(Model::class);

    expect($column->resolveSrc($model))->toBeNull();
});

it('resolves alt from a string value', function () {
    $column = ImageColumn::make('avatar')->alt('Profile picture');
    $model = Mockery::mock(Model::class);

    expect($column->resolveAlt($model))->toBe('Profile picture');
});

it('resolves alt from a closure', function () {
    $column = ImageColumn::make('avatar')->alt(fn ($model) => "Avatar for {$model->name}");
    $model = Mockery::mock(Model::class)->makePartial();
    $model->shouldReceive('getAttribute')->with('name')->andReturn('Jane');

    expect($column->resolveAlt($model))->toBe('Avatar for Jane');
});
