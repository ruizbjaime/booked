<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    /**
     * Callers MUST apply Property::scopeOwnedBy() to enforce tenant isolation.
     * This policy only checks role + permission, not ownership scoping.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasHostPermission($user, 'property.viewAny');
    }

    public function view(User $user, Property $property): bool
    {
        return $this->hasHostPermission($user, 'property.view')
            && $this->isOwner($user, $property);
    }

    public function create(User $user): bool
    {
        return $this->hasHostPermission($user, 'property.create');
    }

    public function update(User $user, Property $property): bool
    {
        return $this->hasHostPermission($user, 'property.update')
            && $this->isOwner($user, $property);
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->hasHostPermission($user, 'property.delete')
            && $this->isOwner($user, $property);
    }

    private function hasHostPermission(User $user, string $permission): bool
    {
        return $user->hasRole('host') && $user->checkPermissionTo($permission);
    }

    private function isOwner(User $user, Property $property): bool
    {
        if (! $property->exists) {
            return true;
        }

        return $user->id === $property->user_id;
    }
}
