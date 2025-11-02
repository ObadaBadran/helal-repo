@php
    $isArabic =  $isArabic = ($locale ?? app()->getLocale()) === 'ar';
@endphp

<!DOCTYPE html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $isArabic ? 'طلب استشارة خاصة جديدة' : 'New Private Consultation Request' }}</title>
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
    </style>
</head>
<body>
<div class="email-container">
    <h2>{{ $isArabic ? 'تفاصيل الاستشارة المدفوعة' : 'Paid Consultation Details' }}</h2>

    <p>
        {{ $isArabic ? 'تم استلام طلب استشارة مدفوعة جديدة من:' : 'A new paid consultation has been received from:' }}
    </p>

    <p><strong>{{ $isArabic ? 'الاسم:' : 'Name:' }}</strong> {{ $consultation->name }}</p>
    <p><strong>{{ $isArabic ? 'البريد الإلكتروني:' : 'Email:' }}</strong> {{ $consultation->email }}</p>
    <p><strong>{{ $isArabic ? 'رقم الهاتف:' : 'Phone:' }}</strong> {{ $consultation->phone }}</p>
    <p><strong>{{ $isArabic ? 'المبلغ:' : 'Amount:' }}</strong> {{ $consultation->amount }} {{ $consultation->currency }}</p>

    <div class="footer">
        {{ $isArabic ? 'شكراً لتعاملك معنا.' : 'Thank you for choosing our service.' }}
    </div>
</div>
</body>
</html>
