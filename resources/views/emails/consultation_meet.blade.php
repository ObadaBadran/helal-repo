@php
    $isArabic = $locale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $isArabic ? 'تفاصيل الاستشارة الخاصة' : 'Private Consultation Details' }}</title>
</head>
<body>
    <h2>{{ $isArabic ? 'تفاصيل استشارتك' : 'Your Consultation Details' }}</h2>
    <p>{{ $isArabic ? 'تم تحديد موعد الاستشارة الخاصة بك:' : 'Your private consultation is scheduled at:' }}</p>
    <p><strong>{{ $isArabic ? 'التاريخ:' : 'Date:' }}</strong> {{ $consultation->consultation_date }}</p>
    <p><strong>{{ $isArabic ? 'الوقت:' : 'Time:' }}</strong> {{ $consultation->consultation_time }}</p>
    <p><strong>{{ $isArabic ? 'رابط الاجتماع:' : 'Meeting URL:' }}</strong> <a href="{{ $consultation->meet_url }}">{{ $consultation->meet_url }}</a></p>
</body>
</html>
