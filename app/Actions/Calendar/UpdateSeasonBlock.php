<?php

namespace App\Actions\Calendar;

use App\Models\SeasonBlock;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateSeasonBlock
{
    public function handle(User $actor, SeasonBlock $block, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $block);

        $normalized = match ($field) {
            'name' => is_string($value) ? Str::lower(trim($value)) : $value,
            'en_name', 'es_name' => is_string($value) ? trim($value) : $value,
            default => $value,
        };

        $this->validate($block, $field, $normalized);

        $block->update([$field => $normalized]);
    }

    private function validate(SeasonBlock $block, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('season_blocks', 'name')->ignore($block->id)],
            'en_name' => ['required', 'string', 'max:255'],
            'es_name' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'integer', 'min:0', 'max:9999'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
