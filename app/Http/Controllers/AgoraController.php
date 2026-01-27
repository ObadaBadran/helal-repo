<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AgoraService;
use Illuminate\Support\Facades\Cache;

class AgoraController extends Controller
{
    protected AgoraService $agora;

    public function __construct(AgoraService $agora)
    {
        $this->agora = $agora;
    }


    public function showBroadcast($channelName)
{
    // 1. توليد UID عشوائي
    $uid = rand(10000, 99999);

    $adminKey = "channel-admin-" . $channelName;
    $participantsKey = "channel-participants-" . $channelName;

    // 2. إدارة منطق الآدمن
    if (!Cache::has($adminKey)) {
        Cache::put($adminKey, $uid, now()->addHours(2));
        $isAdmin = true;
        $adminUid = $uid;
    } else {
        $isAdmin = false;
        $adminUid = Cache::get($adminKey);
    }

    // 3. تحديث قائمة المشاركين في الكاش
    $participants = Cache::get($participantsKey, []);
    $participants[$uid] = [
        'uid' => $uid,
        'name' => $isAdmin ? 'Teacher' : 'Student ' . $uid,
        'isAdmin' => $isAdmin,
        'raisedHand' => false,
        'isMuted' => false,
        'videoEnabled' => true,
        'joined_at' => now()->toDateTimeString()
    ];
    
    // حفظ في الكاش مع التأكد من وجود البيانات
    Cache::put($participantsKey, $participants, now()->addHours(2));

    // 4. توليد التوكن
    try {
        $token = $this->agora->generateToken($channelName, $uid);
    } catch (\Throwable $e) {
        abort(500, "Token Error: " . $e->getMessage());
    }

    return view('live.broadcast', [
        'appId'       => config('services.agora.app_id'),
        'token'       => $token,
        'channelName' => $channelName,
        'uid'         => $uid,
        'isAdmin'     => $isAdmin,
        'adminUid'    => $adminUid,
        'participants' => $participants
    ]);
}
    /**
     * عرض البث المباشر للقناة    // إذا لم يوجد آدمن بعد، يصبح هذا المستخدم أول آدمن
     */
    public function getJoinData(Request $request, $channelName)
{
    // 1. الحصول على المستخدم من التوكن
    $user = auth('api')->user();

    // 2. التحقق الصارم من الهوية
    if (!$user) {
        return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    }

   // $uid = $user->id;
 //  $uid = crc32($user->id . microtime());

    $uid = random_int(1, 2000000000);

    // 3. تحديد هل هو أدمن بناءً على قاعدة البيانات (وليس أسبقية الدخول)
    // نفترض أن عندك حقل في جدول المستخدمين اسمه role
    $isAdmin = ($user->role === 'admin'); 

    $adminKey = "channel-admin-" . $channelName;
    $participantsKey = "channel-participants-" . $channelName;

    // 4. إدارة مفتاح الأدمن في الكاش
    // إذا كان المستخدم الحالي أدمن، نقوم بتحديث الكاش للتأكيد أن الأدمن متواجد
    if ($isAdmin) {
        Cache::put($adminKey, $uid, now()->addHours(2));
    }

    // 5. تحديث قائمة المشاركين
    $participants = Cache::get($participantsKey, []);
    $participants[$uid] = [
        'uid' => $uid,
        'name' => $user->name,
        'isAdmin' => $isAdmin,
        'role' => $user->role, // إضافة الدور لسهولة التعامل في فرونت إند
        'raisedHand' => false,
        'isMuted' => false,
        'videoEnabled' => true,
        'joinedAt' => now()->toDateTimeString()
    ];
    Cache::put($participantsKey, $participants, now()->addHours(2));

    try {
        /**
         * 6. توليد التوكن مع الصلاحيات (Privileges)
         * الأفضل تعديل دالة generateToken لتقبل Role:
         * الأدمن (Publisher) = يستطيع فتح الكاميرا والمايك
         * الطالب (Subscriber) = يشاهد ويسمع فقط حتى يأذن له الأدمن
         */
        $role = $isAdmin ? 1 : 2; // 1: Host/Publisher, 2: Subscriber
        
        // تأكد من تحديث دالة generateToken في AgoraService لتقبل هذا المتغير
       // $token = $this->agora->generateToken($channelName, $uid, $role);
$token = $this->agora->generateToken($channelName, $uid, 3600);

        return response()->json([
            'status' => true,
            'appId' => config('services.agora.app_id'),
            'token' => $token,
            'channelName' => $channelName,
            'uid' => $uid,
            'isAdmin' => $isAdmin, // سيعرف React الآن يقيناً هل يظهر أدوات التحكم أم لا
            'participants' => $participants,
            'serverTime' => now()->toDateTimeString()
        ]);

    } catch (\Throwable $e) {
        // تسجيل الخطأ للمطور ولكن إرجاع رسالة بسيطة للمستخدم
        \Log::error("Agora Token Error: " . $e->getMessage());
        return response()->json(['status' => false, 'message' => 'Failed to join the session'], 500);
    }
}
    /**
     * API لرفع اليد
     */
    public function raiseHand(Request $request)
    {
        $uid = $request->uid;
        $channelName = $request->channel;
        $action = $request->action; // 'raise' or 'lower'
        
        $participantsKey = "channel-participants-" . $channelName;
        $participants = Cache::get($participantsKey, []);
        
        if (isset($participants[$uid])) {
            $participants[$uid]['raisedHand'] = ($action === 'raise');
            Cache::put($participantsKey, $participants, now()->addHours(2));
            
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false]);
    }

    /**
     * API لعمل Mute للكل أو لمستخدم محدد
     */
    public function muteUser(Request $request)
    {
        $channelName = $request->channel;
        $targetUid = $request->targetUid;
        $muteAll = $request->boolean('muteAll', false);
        
        $participantsKey = "channel-participants-" . $channelName;
        $participants = Cache::get($participantsKey, []);
        
        if ($muteAll) {
            foreach ($participants as $uid => $data) {
                if (!$data['isAdmin']) {
                    $participants[$uid]['isMuted'] = true;
                }
            }
        } elseif ($targetUid && isset($participants[$targetUid])) {
            $participants[$targetUid]['isMuted'] = true;
        }
        
        Cache::put($participantsKey, $participants, now()->addHours(2));
        
        return response()->json(['success' => true]);
    }

    /**
     * API لإيقاف كاميرا مستخدم محدد أو للكل
     */
    public function disableVideo(Request $request)
    {
        $channelName = $request->channel;
        $targetUid = $request->targetUid;
        $disableAll = $request->boolean('disableAll', false);
        
        $participantsKey = "channel-participants-" . $channelName;
        $participants = Cache::get($participantsKey, []);
        
        if ($disableAll) {
            foreach ($participants as $uid => $data) {
                if (!$data['isAdmin']) {
                    $participants[$uid]['videoEnabled'] = false;
                }
            }
        } elseif ($targetUid && isset($participants[$targetUid])) {
            $participants[$targetUid]['videoEnabled'] = false;
        }
        
        Cache::put($participantsKey, $participants, now()->addHours(2));
        
        return response()->json(['success' => true]);
    }

    /**
     * API لطرد مستخدم
     */
    public function kickUser(Request $request)
    {
        $channelName = $request->channel;
        $targetUid = $request->targetUid;
        
        $participantsKey = "channel-participants-" . $channelName;
        $participants = Cache::get($participantsKey, []);
        
        if (isset($participants[$targetUid])) {
            unset($participants[$targetUid]);
            Cache::put($participantsKey, $participants, now()->addHours(2));
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * API للحصول على المشاركين (للأدمن فقط)
     */
    public function getParticipants($channelName)
    {
        $participantsKey = "channel-participants-" . $channelName;
        $participants = Cache::get($participantsKey, []);
        
        return response()->json($participants);
    }
}