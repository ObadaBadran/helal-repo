@component('mail::message')
    <div style="text-align: center;">
        <img src="{{ asset('images/logo.png') }}" alt="Website Logo" style="width:120px; margin-bottom:20px;">
    </div>

    # 🎉 New Course Added: {{ $course->title_en }}

    Hello {{ $user->name ?? 'Valued Student' }},

    We’re excited to announce a brand new course: **{{ $course->title_en }}**!
    Here’s what you can expect:
    - 💡 Engaging lessons
    - 🎥 HD videos

    Thanks for being part of our learning community!
    **The {{ config('app.name') }} Team**
@endcomponent
