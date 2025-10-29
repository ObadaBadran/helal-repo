<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $data['subject'] ?? 'Contact Message' }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background-color: #f5f5f5; padding: 20px; text-align: center; }
        .header img { max-height: 80px; }
        .content { margin: 20px; }
        .content p { margin: 5px 0; }
        .footer { margin: 20px; font-size: 12px; color: #888; text-align: center; }
        hr { margin: 15px 0; }
    </style>
</head>
<body>

<div class="content">
    <h2> New Contact Message</h2>

    <p><strong>Name:</strong> {{ $data['full_name'] }}</p>
    <p><strong>Email:</strong> {{ $data['email'] }}</p>
    <p><strong>Subject:</strong> {{ $data['subject'] }}</p>

    <hr>

    <p><strong>Message:</strong></p>
    <p>{{ $data['message'] }}</p>
</div>

<div class="footer">
    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
</div>
</body>
</html>
