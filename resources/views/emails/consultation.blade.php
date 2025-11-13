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
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .email-container {
            background-color: #fff;
            border-radius: 12px;
            padding: 30px;
            max-width: 620px;
            margin: 30px auto;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        h2 {
            color: #007bff;
            font-size: 22px;
            margin-bottom: 20px;
            line-height: 1.4;
            text-align: center;
        }
        p {
            line-height: 1.7;
            font-size: 15px;
            margin: 10px 0;
        }
        .dual-text {
            direction: rtl;
            text-align: right;
            margin-bottom: 12px;
        }
        .dual-text span.en {
            display: block;
            direction: ltr;
            text-align: left;
            font-size: 14px;
            color: #555;
            margin-top: 2px;
        }
        .label {
            font-weight: bold;
            color: #333;
        }
        .value {
            color: #007bff;
        }
        .footer {
            margin-top: 35px;
            font-size: 13px;
            color: #999;
            text-align: center;
        }
        .footer span.en {
            display: block;
            font-size: 13px;
            color: #999;
            margin-top: 2px;
            text-align: center;
        }
        @media screen and (max-width: 640px) {
            .email-container {
                padding: 20px;
            }
            h2 {
                font-size: 20px;
            }
            p, .dual-text span.en {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<div class="email-container">

    <h2>تفاصيل الاستشارة المدفوعة / <span style="color:#007bff;">Paid Consultation Details</span></h2>

    <p class="dual-text">
        <span class="ar">تم استلام طلب استشارة مدفوعة جديدة من:</span>
        <span class="en">A new paid consultation has been received from:</span>
    </p>

    <p class="dual-text"><span class="label">الاسم (Name):</span> <span class="value">{{ $consultation->name }}</span></p>
    <p class="dual-text"><span class="label">البريد الإلكتروني (Email):</span> <span class="value">{{ $consultation->email }}</span></p>
    <p class="dual-text"><span class="label">رقم الهاتف (Phone):</span> <span class="value">{{ $consultation->phone }}</span></p>
    <p class="dual-text"><span class="label">المبلغ (Amount):</span> <span class="value">{{ $consultation->currency === 'USD' ? $consultation->information->price_usd . 'USD' : $consultation->information->price_aed . 'AED'}}</span></p>
    <p class="dual-text"><span class="label">التاريخ (Date):</span> <span class="value">{{ $consultation->appointment->date}}</span></p>
    <p class="dual-text"><span class="label">الوقت (Time):</span> <span class="value">{{ $consultation->appointment->start_time}}</span></p>
    <p class="dual-text"><span class="label">وقت الانتهاء (End Time):</span> <span class="value">{{ $consultation->appointment->end_time}}</span></p>
     
    
    <div class="footer dual-text">
        <span class="ar">شكراً لتعاملك معنا.</span>
        <span class="en">Thank you for choosing our service.</span>
    </div>

</div>
</body>
</html>