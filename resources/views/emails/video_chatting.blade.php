@component('mail::message')
    # قناة فيديو جديدة

    تم إنشاء قناة فيديو جديدة بواسطة المشرف.

    **Room ID:** {{ $channel['room_id'] }}

    @component('mail::button', ['url' => env('FRONTEND_URL') . '/join-video/' . $channel['room_id']])
        انضم إلى القناة الآن
    @endcomponent

    شكراً,<br>
    {{ config('app.name') }}
@endcomponent
