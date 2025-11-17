<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrivateLesson;
use App\Models\PrivateLessonInformation;
use App\Models\Appointment;

class PrivateLessonInformationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $requiredIds = [1, 2, 3, 4];

        $existingIds = PrivateLesson::whereIn('id', $requiredIds)->pluck('id')->toArray();
        $missing = array_diff($requiredIds, $existingIds);

        if (!empty($missing)) {
            $this->command->error('Required private lessons not found. Missing IDs: ' . implode(',', $missing));
            return;
        }

        // إنشاء أو الحصول على Appointment
        /*$appointment = Appointment::first() ?? Appointment::create([
            'date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $appointmentId = $appointment->id;*/

        $privateLessonInformations = [

            [
                'private_lesson_id' => 1,
                'place_en' => 'Online Session - Zoom',
                'place_ar' => 'جلسة أونلاين - زوم',
                'price_aed' => 300,
                'price_usd' => 82,
                'duration' => 60,
            ],
            [
                'private_lesson_id' => 1,
                'place_en' => 'In-Person - Dubai Office',
                'place_ar' => 'حضوري - مكتب دبي',
                'price_aed' => 400,
                'price_usd' => 109,
                'duration' => 60,
            ],
            [
                'private_lesson_id' => 1,
                'place_en' => 'Premium Package - 4 Sessions',
                'place_ar' => 'باقة مميزة - 4 جلسات',
                'price_aed' => 1200,
                'price_usd' => 327,
                'duration' => 60,
            ],


            [
                'private_lesson_id' => 2,
                'place_en' => 'Online Session - Google Meet',
                'place_ar' => 'جلسة أونلاين - جوجل ميت',
                'price_aed' => 350,
                'price_usd' => 95,
                'duration' => 60,
            ],
            [
                'private_lesson_id' => 2,
                'place_en' => 'In-Person - Abu Dhabi',
                'place_ar' => 'حضوري - أبوظبي',
                'price_aed' => 450,
                'price_usd' => 123,
                'duration' => 60,
            ],
            [
                'private_lesson_id' => 2,
                'place_en' => 'Crypto Trading Bootcamp',
                'place_ar' => 'معسكر تداول العملات الرقمية',
                'price_aed' => 800,
                'price_usd' => 218,
                'duration' => 60,
            ],


            [
                'private_lesson_id' => 3,
                'place_en' => 'Online Session - Microsoft Teams',
                'place_ar' => 'جلسة أونلاين - مايكروسوفت تيمز',
                'price_aed' => 320,
                'price_usd' => 87,
                'duration' => 60,
            ],
            [
                'private_lesson_id' => 3,
                'place_en' => 'In-Person - Sharjah',
                'place_ar' => 'حضوري - الشارقة',
                'price_aed' => 420,
                'price_usd' => 114,
                'duration' => 60,
            ],
            [
                'private_lesson_id' => 3,
                'place_en' => 'Online Session - Custom Platform',
                'place_ar' => 'جلسة أونلاين - منصة مخصصة',
                'price_aed' => 380,
                'price_usd' => 104,
                'duration' => 60,
            ],
            [
                'private_lesson_id' => 3,
                'place_en' => 'Intensive Workshop - 3 Sessions',
                'place_ar' => 'ورشة مكثفة - 3 جلسات',
                'price_aed' => 1000,
                'price_usd' => 272,
                'duration' => 60,
            ],
        ];

        foreach ($privateLessonInformations as $info) {
            PrivateLessonInformation::create($info);
        }

        $this->command->info('Private Lesson Informations seeded successfully!');
        $this->command->info('Total created: ' . count($privateLessonInformations));
    }
}
