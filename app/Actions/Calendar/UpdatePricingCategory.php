<?php

namespace App\Actions\Calendar;

use App\Models\PricingCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdatePricingCategory
{
    public function __construct(
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    public function handle(User $actor, PricingCategory $category, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $category);

        $normalized = match ($field) {
            'name' => is_string($value) ? Str::lower(trim($value)) : $value,
            'en_name', 'es_name', 'color' => is_string($value) ? trim($value) : $value,
            default => $value,
        };

        $this->validate($category, $field, $normalized);

        $category->update([$field => $normalized]);

        $this->freshnessTimestamp->stampModel($category);
    }

    private function validate(PricingCategory $category, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('pricing_categories', 'name')->ignore($category->id)],
            'en_name' => ['required', 'string', 'max:255'],
            'es_name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:10', Rule::unique('pricing_categories', 'level')->ignore($category->id)],
            'color' => ['required', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'multiplier' => ['required', 'numeric', 'min:0.01', 'max:99.99'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
