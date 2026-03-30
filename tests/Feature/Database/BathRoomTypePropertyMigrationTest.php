<?php

use Illuminate\Support\Facades\Schema;

test('shared bathroom property pivot migration creates quantity and timestamps', function () {
    expect(Schema::hasColumns('bath_room_type_property', [
        'id',
        'property_id',
        'bath_room_type_id',
        'quantity',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
