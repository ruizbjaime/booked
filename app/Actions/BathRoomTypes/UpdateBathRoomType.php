<?php

namespace App\Actions\BathRoomTypes;

use App\Models\BathRoomType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateBathRoomType
{
    public function handle(User $actor, BathRoomType $bathRoomType, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $bathRoomType);

        $normalized = match ($field) {
            'en_name', 'es_name', 'description' => is_string($value) ? trim($value) : $value,
            default => $value,
        };

        $this->validate($bathRoomType, $field, $normalized);

        $bathRoomType->update([$field => $normalized]);
    }

    private function validate(BathRoomType $bathRoomType, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'en_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-_]+$/u'],
            'es_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-_]+$/u'],
            'description' => ['required', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
