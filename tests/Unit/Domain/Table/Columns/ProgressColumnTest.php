<?php

use App\Domain\Table\Columns\ProgressColumn;
use Illuminate\Database\Eloquent\Model;

it('resolves color from a string value', function () {
    $column = ProgressColumn::make('progress')->color('blue');
    $model = Mockery::mock(Model::class);

    expect($column->resolveColor($model))->toBe('blue');
});
