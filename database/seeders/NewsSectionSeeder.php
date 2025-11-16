<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NewsSection;
use App\Models\NewsSectionImage;

class NewsSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sectionsData = [
            [
                'title_en' => 'Market Analysis: Forex Trends 2024',
                'title_ar' => 'تحليل السوق: اتجاهات الفوركس 2024',
                'subtitle_en' => 'Major Currency Pairs Forecast',
                'subtitle_ar' => 'توقعات أزواج العملات الرئيسية',
                'description_en' => 'Comprehensive analysis of EUR/USD, GBP/USD, and USD/JPY trends with technical and fundamental insights for professional traders.',
                'description_ar' => 'تحليل شامل لاتجاهات اليورو/الدولار، الجنيه الإسترليني/الدولار، والدولار/الين الياباني مع رؤى تقنية وأساسية للمتداولين المحترفين.',
                'images' => [
                    'seed/trading/forex_analysis.jpg',
                    'seed/trading/currency_trends.jpg'
                ]
            ],
            [
                'title_en' => 'Cryptocurrency Market Update',
                'title_ar' => 'تحديث سوق العملات الرقمية',
                'subtitle_en' => 'Bitcoin & Altcoins Performance',
                'subtitle_ar' => 'أداء البيتكوين والعملات البديلة',
                'description_en' => 'Latest crypto market movements, regulatory developments, and technical analysis for major cryptocurrencies including Bitcoin, Ethereum, and emerging altcoins.',
                'description_ar' => 'أحدث تحركات سوق العملات الرقمية، التطورات التنظيمية، والتحليل الفني للعملات الرقمية الرئيسية including البيتكوين، الإيثيريوم، والعملات البديلة الناشئة.',
                'images' => [
                    'seed/trading/crypto_update.jpg',
                    'seed/trading/bitcoin_analysis.jpg',
                    'seed/trading/altcoins.jpg'
                ]
            ],
            [
                'title_en' => 'Stock Market Investment Strategies',
                'title_ar' => 'استراتيجيات الاستثمار في سوق الأسهم',
                'subtitle_en' => 'Building Profitable Portfolios',
                'subtitle_ar' => 'بناء محافظ استثمارية مربحة',
                'description_en' => 'Expert guidance on stock selection, portfolio diversification, risk management, and long-term wealth building strategies in volatile markets.',
                'description_ar' => 'توجيهات الخبراء حول اختيار الأسهم، تنويع المحافظ، إدارة المخاطر، واستراتيجيات بناء الثروة طويلة الأجل في الأسواق المتقلبة.',
                'images' => [
                    'seed/trading/stock_strategies.jpg',
                ]
            ],
            [
                'title_en' => 'Technical Analysis Masterclass',
                'title_ar' => 'دورة متقدمة في التحليل الفني',
                'subtitle_en' => 'Advanced Chart Patterns & Indicators',
                'subtitle_ar' => 'أنماط الرسوم البيانية والمؤشرات المتقدمة',
                'description_en' => 'Deep dive into advanced technical analysis techniques including Elliott Wave, Fibonacci retracements, and multiple timeframe analysis for precise entry and exit points.',
                'description_ar' => 'غوص عميق في تقنيات التحليل الفني المتقدمة including موجات إليوت، مستويات فيبوناتشي، وتحليل الإطارات الزمنية المتعددة لنقاط الدخول والخروج الدقيقة.',
                'images' => [
                    'seed/trading/technical_analysis.jpg',
                    'seed/trading/chart_patterns.jpg'
                ]
            ],
           
            [
                'title_en' => 'Risk Management in Trading',
                'title_ar' => 'إدارة المخاطر في التداول',
                'subtitle_en' => 'Protect Your Capital',
                'subtitle_ar' => 'احمي رأس مالك',
                'description_en' => 'Essential risk management techniques including position sizing, stop-loss strategies, leverage control, and psychological aspects of successful trading.',
                'description_ar' => 'تقنيات أساسية لإدارة المخاطر including تحديد حجم المركز، استراتيجيات وقف الخسارة، التحكم في الرافعة المالية، والجوانب النفسية للتداول الناجح.',
                'images' => [
                    'seed/trading/risk_management.jpg'
                ]
            ],
           
            [
                'title_en' => 'Trading Psychology & Discipline',
                'title_ar' => 'سيكولوجية التداول والانضباط',
                'subtitle_en' => 'Master Your Emotions',
                'subtitle_ar' => 'أسيطر على مشاعرك',
                'description_en' => 'Developing the right mindset for trading success, overcoming fear and greed, maintaining discipline, and building consistent trading habits.',
                'description_ar' => 'تطوير العقلية الصحيحة لنجاح التداول، التغلب على الخوف والجشع، الحفاظ على الانضباط، وبناء عادات تداول متسقة.',
                'images' => [
                    'seed/trading/trading_psychology.jpg',
                    'seed/trading/discipline.jpg'
                ]
            ],
        ];

       foreach ($sectionsData as $sec) {
            $section = NewsSection::create([
                'title_en' => $sec['title_en'],
                'title_ar' => $sec['title_ar'],
                'subtitle_en' => $sec['subtitle_en'],
                'subtitle_ar' => $sec['subtitle_ar'],
                'description_en' => $sec['description_en'],
                'description_ar' => $sec['description_ar'],
            ]);

            foreach ($sec['images'] as $img) {
                NewsSectionImage::create([
                    'news_section_id' => $section->id,
                    'image' => $img, // حفظ المسار فقط بدون asset()
                ]);
            }
        }
    }
}