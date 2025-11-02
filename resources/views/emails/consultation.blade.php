@php
    $isArabic = ($locale ?? app()->getLocale()) === 'ar';
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طلب استشارة خاصة جديدة / New Private Consultation Request</title>
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
        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #999;
            text-align: center;
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

    <h2>تفاصيل الاستشارة المدفوعة / <span style="color:#007bff;">Paid Consultation Details</span></h2>

    <p class="dual-text">
        تم استلام طلب استشارة مدفوعة جديدة من:  
        <span class="en">A new paid consultation has been received from:</span>
    </p>

    <p class="dual-text"><strong>الاسم (Name):</strong> {{ $consultation->name }}</p>
    <p class="dual-text"><strong>البريد الإلكتروني (Email):</strong> {{ $consultation->email }}</p>
    <p class="dual-text"><strong>رقم الهاتف (Phone):</strong> {{ $consultation->phone }}</p>
    <p class="dual-text"><strong>المبلغ (Amount):</strong> {{ $consultation->amount }} {{ $consultation->currency }}</p>

    <div class="footer dual-text">
        شكراً لتعاملك معنا.  
        <span class="en">Thank you for choosing our service.</span>
    </div>

</div>
</body>
</html>
