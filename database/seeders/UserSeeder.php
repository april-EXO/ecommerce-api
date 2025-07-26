<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create a regular user
        User::create([
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'country_code' => 'MY',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        // Create an admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'country_code' => 'MY',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Test users created successfully!');
        $this->command->line('');
        $this->command->line('📧 Regular User: user@example.com | Password: password123');
        $this->command->line('👨‍💼 Admin User: admin@example.com | Password: admin123');
    }
} 