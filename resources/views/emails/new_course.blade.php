<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كورس جديد / New Course</title>
    <style>
        body {
            font-family: 'Tahoma', Arial, sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
            color: #333;
        }
        .email-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 25px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h2 {
            color: #007bff;
        }
        p {
            line-height: 1.6;
        }
        .dual-text {
            direction: rtl;
            text-align: right;
        }
        .dual-text span.en {
            display: block;
            direction: ltr;
            text-align: left;
            color: #555;
        }
    </style>
</head>
<body>
<div class="email-container">
    <h2>تم إضافة كورس جديد / <span style="color:#007bff;">A New Course Has Been Added</span></h2>

    <p class="dual-text">
        <strong>العنوان (Title):</strong>
        {{ $course->title_ar }}
        <span class="en">{{ $course->title_en }}</span>
    </p>

    <p class="dual-text">
        <strong>الوصف (Description):</strong>
        {{ $course->description_ar }}
        <span class="en">{{ $course->description_en }}</span>
    </p>

    <p class="dual-text">
        <strong>السعر (Price):</strong>
        {{ $course->price_usd }} USD | {{ $course->price_aed }} AED
    </p>

    <p class="dual-text">
        سارع بالتسجيل الآن!
        <span class="en">Enroll now!</span>
    </p>
</div>
</body>
</html>
