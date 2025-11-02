@component('mail::message')
# {{ $isArabic ? 'تم إضافة كورس جديد' : 'New Course Available' }}

**{{ $isArabic ? 'العنوان بالعربية:' : 'Title (Arabic):' }}** {{ $course->title_ar }}  
**{{ $isArabic ? 'العنوان بالإنجليزية:' : 'Title (English):' }}** {{ $course->title_en }}

**{{ $isArabic ? 'الوصف بالعربية:' : 'Description (Arabic):' }}** {{ $course->description_ar }}  
**{{ $isArabic ? 'الوصف بالإنجليزية:' : 'Description (English):' }}** {{ $course->description_en }}

**{{ $isArabic ? 'السعر:' : 'Price:' }}** {{ $course->price_usd }} USD | {{ $course->price_aed }} AED

@component('mail::button', ['url' => $courseUrl])
{{ $isArabic ? 'اذهب إلى الكورس' : 'Go to Course' }}
@endcomponent

شكراً,<br>
{{ config('app.name') }}
@endcomponent
