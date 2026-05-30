<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 1;

        return [
            'user_id'        => User::factory(),
            'client_id'      => Client::factory(),
            'invoice_number' => 'INV-' . str_pad($counter++, 4, '0', STR_PAD_LEFT),
            'status'         => fake()->randomElement(['Draft', 'Sent', 'Paid', 'Overdue']),
            'issue_date'     => now()->subDays(10),
            'due_date'       => now()->addDays(20),
            'subtotal'       => 50000,
            'tax'            => 0,
            'discount'       => 0,
            'total'          => 50000,
        ];
    }
}
