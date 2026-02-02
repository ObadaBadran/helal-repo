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
        if (!User::where('email', 'He779@tikit.ae')->exists()) {
            User::create([
                'name' => 'Helal Aljaberi',
                'email' => 'He779@tikit.ae',
                'phone_number' => '+971503338444',
                'password' => Hash::make('Helal@123'), // يمكنك تغييره
                'role' => 'admin',
                'otp_verified' => true,
            ]);

            $this->command->info('✅ Admin user created successfully!');
        } else {
            $this->command->warn('⚠️ Admin user already exists.');
        }
    }
}
