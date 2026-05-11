<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ClientSubaccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'office' => 'Headquarters',
        ]);

        // Create Client User
        $client = User::create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => Hash::make('client123'),
            'role' => 'client',
            'office' => 'Main Office',
        ]);
        //wala natong subaccount
        // Create Subaccount User
        $subaccountUser = User::create([
            'name' => 'Subaccount User',
            'email' => 'subaccount@example.com',
            'password' => Hash::make('subaccount123'),
            'role' => 'subaccount',
            'office' => 'Main Office',
            'parent_client_id' => $client->id,
        ]);

        // Create Subaccount
        ClientSubaccount::create([
            'client_user_id' => $client->id,
            'user_id' => $subaccountUser->id,
            'name' => 'Logistics Team',
            'description' => 'Subaccount for logistics distribution',
        ]);
    }
}
