<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل الاستشارة الخاصة / Private Consultation Details</title>
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
    <h2>تفاصيل استشارتك / <span style="color:#007bff;">Your Consultation Details</span></h2>

    <p class="dual-text">
        تم تحديد موعد الاستشارة الخاصة بك:
        <span class="en">Your private consultation is scheduled at:</span>
    </p>

    <p class="dual-text">
        <strong>التاريخ (Date):</strong> {{ $consultation->consultation_date }}
    </p>

    <p class="dual-text">
        <strong>الوقت (Time):</strong> {{ $consultation->consultation_time }}
    </p>

    <p class="dual-text">
        <strong>رابط الاجتماع (Meeting URL):</strong>
        <a href="{{ $consultation->meet_url }}">{{ $consultation->meet_url }}</a>
    </p>
</div>
</body>
</html>
