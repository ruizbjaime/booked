<?php

namespace App\Policies;

use App\Models\IdentificationDocumentType;
use App\Models\User;

class IdentificationDocumentTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('identification_document_type.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, IdentificationDocumentType $type): bool
    {
        return $user->checkPermissionTo('identification_document_type.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('identification_document_type.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, IdentificationDocumentType $type): bool
    {
        return $user->checkPermissionTo('identification_document_type.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, IdentificationDocumentType $type): bool
    {
        return $user->checkPermissionTo('identification_document_type.delete');
    }
}
