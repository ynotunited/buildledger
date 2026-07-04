<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class BuildLedgerCredentialsSeeder extends Seeder
{
    /**
     * Seed the known BuildLedger login accounts.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'MadeItCodes Admin',
                'email' => 'admin@madeitcodes.online',
                'password' => 'M4deItC0des!Admin#7Qv9',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Tony Olugbusi',
                'email' => 'tony@madeitcodes.online',
                'password' => 'TonyDemo123!',
                'role' => User::ROLE_OWNER,
            ],
            [
                'name' => 'BuildLedger Owner',
                'email' => 'test.owner@buildledger.local',
                'password' => 'BuildLedger123!',
                'role' => User::ROLE_OWNER,
            ],
            [
                'name' => 'BuildLedger Client',
                'email' => 'test.client@buildledger.local',
                'password' => 'BuildLedgerClient123!',
                'role' => User::ROLE_CLIENT,
            ],
        ];

        foreach ($users as $userData) {
            User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'role' => $userData['role'],
                    'email_verified_at' => now(),
                    'trial_ends_at' => now()->addYears(10),
                    'google_id' => null,
                    'email_verification_token' => null,
                    'email_verification_sent_at' => null,
                ]
            );
        }
    }
}
