<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Contract> */
class ContractFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'client_id'     => Client::factory(),
            'proposal_id'   => null,
            'title'         => 'Contract for ' . fake()->bs(),
            'body_content'  => fake()->paragraphs(3, true),
            'status'        => 'Sent',
            'sent_at'       => now(),
            'signing_token' => Str::uuid(),
            'signing_token_expires_at' => now()->addDays(7),
        ];
    }
}
