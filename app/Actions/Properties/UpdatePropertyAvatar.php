<?php

namespace App\Actions\Properties;

use App\Domain\Media\AvatarProcessor;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UpdatePropertyAvatar
{
    public function __construct(private AvatarProcessor $avatarProcessor) {}

    public function handle(User $actor, Property $target, TemporaryUploadedFile $photo): void
    {
        Gate::forUser($actor)->authorize('update', $target);

        $this->avatarProcessor->process($target, $photo);
    }
}
