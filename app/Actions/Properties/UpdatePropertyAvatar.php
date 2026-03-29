<?php

namespace App\Actions\Properties;

use App\Models\Property;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class UpdatePropertyAvatar
{
    public function handle(User $actor, Property $target, TemporaryUploadedFile $photo): void
    {
        Gate::forUser($actor)->authorize('update', $target);

        $settings = SystemSetting::instance();
        $maxKb = $settings->max_upload_size_mb * 1024;

        Validator::make(
            ['photo' => $photo],
            ['photo' => ['required', 'image', "max:{$maxKb}", 'mimes:jpg,jpeg,png,webp', 'dimensions:max_width=4096,max_height=4096']],
        )->validate();

        $format = $settings->avatar_format;
        $extension = $format->extension();
        $tempPath = sys_get_temp_dir().'/'.Str::uuid()->toString().'.'.$extension;
        $size = $this->avatarSize($settings);

        try {
            Image::load($photo->getRealPath())
                ->fit(Fit::Crop, $size, $size)
                ->quality($settings->avatar_quality)
                ->save($tempPath);

            $target->addMedia($tempPath)
                ->usingFileName("avatar.{$extension}")
                ->toMediaCollection('avatar');
        } finally {
            @unlink($tempPath);
        }
    }

    private function avatarSize(SystemSetting $settings): int
    {
        if ($settings->avatar_size > 0) {
            return $settings->avatar_size;
        }

        $configured = config('media-library.avatar_size', 100);

        return is_int($configured) && $configured > 0 ? $configured : 100;
    }
}
