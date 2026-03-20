<?php

namespace App\Actions\BedTypes;

use App\Models\BedType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBedType
{
    public function handle(User $actor, BedType $bedType, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $bedType);

        $normalized = match ($field) {
            'name' => is_string($value) ? Str::lower(trim($value)) : $value,
            default => $value,
        };

        $this->validate($bedType, $field, $normalized);

        $bedType->update([$field => $normalized]);
    }

    private function validate(BedType $bedType, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('bed_types', 'name')->ignore($bedType->id)],
            'name_en' => ['required', 'string', 'max:255'],
            'name_es' => ['required', 'string', 'max:255'],
            'bed_capacity' => ['required', 'integer', 'min:1'],
            'sort_order' => ['required', 'integer', 'min:0'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
