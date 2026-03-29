<?php

use App\Concerns\HasSlug;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('generates slug from en_name on creation', function () {
    $platform = Platform::factory()->create(['en_name' => 'Direct Booking']);

    expect($platform->slug)->toBe('direct-booking');
});

it('preserves an explicitly set slug on creation', function () {
    $platform = Platform::factory()->create([
        'slug' => 'my-custom-slug',
        'en_name' => 'Direct Booking',
    ]);

    expect($platform->slug)->toBe('my-custom-slug');
});

it('regenerates slug when slugSourceField changes on update', function () {
    $platform = Platform::factory()->create(['en_name' => 'Old Name']);

    $platform->update(['en_name' => 'New Platform Name']);

    expect($platform->fresh()->slug)->toBe('new-platform-name');
});

it('does not regenerate slug when slugSourceField is unchanged on update', function () {
    $platform = Platform::factory()->create([
        'slug' => 'my-fixed-slug',
        'en_name' => 'Some Name',
    ]);

    $platform->update(['es_name' => 'Otro Nombre']);

    expect($platform->fresh()->slug)->toBe('my-fixed-slug');
});

it('appends a random 4-char suffix on slug collision', function () {
    Platform::factory()->create([
        'slug' => 'booking-com',
        'en_name' => 'Booking Com One',
    ]);

    $second = Platform::factory()->create(['en_name' => 'Booking Com']);

    expect($second->slug)->toStartWith('booking-com-')
        ->and(strlen($second->slug))->toBe(strlen('booking-com-') + 4);
});

it('generates slug from class basename when source field produces empty string', function () {
    $platform = Platform::factory()->create(['en_name' => '--- ---']);

    expect($platform->slug)->toStartWith('platform');
});

it('generateUniqueSlug returns base slug when no collision exists', function () {
    $slug = Platform::generateUniqueSlug('My Platform');

    expect($slug)->toBe('my-platform');
});

it('generateUniqueSlug excludes the ignored model from uniqueness check on update', function () {
    $platform = Platform::factory()->create([
        'slug' => 'booking-com',
        'en_name' => 'Booking Com',
    ]);

    $result = Platform::generateUniqueSlug('Booking Com', $platform);

    expect($result)->toBe('booking-com');
});

it('repairs a persisted slug collision after creation', function () {
    Schema::create('test_slug_models', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->timestamps();
    });

    $model = new class extends Model
    {
        use HasSlug;

        protected $table = 'test_slug_models';

        protected $guarded = [];

        public static function generateUniqueSlug(string $source, ?Model $ignore = null): string
        {
            return self::slugBase($source);
        }
    };

    $modelClass = $model::class;

    $first = $modelClass::query()->create(['name' => 'Booking Com']);
    $second = $modelClass::query()->create(['name' => 'Booking Com']);

    expect($first->fresh()->slug)->toBe('booking-com')
        ->and($second->fresh()->slug)->toMatch('/^booking-com-[a-z]{4}$/');
});

it('resolvePersistedSlugCollision exits early when the model is not yet persisted', function () {
    $model = new Platform(['en_name' => 'Test Platform']);

    $method = new ReflectionMethod(Platform::class, 'resolvePersistedSlugCollision');
    $method->invoke(null, $model, 'en_name');

    expect($model->getKey())->toBeNull();
});

it('repairs a post-save slug collision via the repair loop', function () {
    Schema::create('test_slug_repair_models', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->timestamps();
    });

    $instance = new class extends Model
    {
        use HasSlug;

        protected $table = 'test_slug_repair_models';

        protected $guarded = [];

        protected static function assignGeneratedSlug(Model $model, string $sourceField, bool $acquireLock = false): void
        {
            // Always assign base slug without a uniqueness check — simulates concurrent creation race condition
            $source = $model->getAttribute($sourceField);
            $base = self::slugBase(is_string($source) ? $source : '');
            $model->setAttribute('slug', $base);
        }
    };

    $class = $instance::class;

    $first = $class::query()->create(['name' => 'Test Platform']);
    $second = $class::query()->create(['name' => 'Test Platform']);

    expect($first->fresh()->slug)->toBe('test-platform')
        ->and($second->fresh()->slug)->toMatch('/^test-platform-[a-z]{4}$/');
});

it('isUniqueConstraintViolation correctly identifies unique constraint violations', function (string $sqlState, ?int $driverCode, string $message, bool $expected) {
    $pdo = new PDOException($message);
    $pdo->errorInfo = [$sqlState, $driverCode, $message];

    $exception = new QueryException('test', 'UPDATE foo SET slug = ?', [], $pdo);

    $method = new ReflectionMethod(Platform::class, 'isUniqueConstraintViolation');
    $result = $method->invoke(null, $exception);

    expect($result)->toBe($expected);
})->with([
    'SQLSTATE 23000 (MySQL)' => ['23000', 1062, 'Duplicate entry for key', true],
    'SQLSTATE 23505 (PostgreSQL)' => ['23505', null, 'duplicate key value violates unique constraint', true],
    'driver code 19 (SQLite)' => ['HY000', 19, 'UNIQUE constraint failed', true],
    '"unique constraint" in message' => ['HY000', null, 'unique constraint failed', true],
    '"duplicate entry" in message' => ['HY000', null, 'Duplicate entry for key', true],
    'non-unique error' => ['22003', null, 'numeric value out of range', false],
]);

it('uses a numeric fallback suffix when random candidates are exhausted', function () {
    Schema::create('test_slug_exhaustion_models', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->timestamps();
    });

    $model = new class extends Model
    {
        use HasSlug;

        protected $table = 'test_slug_exhaustion_models';

        protected $guarded = [];

        protected static function randomAlphaSuffix(int $length): string
        {
            return str_repeat('a', $length);
        }
    };

    $modelClass = $model::class;

    $modelClass::query()->create(['name' => 'Booking Com']);
    $modelClass::query()->create(['name' => 'Booking Com', 'slug' => 'booking-com-aaaa']);

    $third = $modelClass::query()->create(['name' => 'Booking Com']);

    expect($third->fresh()->slug)->toBe('booking-com-2');
});
