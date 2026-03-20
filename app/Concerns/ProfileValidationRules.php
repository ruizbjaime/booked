<?php

namespace App\Concerns;

use App\Models\Country;
use App\Models\IdentificationDocumentType;
use App\Models\User;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * Get the validation rules for personal information fields.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|Phone|string>>
     */
    protected function personalInformationRules(): array
    {
        return [
            'phone' => ['nullable', 'string', (new Phone)->international()],
            'document_type_id' => ['nullable', 'integer', Rule::exists(IdentificationDocumentType::class, 'id')->where('is_active', true), 'required_with:document_number'],
            'document_number' => ['nullable', 'string', 'max:50', 'required_with:document_type_id'],
            'country_id' => ['nullable', 'integer', Rule::exists(Country::class, 'id')->where('is_active', true)],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
