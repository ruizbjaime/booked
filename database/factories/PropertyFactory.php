<?php

namespace Database\Factories;

use App\Actions\Properties\GeneratePropertySlug;
use App\Models\Country;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = 'Property '.fake()->unique()->city();

        return [
            'user_id' => User::factory(),
            'slug' => app(GeneratePropertySlug::class)->handle($label),
            'name' => $label,
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'country_id' => Country::factory(),
            'is_active' => true,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
