<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Online Course Ready</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f5f7fa; padding: 30px;">

<table
    style="max-width:600px; margin:auto; background:#ffffff; border-radius:10px; padding: 25px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">

    <tr>
        <td style="text-align: center;">
            <h2 style="color:#2d3748; margin-bottom: 10px;">🎉 Your Online Course is Ready!</h2>
            <p style="color:#555; font-size:15px; margin-top:0;">Hello {{ $user->name }}, your course details are
                below.</p>
        </td>
    </tr>

    <tr>
        <td>
            <div style="background:#f1f5f9; padding:20px; border-radius:8px; margin-top:20px;">

                <p><strong>📘 Course:</strong><br>{{ $course->name_en }}</p>

                <hr style="border:0; border-top:1px solid #ddd;">

                <p><strong>📅
                        Date:</strong><br>{{ \Carbon\Carbon::parse($course->appointment->date)->translatedFormat('l, d F Y') }}
                </p>

                <hr style="border:0; border-top:1px solid #ddd;">

                <p><strong>⏰ Start Time:</strong><br>{{ $course->appointment->start_time }}</p>

                <hr style="border:0; border-top:1px solid #ddd;">

                <p><strong>⏳ End Time:</strong><br>{{ $course->appointment->end_time }}</p>

                <hr style="border:0; border-top:1px solid #ddd;">

                <p>
                    <strong>💰 Price:</strong><br>
                    {{ $course->price_usd }} USD - {{ $course->price_aed }} AED
                </p>

            </div>
        </td>
    </tr>

    <tr>
        <td style="text-align:center; padding-top:25px;">
            <a href="{{ $joinUrl }}"
               style="background:#38a169; color:#fff; padding:12px 25px; text-decoration:none;
                          border-radius:6px; font-size:16px; display:inline-block;">
                🚀 Join Session
            </a>
        </td>
    </tr>

    <tr>
        <td style="text-align:center; padding-top:20px; color:#666;">
            Thank you for using our platform!
        </td>
    </tr>

</table>

</body>
</html>
