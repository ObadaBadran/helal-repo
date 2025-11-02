@component('mail::message')
# قناة فيديو جديدة / New Video Channel

تم إنشاء قناة فيديو جديدة بواسطة المشرف.  
*A new video channel has been created by the administrator.*

---

**معرّف الغرفة (Room ID):** {{ $channel['room_id'] }}

@component('mail::button', ['url' => env('FRONTEND_URL') . '/join-video/' . $channel['room_id']])
انضم إلى القناة الآن / Join the Channel Now
@endcomponent

---

شكراً,<br>
**{{ config('app.name') }}**  
*Thank you,*  
**{{ config('app.name') }} Team**
@endcomponent
