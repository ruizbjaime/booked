<?php

namespace App\Actions\BathRoomTypes;

use App\Models\BathRoomType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBathRoomType
{
    public function handle(User $actor, BathRoomType $bathRoomType, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $bathRoomType);

        $normalized = match ($field) {
            'name' => is_string($value) ? Str::lower(trim($value)) : $value,
            'description' => is_string($value) ? trim($value) : $value,
            default => $value,
        };

        $this->validate($bathRoomType, $field, $normalized);

        $bathRoomType->update([$field => $normalized]);
    }

    private function validate(BathRoomType $bathRoomType, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('bath_room_types', 'name')->ignore($bathRoomType->id)],
            'name_en' => ['required', 'string', 'max:255'],
            'name_es' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
