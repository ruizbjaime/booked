<?php

use App\Actions\Properties\UpdateProperty;
use App\Models\Country;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-host updates a property', function () {
    $guest = makeGuest();
    $property = Property::factory()->create();

    app(UpdateProperty::class)->handle($guest, $property, 'name', 'Updated Property');
})->throws(AuthorizationException::class);

it('updates the name and regenerates slug', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['name' => 'Old Name']);

    app(UpdateProperty::class)->handle($host, $property, 'name', 'New Name');

    $fresh = $property->fresh();

    expect($fresh->name)->toBe('New Name')
        ->and($fresh->slug)->toContain('new-name');
});

it('trims whitespace from name', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'name', '  Beach House  ');

    expect($property->fresh()->name)->toBe('Beach House');
});

it('updates the city', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['city' => 'Bogotá']);

    app(UpdateProperty::class)->handle($host, $property, 'city', 'Medellín');

    expect($property->fresh()->city)->toBe('Medellín');
});

it('updates the address', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['address' => 'Calle 1']);

    app(UpdateProperty::class)->handle($host, $property, 'address', 'Calle 2 #10-20');

    expect($property->fresh()->address)->toBe('Calle 2 #10-20');
});

it('updates the country', function () {
    $host = makeHost();
    $newCountry = Country::factory()->create();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'country_id', $newCountry->id);

    expect($property->fresh()->country_id)->toBe($newCountry->id);
});

it('rejects an inactive country', function () {
    $host = makeHost();
    $inactiveCountry = Country::factory()->inactive()->create();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'country_id', $inactiveCountry->id);
})->throws(ValidationException::class);

it('updates the active state', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['is_active' => true]);

    app(UpdateProperty::class)->handle($host, $property, 'is_active', false);

    expect($property->fresh()->is_active)->toBeFalse();
});

it('rejects invalid active state values', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['is_active' => true]);

    app(UpdateProperty::class)->handle($host, $property, 'is_active', 'not-a-bool');
})->throws(ValidationException::class);

it('updates base_capacity', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['base_capacity' => null]);

    app(UpdateProperty::class)->handle($host, $property, 'base_capacity', 3);

    expect($property->fresh()->base_capacity)->toBe(3);
});

it('updates max_capacity', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['max_capacity' => null]);

    app(UpdateProperty::class)->handle($host, $property, 'max_capacity', 8);

    expect($property->fresh()->max_capacity)->toBe(8);
});

it('clears base_capacity when set to null', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->withCapacity(2, 6)->create();

    app(UpdateProperty::class)->handle($host, $property, 'base_capacity', null);

    expect($property->fresh()->base_capacity)->toBeNull();
});

it('clears max_capacity when set to null', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->withCapacity(2, 6)->create();

    app(UpdateProperty::class)->handle($host, $property, 'max_capacity', null);

    expect($property->fresh()->max_capacity)->toBeNull();
});

it('normalizes empty string to null for base_capacity', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->withCapacity(2, 6)->create();

    app(UpdateProperty::class)->handle($host, $property, 'base_capacity', '');

    expect($property->fresh()->base_capacity)->toBeNull();
});

it('normalizes empty string to null for max_capacity', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->withCapacity(2, 6)->create();

    app(UpdateProperty::class)->handle($host, $property, 'max_capacity', '');

    expect($property->fresh()->max_capacity)->toBeNull();
});

it('rejects base_capacity of zero', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'base_capacity', 0);
})->throws(ValidationException::class);

it('rejects negative base_capacity', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'base_capacity', -1);
})->throws(ValidationException::class);

it('rejects base_capacity exceeding 255', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'base_capacity', 256);
})->throws(ValidationException::class);

it('rejects max_capacity of zero', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'max_capacity', 0);
})->throws(ValidationException::class);

it('rejects base_capacity exceeding max_capacity', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['base_capacity' => null, 'max_capacity' => 4]);

    app(UpdateProperty::class)->handle($host, $property, 'base_capacity', 5);
})->throws(ValidationException::class);

it('rejects max_capacity below base_capacity', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['base_capacity' => 5, 'max_capacity' => null]);

    app(UpdateProperty::class)->handle($host, $property, 'max_capacity', 3);
})->throws(ValidationException::class);

it('allows base_capacity equal to max_capacity', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['base_capacity' => null, 'max_capacity' => 4]);

    app(UpdateProperty::class)->handle($host, $property, 'base_capacity', 4);

    expect($property->fresh()->base_capacity)->toBe(4);
});

it('allows setting base_capacity when max_capacity is null', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['base_capacity' => null, 'max_capacity' => null]);

    app(UpdateProperty::class)->handle($host, $property, 'base_capacity', 5);

    expect($property->fresh()->base_capacity)->toBe(5);
});

it('updates the description', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['description' => null]);

    app(UpdateProperty::class)->handle($host, $property, 'description', '<p>A lovely beach house.</p>');

    expect($property->fresh()->description)->toBe('<p>A lovely beach house.</p>');
});

it('trims whitespace from description', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'description', '  <p>Trimmed</p>  ');

    expect($property->fresh()->description)->toBe('<p>Trimmed</p>');
});

it('clears description when set to null', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['description' => '<p>Old</p>']);

    app(UpdateProperty::class)->handle($host, $property, 'description', null);

    expect($property->fresh()->description)->toBeNull();
});

it('normalizes empty string to null for description', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['description' => '<p>Old</p>']);

    app(UpdateProperty::class)->handle($host, $property, 'description', '');

    expect($property->fresh()->description)->toBeNull();
});

it('strips disallowed HTML tags from description', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'description', '<p>Safe</p><script>alert(1)</script>');

    expect($property->fresh()->description)->toBe('<p>Safe</p>');
});

it('removes javascript hrefs from description links', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'description', '<p><a href="javascript:alert(1)">click</a></p>');

    expect($property->fresh()->description)->not->toContain('javascript:');
});

it('preserves valid https links in description', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'description', '<p><a href="https://example.com">link</a></p>');

    expect($property->fresh()->description)->toContain('href="https://example.com"');
});

it('aborts with 422 for an unknown property field', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    try {
        app(UpdateProperty::class)->handle($host, $property, 'unknown_field', 'value');
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422);
    }
});
