<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Video;

class VideoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $videos = [
            [
                'course_id' => 1,
                'path' => null,
                'youtube_path' => 'https://www.youtube.com/watch?v=wIyZcY6kNuo',
                'title_en' => 'Forex Trading for Beginners',
                'title_ar' => 'تداول الفوركس للمبتدئين',
                'subTitle_en' => 'Complete Forex Trading Course',
                'subTitle_ar' => 'دورة كاملة في تداول الفوركس',
                'description_en' => 'Learn the basics of forex trading, currency pairs, and how to start trading in the foreign exchange market.',
                'description_ar' => 'تعلم أساسيات تداول الفوركس، أزواج العملات، وكيفية البدء في التداول في سوق الصرف الأجنبي.',
                'cover' => 'covers-seed/forex_basics.jpg',
            ],
            [
                'course_id' => 1,
                'path' => null,
                'youtube_path' => 'https://www.youtube.com/watch?v=hAfZhYPn9I4',
                'title_en' => 'Technical Analysis Fundamentals',
                'title_ar' => 'أساسيات التحليل الفني',
                'subTitle_en' => 'Charts, Patterns & Indicators',
                'subTitle_ar' => 'الرسوم البيانية، الأنماط والمؤشرات',
                'description_en' => 'Master technical analysis techniques including chart patterns, support/resistance, and popular indicators.',
                'description_ar' => 'إتقان تقنيات التحليل الفني including أنماط الرسوم البيانية، الدعم/المقاومة، والمؤشرات الشائعة.',
                'cover' => 'covers-seed/technical_analysis.jpg',
            ],
            [
                'course_id' => 2,
                'path' => null,
                'youtube_path' => 'https://www.youtube.com/watch?v=hAfZhYPn9I4',
                'title_en' => 'Cryptocurrency Trading Strategies',
                'title_ar' => 'استراتيجيات تداول العملات الرقمية',
                'subTitle_en' => 'Bitcoin, Ethereum & Altcoins',
                'subTitle_ar' => 'البيتكوين، الإيثيريوم والعملات البديلة',
                'description_en' => 'Advanced trading strategies for cryptocurrency markets including swing trading and position trading.',
                'description_ar' => 'استراتيجيات تداول متقدمة لأسواق العملات الرقمية including التداول المتأرجح وتداول المراكز.',
                'cover' => 'covers-seed/crypto_trading.jpg',
            ],
            [
                'course_id' => 2,
                'path' => null,
                'youtube_path' => 'https://www.youtube.com/watch?v=hAfZhYPn9I4',
                'title_en' => 'Risk Management in Trading',
                'title_ar' => 'إدارة المخاطر في التداول',
                'subTitle_en' => 'Protect Your Capital',
                'subTitle_ar' => 'احمي رأس مالك',
                'description_en' => 'Learn essential risk management techniques including stop-loss, position sizing, and risk-reward ratios.',
                'description_ar' => 'تعلم تقنيات إدارة المخاطر الأساسية including وقف الخسارة، تحديد حجم المركز، ونسب المخاطرة إلى العائد.',
                'cover' => 'covers-seed/risk_management.jpg',
            ],
            [
                'course_id' => 3,
                'path' => null,
                'youtube_path' => 'https://www.youtube.com/watch?v=hAfZhYPn9I4',
                'title_en' => 'Stock Market Investing Basics',
                'title_ar' => 'أساسيات الاستثمار في سوق الأسهم',
                'subTitle_en' => 'Long-term Wealth Building',
                'subTitle_ar' => 'بناء الثروة على المدى الطويل',
                'description_en' => 'Fundamental analysis and long-term investment strategies for stock market success.',
                'description_ar' => 'التحليل الأساسي واستراتيجيات الاستثمار طويلة الأجل للنجاح في سوق الأسهم.',
                'cover' => 'covers-seed/stock_investing.jpg',
            ],
            [
                'course_id' => 4,
                'path' => null,
                'youtube_path' => 'https://www.youtube.com/watch?v=hAfZhYPn9I4',
                'title_en' => 'Trading Psychology Masterclass',
                'title_ar' => 'دورة متقدمة في سيكولوجية التداول',
                'subTitle_en' => 'Master Your Emotions',
                'subTitle_ar' => 'أسيطر على مشاعرك',
                'description_en' => 'Develop the mental discipline and emotional control required for consistent trading success.',
                'description_ar' => 'طور الانضباط العقلي والتحكم العاطفي المطلوبين للنجاح المستمر في التداول.',
                'cover' => 'covers-seed/trading_psychology.jpg',
            ],
            [
                'course_id' => 5,
                'path' => null,
                'youtube_path' => 'https://www.youtube.com/watch?v=hAfZhYPn9I4',
                'title_en' => 'Advanced Chart Patterns',
                'title_ar' => 'أنماط الرسوم البيانية المتقدمة',
                'subTitle_en' => 'Professional Trading Setups',
                'subTitle_ar' => 'إعدادات التداول الاحترافية',
                'description_en' => 'Learn advanced chart patterns and professional trading setups used by institutional traders.',
                'description_ar' => 'تعلم أنماط الرسوم البيانية المتقدمة وإعدادات التداول الاحترافية التي يستخدمها المتداولون المؤسسيون.',
                'cover' => 'covers-seed/chart_patterns.jpg',
            ],
        ];

       foreach ($videos as $video) {
            // حفظ المسار النسبي فقط في قاعدة البيانات
            Video::create($video);
        }
    }
}