<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\IdentificationDocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            if (! array_key_exists('password', $user->getAttributes())) {
                $user->forceFill([
                    'password' => static::$password ??= Hash::make('password'),
                ]);
            }
        });
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn () => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the model has personal information filled in.
     */
    public function withPersonalInfo(): static
    {
        return $this->state(fn () => [
            'phone' => '+573001234567',
            'country_id' => Country::factory(),
            'document_type_id' => IdentificationDocumentType::factory(),
            'document_number' => fake()->numerify('##########'),
            'state' => fake()->state(),
            'city' => fake()->city(),
            'address' => fake()->address(),
        ]);
    }
}
