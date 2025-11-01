<!DOCTYPE html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $isArabic ? 'كورس جديد' : 'New Course' }}</title>
</head>
<body>
    <h2>{{ $isArabic ? 'تم إضافة كورس جديد:' : 'A New Course Has Been Added:' }}</h2>
    <p><strong>{{ $isArabic ? 'العنوان:' : 'Title:' }}</strong> {{ $isArabic ? $course->title_ar : $course->title_en }}</p>
    <p><strong>{{ $isArabic ? 'الوصف:' : 'Description:' }}</strong> {{ $isArabic ? $course->description_ar : $course->description_en }}</p>
    <p><strong>{{ $isArabic ? 'السعر:' : 'Price:' }}</strong> {{ $course->price_usd }} USD | {{ $course->price_aed }} AED</p>
    <p>{{ $isArabic ? 'سارع بالتسجيل الآن!' : 'Enroll now!' }}</p>
</body>
</html>
