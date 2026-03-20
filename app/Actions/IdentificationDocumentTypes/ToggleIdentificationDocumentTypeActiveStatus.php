<?php

namespace App\Actions\IdentificationDocumentTypes;

use App\Models\IdentificationDocumentType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ToggleIdentificationDocumentTypeActiveStatus
{
    public function handle(User $actor, IdentificationDocumentType $type, bool $isActive): void
    {
        Gate::forUser($actor)->authorize('update', $type);

        $type->update([
            'is_active' => $isActive,
        ]);
    }
}
