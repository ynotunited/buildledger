<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'client_id'   => Client::factory(),
            'title'       => fake()->bs(),
            'description' => fake()->sentence(),
            'status'      => fake()->randomElement(['Planning', 'Active', 'On Hold', 'Completed']),
            'start_date'  => now()->subDays(5),
            'end_date'    => now()->addDays(30),
            'budget'      => fake()->numberBetween(100000, 5000000),
        ];
    }
}
