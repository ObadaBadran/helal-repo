<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
        /*$this->call([
             CourseSeeder::class,
             VideoSeeder::class,
             NewsSectionSeeder::class,
             PrivateLessonsSeeder::class,
             ConsultationInformationSeeder::class,
             CourseOnlineSeeder::class,
             PrivateLessonInformationSeeder::class
         ]);*/
    }
}
