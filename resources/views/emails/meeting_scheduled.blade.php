<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Meeting Details</title>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(to bottom right, #eef2f7, #ffffff);
            padding: 30px;
            color: #333;
        }

        .email-container {
            background: #fff;
            border-radius: 15px;
            padding: 35px;
            max-width: 650px;
            margin: auto;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid #e6e6e6;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header-title {
            font-size: 26px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }

        .sub-text {
            font-size: 14px;
            color: #555;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 10px;
        }

        .info-box {
            background: #f7f9fc;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }

        .info-row {
            margin: 8px 0;
            font-size: 15px;
        }

        .label {
            font-weight: bold;
            color: #007bff;
        }

        a.join-btn {
            display: inline-block;
            padding: 14px 22px;
            background: #007bff;
            color: white !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            margin-top: 20px;
            text-align: center;
        }

        a.join-btn:hover {
            background: #0056b3;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 13px;
            color: #888;
        }
    </style>
</head>

<body>

<div class="email-container">

    <div class="header">
        <div class="header-title">Your Meeting Details</div>
        <div class="sub-text">Please find your meeting information below</div>
    </div>

    <div class="section-title">Meeting Information</div>

    <div class="info-box">

        <div class="info-row">
            <span class="label">Date:</span>
            {{ \Carbon\Carbon::parse($meeting->start_time)->translatedFormat('l, d F Y') }}
        </div>

        <div class="info-row">
            <span class="label">Time:</span>
            {{ \Carbon\Carbon::parse($meeting->start_time)->format('H:i') }}
        </div>

        <div class="info-row">
            <span class="label">Topic:</span>
            {{ $meeting->summary }}
        </div>

        <div class="info-row">
            <span class="label">Duration:</span>
            {{ $meeting->duration }} minutes
        </div>

    </div>

    <div style="text-align: center; margin-top: 25px;">
       <a class="join-btn" href="{{ $url }}" target="_blank">Join Meeting</a>
    </div>

    <div class="footer">
        This is an automated message — please do not reply.<br>
        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </div>

</div>

</body>
</html>
