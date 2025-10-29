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
        if (!User::where('email', 'haidarahmad421@gmail.com')->exists()) {
            User::create([
                'name' => 'Haidar Ahmad',
                'email' => 'haidarahmad421@gmail.com',
                'phone_number' => '0999999999',
                'password' => Hash::make('haidar@123'), // يمكنك تغييره
                'role' => 'admin',
                'otp_verified' => true,
            ]);

            $this->command->info('✅ Admin user created successfully!');
        } else {
            $this->command->warn('⚠️ Admin user already exists.');
        }
    }
}
