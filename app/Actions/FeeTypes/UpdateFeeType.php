<?php

namespace App\Actions\FeeTypes;

use App\Models\FeeType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateFeeType
{
    public function handle(User $actor, FeeType $feeType, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $feeType);

        $normalized = match ($field) {
            'en_name', 'es_name' => is_string($value) ? trim($value) : $value,
            default => $value,
        };

        $this->validate($feeType, $field, $normalized);

        $feeType->update([$field => $normalized]);
    }

    private function validate(FeeType $feeType, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'en_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-_]+$/u'],
            'es_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-_]+$/u'],
            'order' => ['required', 'integer', 'min:0', 'max:9999'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
