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

public function getJoinData(Request $request, $channelName)
{
    $user = auth('api')->user();
    if (!$user) {
        return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    }

    // الحل: استخدم الـ ID الحقيقي للمستخدم ليكون هو الـ UID أو اشتق منه رقم ثابت
    // أغورا تقبل فقط أرقام، لذا نستخدم ID المستخدم مباشرة
    $uid = (int) ($user->id . rand(10, 99)); 

    $isAdmin = ($user->role === 'admin'); 
    $participantsKey = "channel-participants-" . $channelName;

    $participants = Cache::get($participantsKey, []);

    // إذا كان المستخدم مطروداً سابقاً، امنعه من الدخول
    if (isset($participants[$uid]) && isset($participants[$uid]['kicked']) && $participants[$uid]['kicked']) {
         return response()->json(['status' => false, 'message' => 'You are kicked from this session'], 403);
    }

    // تحديث أو إضافة المستخدم (سيقوم بعمل Override لنفس المفتاح، مما يمنع التكرار)
    $participants[$uid] = [
        'uid' => $uid,
        'name' => $user->name,
        'isAdmin' => $isAdmin,
        'isMuted' => $participants[$uid]['isMuted'] ?? false, // الحفاظ على حالة الكتم لو عمل Refresh
        'videoEnabled' => $participants[$uid]['videoEnabled'] ?? true,
        'kicked' => false,
        'joinedAt' => now()->toDateTimeString()
    ];

    Cache::put($participantsKey, $participants, now()->addHours(2));

    try {
        $token = $this->agora->generateToken($channelName, $uid, 3600);
        return response()->json([
            'status' => true,
            'appId' => config('services.agora.app_id'),
            'token' => $token,
            'channelName' => $channelName,
            'uid' => $uid,
            'isAdmin' => $isAdmin,
            'participants' => $participants
        ]);
    } catch (\Throwable $e) {
        return response()->json(['status' => false, 'message' => 'Token Error'], 500);
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
        $user = auth('api')->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $channelName = $request->channel;
        $targetUid = $request->targetUid;
        $muteAll = $request->boolean('muteAll', false);
        
        $participantsKey = "channel-participants-" . $channelName;
        $participants = Cache::get($participantsKey, []);
        
        if ($muteAll) {
            foreach ($participants as $uid => $data) {
                if (isset($data['isAdmin']) && !$data['isAdmin']) {
                    $participants[$uid]['isMuted'] = true;
                }
            }
        } elseif ($targetUid && isset($participants[$targetUid])) {
            $participants[$targetUid]['isMuted'] = true;
        }
        
        Cache::put($participantsKey, $participants, now()->addHours(2));
        return response()->json(['success' => true, 'participants' => $participants]);
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
        $user = auth('api')->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $channelName = $request->channel;
        $targetUid = $request->targetUid;
        
        $participantsKey = "channel-participants-" . $channelName;
        $participants = Cache::get($participantsKey, []);
        
        if (isset($participants[$targetUid])) {
            // ملاحظة هامة: لا نحذف المستخدم فوراً
            // نضع علامة "مطرود" لكي يراها تطبيق الطالب في الـ Polling القادم
            $participants[$targetUid]['kicked'] = true;
            Cache::put($participantsKey, $participants, now()->addHours(2));
            
            // اختيارياً: يمكنك استخدام وظيفة مجدولة لحذفه نهائياً من الكاش بعد دقيقة
            return response()->json(['success' => true, 'message' => 'User marked for kick']);
        }
        
        return response()->json(['success' => false, 'message' => 'User not found']);
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