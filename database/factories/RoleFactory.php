<?php

namespace Database\Factories;

use App\Actions\Roles\CreateRole;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'guard_name' => 'web',
            'en_label' => fake()->words(2, true),
            'es_label' => fake()->words(2, true),
            'color' => fake()->randomElement(CreateRole::AVAILABLE_COLORS),
            'sort_order' => 999,
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
