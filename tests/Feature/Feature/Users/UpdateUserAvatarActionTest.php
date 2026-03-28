<?php

use App\Actions\Users\UpdateUserAvatar;
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

function makeTempUpload(UploadedFile $file): TemporaryUploadedFile
{
    $stored = $file->store('livewire-tmp', 'tmp-for-tests');

    return TemporaryUploadedFile::createFromLivewire(basename($stored));
}

test('admin can upload avatar for another user', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    $photo = makeTempUpload(UploadedFile::fake()->image('avatar.jpg', 200, 200));

    app(UpdateUserAvatar::class)->handle($admin, $target, $photo);

    expect($target->fresh()->avatarUrl())->not->toBeNull();
});

test('user can upload own avatar', function () {
    $user = makeGuest();

    $photo = makeTempUpload(UploadedFile::fake()->image('avatar.png', 150, 150));

    app(UpdateUserAvatar::class)->handle($user, $user, $photo);

    expect($user->fresh()->avatarUrl())->not->toBeNull();
});

test('rejects non image file', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    $file = makeTempUpload(UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'));

    expect(fn () => app(UpdateUserAvatar::class)->handle($admin, $target, $file))
        ->toThrow(ValidationException::class);
});

test('rejects unsupported mime type', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    $file = makeTempUpload(UploadedFile::fake()->image('avatar.gif'));

    expect(fn () => app(UpdateUserAvatar::class)->handle($admin, $target, $file))
        ->toThrow(ValidationException::class);
});

test('replaces previous avatar on new upload', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    $first = makeTempUpload(UploadedFile::fake()->image('first.jpg', 200, 200));
    app(UpdateUserAvatar::class)->handle($admin, $target, $first);

    $second = makeTempUpload(UploadedFile::fake()->image('second.jpg', 200, 200));
    app(UpdateUserAvatar::class)->handle($admin, $target, $second);

    expect($target->fresh()->getMedia('avatar'))->toHaveCount(1);
});

test('non admin cannot upload avatar for another user', function () {
    $guest = makeGuest();
    $target = makeGuest();

    $photo = makeTempUpload(UploadedFile::fake()->image('avatar.jpg', 200, 200));

    expect(fn () => app(UpdateUserAvatar::class)->handle($guest, $target, $photo))
        ->toThrow(AuthorizationException::class);
});

test('avatar upload falls back to config avatar size when system setting is zero', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    SystemSetting::instance()->update(['avatar_size' => 0]);
    SystemSetting::clearCache();

    $photo = makeTempUpload(UploadedFile::fake()->image('avatar.jpg', 400, 400));

    app(UpdateUserAvatar::class)->handle($admin, $target, $photo);

    $media = $target->fresh()->getFirstMedia('avatar');
    $dimensions = getimagesize($media->getPath());

    expect($media)->not->toBeNull()
        ->and($dimensions)->not->toBeFalse()
        ->and($dimensions[0])->toBeGreaterThan(0);
});

test('avatar upload uses configured size and format', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    SystemSetting::instance()->update([
        'avatar_size' => 96,
        'avatar_format' => 'webp',
        'avatar_quality' => 80,
    ]);
    SystemSetting::clearCache();

    $photo = makeTempUpload(UploadedFile::fake()->image('avatar.jpg', 320, 240));

    app(UpdateUserAvatar::class)->handle($admin, $target, $photo);

    $media = $target->fresh()->getFirstMedia('avatar');
    $dimensions = getimagesize($media->getPath());

    expect($media)->not->toBeNull()
        ->and($media?->file_name)->toBe('avatar.webp')
        ->and($dimensions)->not->toBeFalse()
        ->and($dimensions[0])->toBe(96)
        ->and($dimensions[1])->toBe(96);
});
