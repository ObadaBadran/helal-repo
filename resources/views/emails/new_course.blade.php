<!DOCTYPE html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $isArabic ? 'كورس جديد' : 'New Course' }}</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f5f7fa; padding: 30px;">

<table
    style="max-width:600px; margin:auto; background:#ffffff; border-radius:10px; padding: 25px; box-shadow:0 2px 10px rgba(0,0,0,0.08);">
    <tr>
        <td style="text-align: center;">
            <h1 style="color:#2d3748; margin-bottom: 5px;">
                {{ $isArabic ? '🎉 تم إطلاق كورس جديد!' : '🎉 A New Course Has Been Launched!' }}
            </h1>
            <p style="color:#555; font-size:15px; margin-top:0;">
                {{ $isArabic ? 'يسعدنا إعلامك بإضافة كورس جديد إلى منصتنا.' : 'We are excited to inform you that a new course has been added.' }}
            </p>
        </td>
    </tr>

    <tr>
        <td>
            <div style="background:#f1f5f9; padding:20px; border-radius:8px; margin-top:20px;">
                <h3 style="margin-top:0; color:#333;">
                    {{ $isArabic ? '📘 تفاصيل الكورس' : '📘 Course Details' }}
                </h3>

                <p>
                    <strong>{{ $isArabic ? 'العنوان:' : 'Title:' }}</strong><br>
                    {{ $isArabic ? $course->title_ar : $course->title_en }}
                </p>

                <hr style="border:0; border-top:1px solid #ddd;">

                <p>
                    <strong>{{ $isArabic ? 'الوصف:' : 'Description:' }}</strong><br>
                    {{ $isArabic ? $course->description_ar : $course->description_en }}
                </p>

                <hr style="border:0; border-top:1px solid #ddd;">

                <p>
                    <strong>{{ $isArabic ? 'السعر:' : 'Price:' }}</strong><br>
                    💲 {{ $course->price_usd }} USD - {{ $course->price_aed }} AED
                </p>
            </div>
        </td>
    </tr>

    <tr>
        @if($courseUrl)
            <td style="text-align:center; padding-top:25px;">
                <a href="{{ $courseUrl }}"
                   style="background:#38a169; color:#fff; padding:12px 25px; text-decoration:none;
                          border-radius:6px; font-size:16px; display:inline-block;">
                    {{ $isArabic ? '🚀 اذهب إلى الكورس' : '🚀 Go to Course' }}
                </a>
            </td>
        @endif
    </tr>

    <tr>
        <td style="text-align:center; padding-top:20px; color:#666;">
            {{ $isArabic ? 'شكراً لاختيارك منصتنا التعليمية!' : 'Thank you for choosing our learning platform!' }}
        </td>
    </tr>
</table>

</body>
</html>
