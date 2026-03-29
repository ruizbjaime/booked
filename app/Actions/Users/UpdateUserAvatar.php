<?php

namespace App\Actions\Users;

use App\Domain\Media\AvatarProcessor;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UpdateUserAvatar
{
    public function __construct(private AvatarProcessor $avatarProcessor) {}

    public function handle(User $actor, User $target, TemporaryUploadedFile $photo): void
    {
        Gate::forUser($actor)->authorize('update', $target);

        $this->avatarProcessor->process($target, $photo);
    }
}
