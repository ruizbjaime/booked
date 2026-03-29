<?php

use App\Actions\Properties\UpdatePropertyAvatar;
use App\Models\Property;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Storage::fake('public');
    Storage::fake('tmp-for-tests');
});

function makePropertyTempUpload(UploadedFile $file): TemporaryUploadedFile
{
    $stored = $file->store('livewire-tmp', 'tmp-for-tests');

    return TemporaryUploadedFile::createFromLivewire(basename($stored));
}

test('host can upload avatar for own property', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    $photo = makePropertyTempUpload(UploadedFile::fake()->image('avatar.jpg', 200, 200));

    app(UpdatePropertyAvatar::class)->handle($host, $property, $photo);

    expect($property->fresh()->avatarUrl())->not->toBeNull();
});

test('rejects non image file', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    $file = makePropertyTempUpload(UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'));

    expect(fn () => app(UpdatePropertyAvatar::class)->handle($host, $property, $file))
        ->toThrow(ValidationException::class);
});

test('rejects unsupported mime type', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    $file = makePropertyTempUpload(UploadedFile::fake()->image('avatar.gif'));

    expect(fn () => app(UpdatePropertyAvatar::class)->handle($host, $property, $file))
        ->toThrow(ValidationException::class);
});

test('replaces previous avatar on new upload', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    $first = makePropertyTempUpload(UploadedFile::fake()->image('first.jpg', 200, 200));
    app(UpdatePropertyAvatar::class)->handle($host, $property, $first);

    $second = makePropertyTempUpload(UploadedFile::fake()->image('second.jpg', 200, 200));
    app(UpdatePropertyAvatar::class)->handle($host, $property, $second);

    expect($property->fresh()->getMedia('avatar'))->toHaveCount(1);
});

test('non owner host cannot upload avatar', function () {
    $host = makeHost();
    $otherHost = makeHost();
    $property = Property::factory()->forUser($host)->create();

    $photo = makePropertyTempUpload(UploadedFile::fake()->image('avatar.jpg', 200, 200));

    expect(fn () => app(UpdatePropertyAvatar::class)->handle($otherHost, $property, $photo))
        ->toThrow(AuthorizationException::class);
});

test('guest cannot upload property avatar', function () {
    $guest = makeGuest();
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    $photo = makePropertyTempUpload(UploadedFile::fake()->image('avatar.jpg', 200, 200));

    expect(fn () => app(UpdatePropertyAvatar::class)->handle($guest, $property, $photo))
        ->toThrow(AuthorizationException::class);
});

test('avatar upload falls back to config avatar size when system setting is zero', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    SystemSetting::instance()->update(['avatar_size' => 0]);
    SystemSetting::clearCache();

    $photo = makePropertyTempUpload(UploadedFile::fake()->image('avatar.jpg', 400, 400));

    app(UpdatePropertyAvatar::class)->handle($host, $property, $photo);

    $media = $property->fresh()->getFirstMedia('avatar');
    $dimensions = getimagesize($media->getPath());

    expect($media)->not->toBeNull()
        ->and($dimensions)->not->toBeFalse()
        ->and($dimensions[0])->toBeGreaterThan(0);
});

test('avatar upload uses configured size and format', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    SystemSetting::instance()->update([
        'avatar_size' => 96,
        'avatar_format' => 'webp',
        'avatar_quality' => 80,
    ]);
    SystemSetting::clearCache();

    $photo = makePropertyTempUpload(UploadedFile::fake()->image('avatar.jpg', 320, 240));

    app(UpdatePropertyAvatar::class)->handle($host, $property, $photo);

    $media = $property->fresh()->getFirstMedia('avatar');
    $dimensions = getimagesize($media->getPath());

    expect($media)->not->toBeNull()
        ->and($media?->file_name)->toBe('avatar.webp')
        ->and($dimensions)->not->toBeFalse()
        ->and($dimensions[0])->toBe(96)
        ->and($dimensions[1])->toBe(96);
});
