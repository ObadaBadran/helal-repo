<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\CourseOnline;
use Carbon\Carbon;

class CourseOnlineSeeder extends Seeder
{
    public function run(): void
    {
        $coursesData = [
            [
                'name_en' => 'Forex Trading Fundamentals',
                'name_ar' => 'أساسيات تداول الفوركس',
                'description_en' => 'Learn the basics of forex trading, including currency pairs, market analysis, and risk management strategies for beginners.',
                'description_ar' => 'تعلم أساسيات تداول الفوركس، including أزواج العملات، تحليل السوق، واستراتيجيات إدارة المخاطر للمبتدئين.',
                'price_aed' => 500,
                'price_usd' => 136,
                'date' => '15-12-2024',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'cover_image' => 'course_covers-seed/forex_fundamentals.jpg',
            ],
            [
                'name_en' => 'Advanced Technical Analysis',
                'name_ar' => 'التحليل الفني المتقدم',
                'description_en' => 'Master advanced chart patterns, indicators, and trading strategies used by professional traders.',
                'description_ar' => 'إتقان أنماط الرسوم البيانية المتقدمة، المؤشرات، واستراتيجيات التداول التي يستخدمها المتداولون المحترفون.',
                'price_aed' => 750,
                'price_usd' => 204,
                'date' => '20-12-2024',
                'start_time' => '14:00',
                'end_time' => '16:30',
                'cover_image' => 'course_covers-seed/technical_analysis.jpg',
            ],
            [
                'name_en' => 'Cryptocurrency Trading Course',
                'name_ar' => 'دورة تداول العملات الرقمية',
                'description_en' => 'Comprehensive guide to cryptocurrency trading, covering Bitcoin, Ethereum, altcoins, and blockchain technology.',
                'description_ar' => 'دليل شامل لتداول العملات الرقمية، covering البيتكوين، الإيثيريوم، العملات البديلة، وتكنولوجيا البلوكشين.',
                'price_aed' => 600,
                'price_usd' => 163,
                'date' => '25-12-2024',
                'start_time' => '09:00',
                'end_time' => '11:00',
                'cover_image' => 'course_covers-seed/crypto_trading.jpg',
            ],
            [
                'name_en' => 'Risk Management Masterclass',
                'name_ar' => 'دورة متقدمة في إدارة المخاطر',
                'description_en' => 'Learn professional risk management techniques to protect your capital and maximize profits.',
                'description_ar' => 'تعلم تقنيات إدارة المخاطر الاحترافية لحماية رأس مالك وتعظيم الأرباح.',
                'price_aed' => 400,
                'price_usd' => 109,
                'date' => '05-01-2025',
                'start_time' => '11:00',
                'end_time' => '13:00',
                'cover_image' => 'course_covers-seed/risk_management.jpg',
            ],
            [
                'name_en' => 'Stock Market Investing',
                'name_ar' => 'الاستثمار في سوق الأسهم',
                'description_en' => 'Complete guide to stock market investing, portfolio management, and long-term wealth building.',
                'description_ar' => 'دليل كامل للاستثمار في سوق الأسهم، إدارة المحافظ، وبناء الثروة على المدى الطويل.',
                'price_aed' => 550,
                'price_usd' => 150,
                'date' => '10-01-2025',
                'start_time' => '15:00',
                'end_time' => '17:00',
                'cover_image' => 'course_covers-seed/stock_investing.jpg',
            ],
            [
                'name_en' => 'Trading Psychology & Discipline',
                'name_ar' => 'سيكولوجية التداول والانضباط',
                'description_en' => 'Develop the mental toughness and discipline required for successful trading in volatile markets.',
                'description_ar' => 'طور الصلابة العقلية والانضباط المطلوبين للتداول الناجح في الأسواق المتقلبة.',
                'price_aed' => 350,
                'price_usd' => 95,
                'date' => '15-01-2025',
                'start_time' => '13:00',
                'end_time' => '15:00',
                'cover_image' => 'course_covers-seed/trading_psychology.jpg',
            ],
        ];

        foreach ($coursesData as $courseData) {
            // إنشاء الموعد فقط ببيانات التاريخ والوقت
            $appointment = Appointment::create([
                'date' => Carbon::createFromFormat('d-m-Y', $courseData['date'])->format('Y-m-d'),
                'start_time' => $courseData['start_time'],
                'end_time' => $courseData['end_time'],
                // إذا كان هناك حقل للنوع أو أي حقول أخرى مطلوبة في الموعد، أضفها هنا
            ]);

            // إنشاء الكورس مرتبط بالموعد مع بقية البيانات
            $course = $appointment->courseOnline()->create([
                'name_en' => $courseData['name_en'],
                'name_ar' => $courseData['name_ar'],
                'description_en' => $courseData['description_en'],
                'description_ar' => $courseData['description_ar'],
                'price_aed' => $courseData['price_aed'],
                'price_usd' => $courseData['price_usd'],
                'cover_image' => $courseData['cover_image'],
            ]);
        }
    }
}