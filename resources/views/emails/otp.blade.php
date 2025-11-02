<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>رمز التحقق / Verification Code</title>
    <style>
        body {
            font-family: 'Tahoma', Arial, sans-serif;
            background-color: #f9f9f9;
            padding: 30px;
            color: #333;
        }
        .email-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 25px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            line-height: 1.8;
        }
        h2 {
            color: #007bff;
            text-align: center;
            font-size: 28px;
            letter-spacing: 2px;
        }
        p {
            margin: 10px 0;
        }
        .en {
            display: block;
            direction: ltr;
            text-align: left;
            color: #555;
            font-style: italic;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>
<div class="email-container">
    <p>
        مرحبًا،
        <span class="en">Hello,</span>
    </p>

    <p>
        رمز التحقق الخاص بك لتغيير كلمة المرور هو:
        <span class="en">Your verification code to reset your password is:</span>
    </p>

    <h2>{{ $otp }}</h2>

    <p>
        هذا الرمز صالح لمدة 10 دقائق فقط.
        <span class="en">This code is valid for 10 minutes only.</span>
    </p>

    <p>
        إذا لم تطلب تغيير كلمة المرور، يمكنك تجاهل هذه الرسالة.
        <span class="en">If you did not request a password reset, you can safely ignore this email.</span>
    </p>

    <div class="footer">
        <p>
            مع تحيات،  
            <strong>فريق Helal</strong>
            <span class="en">Best regards,  
            <strong>Helal Team</strong></span>
        </p>
    </div>
</div>
</body>
</html>
