<?php

use App\Domain\Media\AvatarProcessor;
use App\Models\Property;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Storage::fake('public');
    Storage::fake('tmp-for-tests');
});

function makeAvatarTempUpload(UploadedFile $file): TemporaryUploadedFile
{
    $stored = $file->store('livewire-tmp', 'tmp-for-tests');

    return TemporaryUploadedFile::createFromLivewire(basename($stored));
}

test('processes avatar for a User model', function () {
    $user = makeGuest();

    $photo = makeAvatarTempUpload(UploadedFile::fake()->image('avatar.jpg', 200, 200));

    app(AvatarProcessor::class)->process($user, $photo);

    expect($user->fresh()->avatarUrl())->not->toBeNull();
});

test('processes avatar for a Property model', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    $photo = makeAvatarTempUpload(UploadedFile::fake()->image('avatar.jpg', 200, 200));

    app(AvatarProcessor::class)->process($property, $photo);

    expect($property->fresh()->avatarUrl())->not->toBeNull();
});

test('rejects non image file', function () {
    $user = makeGuest();

    $file = makeAvatarTempUpload(UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'));

    expect(fn () => app(AvatarProcessor::class)->process($user, $file))
        ->toThrow(ValidationException::class);
});

test('rejects unsupported mime type', function () {
    $user = makeGuest();

    $file = makeAvatarTempUpload(UploadedFile::fake()->image('avatar.gif'));

    expect(fn () => app(AvatarProcessor::class)->process($user, $file))
        ->toThrow(ValidationException::class);
});

test('replaces previous avatar on new upload', function () {
    $user = makeGuest();

    $first = makeAvatarTempUpload(UploadedFile::fake()->image('first.jpg', 200, 200));
    app(AvatarProcessor::class)->process($user, $first);

    $second = makeAvatarTempUpload(UploadedFile::fake()->image('second.jpg', 200, 200));
    app(AvatarProcessor::class)->process($user, $second);

    expect($user->fresh()->getMedia('avatar'))->toHaveCount(1);
});

test('falls back to config avatar size when system setting is zero', function () {
    $user = makeGuest();

    SystemSetting::instance()->update(['avatar_size' => 0]);
    SystemSetting::clearCache();

    $photo = makeAvatarTempUpload(UploadedFile::fake()->image('avatar.jpg', 400, 400));

    app(AvatarProcessor::class)->process($user, $photo);

    $media = $user->fresh()->getFirstMedia('avatar');
    $dimensions = getimagesize($media->getPath());

    expect($media)->not->toBeNull()
        ->and($dimensions)->not->toBeFalse()
        ->and($dimensions[0])->toBeGreaterThan(0);
});

test('uses configured size and format', function () {
    $user = makeGuest();

    SystemSetting::instance()->update([
        'avatar_size' => 96,
        'avatar_format' => 'webp',
        'avatar_quality' => 80,
    ]);
    SystemSetting::clearCache();

    $photo = makeAvatarTempUpload(UploadedFile::fake()->image('avatar.jpg', 320, 240));

    app(AvatarProcessor::class)->process($user, $photo);

    $media = $user->fresh()->getFirstMedia('avatar');
    $dimensions = getimagesize($media->getPath());

    expect($media)->not->toBeNull()
        ->and($media?->file_name)->toBe('avatar.webp')
        ->and($dimensions)->not->toBeFalse()
        ->and($dimensions[0])->toBe(96)
        ->and($dimensions[1])->toBe(96);
});
