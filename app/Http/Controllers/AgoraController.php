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

    $isAdmin = ($user->role === 'admin'); 
    $participantsKey = "channel-participants-" . $channelName;

    // جلب المشاركين الحاليين من الكاش
    $participants = Cache::get($participantsKey, []);

    $user_id = $user->id;

   if (isset($participants[$user_id]['uid'])) {
        $agoraUid = $participants[$user_id]['uid'];
    } else {
        $agoraUid = rand(10000, 99999); 
    }

    //  تنظيف المشاركين القدماء (لم ينضموا منذ أكثر من 24 ساعة)
    foreach ($participants as $id => $p) {
        if (strtotime($p['joinedAt']) < now()->subHours(24)->timestamp) {
            unset($participants[$id]);
        }
    }

    //  التحقق من الطرد باستخدام user_id الثابت
    if (isset($participants[$user_id]) && ($participants[$user_id]['kicked'] ?? false)) {
        return response()->json(['status' => false, 'message' => 'You are kicked from this session'], 403);
    }

    //  تحديث بيانات المستخدم في الكاش
    $participants[$user_id] = [
        'uid' => $agoraUid, // UID جديد لكل دخول
        'name' => $user->name,
        'isAdmin' => $isAdmin,
        'isMuted' => $participants[$user_id]['isMuted'] ?? false,
        'videoEnabled' => $participants[$user_id]['videoEnabled'] ?? true,
        'kicked' => false,
        'joinedAt' => now()->toDateTimeString()
    ];

    //  تخزين الكاش لمدة 24 ساعة
    Cache::put($participantsKey, $participants, now()->addHours(24));

    // توليد توكن أغورا
    try {
        $token = $this->agora->generateToken($channelName, $agoraUid, 3600);
    } catch (\Throwable $e) {
        return response()->json(['status' => false, 'message' => 'Token Error: ' . $e->getMessage()], 500);
    }

    //  إعادة البيانات للواجهة
    return response()->json([
        'status' => true,
        'appId' => config('services.agora.app_id'),
        'token' => $token,
        'channelName' => $channelName,
        'uid' => $agoraUid,
        'isAdmin' => $isAdmin,
        'participants' => $participants
    ]);
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

   public function muteUser(Request $request)
{
    $user = auth('api')->user();
    if (!$user || $user->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $channelName = $request->channel;
    $targetUserId = $request->targetUserId; // غيرنا التسمية لـ UserId لضمان الوضوح
    $muteAll = $request->boolean('muteAll', false);
    $action = $request->get('action', 'mute'); // إضافة خيار لإلغاء المكتوم (unmute)

    $participantsKey = "channel-participants-" . $channelName;
    $participants = Cache::get($participantsKey, []);

    if ($muteAll) {
        foreach ($participants as $id => $data) {
            if (isset($data['isAdmin']) && !$data['isAdmin']) {
                $participants[$id]['isMuted'] = ($action === 'mute');
            }
        }
    } elseif ($targetUserId && isset($participants[$targetUserId])) {
        $participants[$targetUserId]['isMuted'] = ($action === 'mute');
    }

    Cache::put($participantsKey, $participants, now()->addHours(24));
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
    // 1. التحقق من صلاحية الأدمن
    $admin = auth('api')->user();
    if (!$admin || $admin->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    // 2. التحقق من البيانات المرسلة
    $request->validate([
        'channel' => 'required|string',
        'targetUserId' => 'required' // هنا نطلب الـ ID الحقيقي للمستخدم (الموجود في الكاش كمفتاح)
    ]);

    $channelName = $request->channel;
    $targetUserId = $request->targetUserId;
    
    $participantsKey = "channel-participants-" . $channelName;
    $participants = Cache::get($participantsKey, []);
    
    // 3. البحث عن المستخدم في الكاش باستخدام الـ Key (User ID)
    if (isset($participants[$targetUserId])) {
        // نضع علامة الطرد
        $participants[$targetUserId]['kicked'] = true;
        
        // تحديث الكاش (نحتفظ به لمدة 24 ساعة لضمان عدم تمكنه من الدخول مرة أخرى)
        Cache::put($participantsKey, $participants, now()->addHours(24));
        
        return response()->json([
            'success' => true, 
            'message' => 'User (ID: '.$targetUserId.') has been kicked successfully.'
        ]);
    }
    
    return response()->json(['success' => false, 'message' => 'User not found in this channel'], 404);
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