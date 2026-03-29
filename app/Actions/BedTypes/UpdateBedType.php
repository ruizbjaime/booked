<?php

namespace App\Actions\BedTypes;

use App\Models\BedType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateBedType
{
    public function handle(User $actor, BedType $bedType, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $bedType);

        $normalized = match ($field) {
            'en_name', 'es_name' => is_string($value) ? trim($value) : $value,
            default => $value,
        };

        $this->validate($bedType, $field, $normalized);

        $bedType->update([$field => $normalized]);
    }

    private function validate(BedType $bedType, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'en_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-_]+$/u'],
            'es_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-_]+$/u'],
            'bed_capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
