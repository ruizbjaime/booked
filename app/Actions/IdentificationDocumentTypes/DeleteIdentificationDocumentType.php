<?php

namespace App\Actions\IdentificationDocumentTypes;

use App\Models\IdentificationDocumentType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteIdentificationDocumentType
{
    /**
     * Delete the document type if it has no associated users, otherwise deactivate it.
     *
     * @return bool True if deleted, false if deactivated.
     */
    public function handle(User $actor, IdentificationDocumentType $type): bool
    {
        Gate::forUser($actor)->authorize('delete', $type);

        return DB::transaction(function () use ($type): bool {
            $locked = IdentificationDocumentType::query()->lockForUpdate()->findOrFail($type->id);

            if ($locked->users()->exists()) {
                $locked->update(['is_active' => false]);

                return false;
            }

            $locked->delete();

            return true;
        });
    }
}
