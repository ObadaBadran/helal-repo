@php
    use Carbon\Carbon;
    $isArabic = ($locale ?? app()->getLocale()) === 'ar';
@endphp

    <!DOCTYPE html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>تذكير بموعد استشارة قريب / Upcoming Consultation Reminder</title>
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
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #ff9800;
        }
        .reminder-header {
            background: linear-gradient(135deg, #ff9800, #ff5722);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
        }
        h2 {
            color: white;
            font-size: 22px;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .reminder-time {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
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
        .label { font-weight: bold; color: #333; }
        .value { color: #007bff; }
        .user-info, .consultation-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-right: 3px solid #007bff;
        }
        .consultation-details.orange {
            background-color: #fff3e0;
            border-right-color: #ff9800;
        }
        .urgent-badge {
            background-color: #ff5722;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
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
        .action-buttons {
            text-align: center;
            margin: 25px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        @media screen and (max-width: 640px) {
            .email-container { padding: 20px; }
            h2 { font-size: 20px; }
            p, .dual-text span.en { font-size: 14px; }
            .btn {
                display: block;
                margin: 10px auto;
                width: 80%;
            }
        }
    </style>
</head>
<body>
<div class="email-container">

    @php
        $appointment = $consultation->appointment ?? null;
        $info = $consultation->information ?? null;
        $startDateTime = $appointment
            ? Carbon::parse($appointment->date)->setTimeFromTimeString($appointment->start_time)
            : null;
    @endphp

    <div class="reminder-header">
        <h2>تذكير بموعد استشارة قريب / <span style="color:#fff;">Upcoming Consultation Reminder</span></h2>
        @if($startDateTime)
            <div class="reminder-time">1 hours ago</div>
        @endif
    </div>

    @if(!empty($isUrgent))
        <div class="urgent-badge">عاجل / URGENT</div>
    @endif

    <p class="dual-text">
        <span class="ar">هذا تذكير بموعد استشارة مدفوعة قريبة:</span>
        <span class="en">This is a reminder for an upcoming paid consultation:</span>
    </p>

    <div class="consultation-details orange">
        <h3 style="color: #ff9800; margin-top: 0;">تفاصيل الاستشارة / Consultation Details</h3>
        <p class="dual-text">
            <span class="label">نوع الاستشارة (Type):</span>
            <span class="value">{{ $info->type_ar ?? 'N/A' }} / {{ $info->type_en ?? 'N/A' }}</span>
        </p>
        <p class="dual-text">
            <span class="label">المبلغ (Amount):</span>
            <span class="value">
                @if($consultation->currency === 'USD')
                    {{ $info->price_usd ?? 'N/A' }} USD
                @else
                    {{ $info->price_aed ?? 'N/A' }} AED
                @endif
            </span>
        </p>
        <p class="dual-text"><span class="label">المدة (Duration):</span> <span class="value">{{ $info->duration ?? 'N/A' }} دقائق / minutes</span></p>
        <p class="dual-text">
            <span class="label">حالة الدفع (Payment Status):</span>
            <span class="value" style="color: {{ $consultation->payment_status === 'paid' ? '#28a745' : '#dc3545' }};">
                {{ ucfirst($consultation->payment_status) }}
            </span>
        </p>
    </div>

    @if($appointment)
        <div class="consultation-details">
            <h3 style="color: #28a745; margin-top: 0;">تفاصيل الموعد / Appointment Details</h3>
            <p class="dual-text">
                <span class="label">التاريخ (Date):</span>
                <span class="value">{{ Carbon::parse($appointment->date)->translatedFormat('l, d F Y') }}</span>
            </p>
            <p class="dual-text"><span class="label">وقت البدء (Start Time):</span> <span class="value">{{ $appointment->start_time }}</span></p>
            <p class="dual-text"><span class="label">وقت الانتهاء (End Time):</span> <span class="value">{{ $appointment->end_time }}</span></p>
            <p class="dual-text">
                <span class="label">الوقت المتبقي (Time Left):</span>
                <span class="value" style="color: #ff5722; font-weight: bold;">
                    1 hours ago
                </span>
            </p>
        </div>
    @endif

    @if(!empty($joinUrl))
        <div class="action-buttons">
            <a href="{{ $joinUrl }}" class="btn btn-primary" target="_blank">
                انضم إلى الاجتماع / Join Meeting
            </a>
        </div>
    @endif

    <div class="footer dual-text">
        <span class="ar">يرجى التحضير للاستشارة والتأكد من جاهزية جميع المتطلبات.</span>
        <span class="en">Please prepare for the consultation and ensure all requirements are ready.</span>
    </div>

</div>
</body>
</html>
