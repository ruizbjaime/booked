<?php

namespace App\Concerns;

use App\Domain\Users\RoleConfig;

trait HasRolePresentation
{
    public function roleColor(string $role): string
    {
        return RoleConfig::color($role);
    }

    public function roleLabel(string $role): string
    {
        return RoleConfig::label($role);
    }
}
