<?php

declare(strict_types=1);

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Workbench\App\Models\User;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    /**
     * @var class-string<User>
     */
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            // Hashed, and never typed anywhere. The demo exists to show that
            // you do not need this value, but the column is not nullable and
            // a user provider that finds a broken hash is a distraction.
            'password' => Hash::make('demo-password-nobody-types'),
        ];
    }
}
