<?php

namespace App\Actions\Calendar;

use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class UpdatePricingRule
{
    public function __construct(
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    public function handle(User $actor, PricingRule $rule, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $rule);

        $normalized = $this->normalize($field, $value);

        $this->validate($rule, $field, $normalized);

        $rule->update([$field => $normalized]);

        $this->freshnessTimestamp->stampModel($rule);
    }

    private function validate(PricingRule $rule, string $field, mixed $value): void
    {
        Validator::make([$field => $value], [$field => $this->rulesFor($rule, $field)])->validate();
    }

    private function normalize(string $field, mixed $value): mixed
    {
        return match ($field) {
            'name' => is_string($value) ? Str::lower(trim($value)) : $value,
            'en_description', 'es_description' => is_string($value) ? trim($value) : $value,
            'conditions' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /** @return array<int, ValidationRule|Unique|Exists|string> */
    private function rulesFor(PricingRule $rule, string $field): array
    {
        return match ($field) {
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('pricing_rules', 'name')->ignore($rule->id)],
            'en_description' => ['required', 'string', 'max:500'],
            'es_description' => ['required', 'string', 'max:500'],
            'pricing_category_id' => ['required', 'integer', Rule::exists('pricing_categories', 'id')],
            'conditions' => ['required', 'array'],
            'priority' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            default => abort(422),
        };
    }
}
