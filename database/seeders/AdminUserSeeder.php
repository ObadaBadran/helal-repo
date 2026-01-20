<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // تحقق إن كان يوجد أدمن مسبقاً
        if (!User::where('email', 'helal@gmail.com')->exists()) {
            User::create([
                'name' => 'Helal Aljaberi',
                'email' => 'helal@gmail.com',
                'phone_number' => '0999999999',
                'password' => Hash::make('helal@779'), // يمكنك تغييره
                'role' => 'admin',
                'otp_verified' => true,
            ]);

            $this->command->info('✅ Admin user created successfully!');
        } else {
            $this->command->warn('⚠️ Admin user already exists.');
        }
    }
}
