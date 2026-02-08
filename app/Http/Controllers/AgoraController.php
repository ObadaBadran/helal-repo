<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yasser\Agora\RtcTokenBuilder;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Services\FirebaseService;

class AgoraController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function generateToken(Request $request)
    {
        try {
            $request->validate([
                'channelName' => 'required|string',
            ]);

            $appID = config('services.agora.app_id');
            $appCertificate = config('services.agora.app_certificate');

            if (empty($appID) || empty($appCertificate)) {
                throw new Exception('Missing Agora credentials in server configuration.');
            }

            $channelName = $request->channelName;
            $user = auth('api')->user();

            $uid = $user->id;
            $cacheKey = "agora_session_{$channelName}_{$uid}";
            $cachedSession = Cache::get($cacheKey);

            if ($cachedSession) {
                // If it's an admin rejoining, they still might want the current participant list
                $responseData = [
                    'token'   => $cachedSession['token'],
                    'uid'     => $uid,
                    'appId'   => $appID
                ];

                return response()->json([
                    'status' => true,
                    'message' => 'Token retrieved successfully.',
                    'data' => $responseData
                ]);
            }

            $role = RtcTokenBuilder::RolePublisher;
            $expireTimeInSeconds = 86400; // 24 hours
            $currentTimestamp = now()->getTimestamp();
            $privilegeExpireTs = $currentTimestamp + $expireTimeInSeconds;

            $token = RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpireTs);

            // 1. Manage Active Participants List in Cache (Internal Use)
            $participantsKey = "agora_channel_participants_{$channelName}";
            $participants = Cache::get($participantsKey, []);
            if (!in_array($uid, $participants)) {
                $participants[] = $uid;
                $ttl = isset($participants['expires_at']) ? ($participants['expires_at'] - now()->timestamp) : $expireTimeInSeconds;
                if ($ttl > 0) Cache::put($participantsKey, $participants, $ttl);
            }

            // 2. Cache Individual Session Information (including the token)
            $sessionData = [
                'token'         => $token,
                'channelName'   => $channelName,
                'uid'           => $uid,
                'userName'      => $user->name,
                'isAdmin'       => $user->role === 'admin',
                'isMuted'       => false,
                'videoEnabled'  => true,
                'kicked'        => false,
                'joinedAt'      => now()->toDateTimeString(),
                'expires_at'    => $privilegeExpireTs,
            ];

            Cache::put($cacheKey, $sessionData, $expireTimeInSeconds);

            // 3. Handle Notifications (If it's a regular user joining)
            if ($user->role !== 'admin') {
                $admins = User::where('role', 'admin')->whereNotNull('fcm_token')->get();
                $tokens = $admins->pluck('fcm_token')->toArray();

                if (!empty($tokens)) {
                    $this->firebaseService->sendNotification(
                        $tokens,
                        'New Participant Joined',
                        "User {$user->name} has joined the channel: {$channelName}",
                        ['action' => 'new_participant_joined', 'channelName' => $channelName, 'userId' => (string)$uid]
                    );
                }
            }

            // 4. Return Data
            $responseData = [
                'token'      => $token,
                'uid'        => $uid,
                'appId'      => $appID
            ];

            return response()->json([
                'status' => true,
                'message' => 'Token generated successfully.',
                'data' => $responseData
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Agora Token Generation Failed',
                'error' => $e->getMessage()
            ], 500);
        }
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

    // الحفاظ على الـ UID القديم أو توليد واحد جديد
    if (isset($participants[$user_id]['uid'])) {
        $agoraUid = $participants[$user_id]['uid'];
    } else {
        $agoraUid = rand(10000, 99999); 
    }

    // تنظيف المشاركين القدماء
    foreach ($participants as $id => $p) {
        if (isset($p['joinedAt']) && strtotime($p['joinedAt']) < now()->subHours(24)->timestamp) {
            unset($participants[$id]);
        }
    }

    // التحقق من الطرد
    if (isset($participants[$user_id]) && ($participants[$user_id]['kicked'] ?? false)) {
        return response()->json(['status' => false, 'message' => 'You are kicked from this session'], 403);
    }

    // تحديث بيانات المستخدم في الكاش (نستخدم user_id كمفتاح ثابت للتخزين)
    $participants[$user_id] = [
        'id'           => $user_id, // أضفنا الـ ID هنا لكي لا يضيع بعد تحويل المصفوفة
        'uid'          => $agoraUid,
        'name'         => $user->name,
        'isAdmin'      => $isAdmin,
        'isMuted'      => $participants[$user_id]['isMuted'] ?? false,
        'videoEnabled' => $participants[$user_id]['videoEnabled'] ?? true,
        'kicked'       => false,
        'joinedAt'     => now()->toDateTimeString()
    ];

    // تخزين الكاش (نحتفظ بالمفاتيح الأصلية في الكاش ليسهل علينا البحث في mute و kick)
    Cache::put($participantsKey, $participants, now()->addHours(24));

    // توليد توكن أغورا
    try {
        $token = $this->agora->generateToken($channelName, $agoraUid, 3600);
    } catch (\Throwable $e) {
        return response()->json(['status' => false, 'message' => 'Token Error'], 500);
    }

   
    $participantsArray = array_values($participants);

    return response()->json([
        'status'       => true,
        'appId'        => config('services.agora.app_id'),
        'token'        => $token,
        'channelName'  => $channelName,
        'uid'          => $agoraUid,
        'isAdmin'      => $isAdmin,
        'participants' => $participantsArray 
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
        try {
            $request->validate([
                'channelName' => 'required|string',
                'uid' => 'required|integer',
            ]);

            $channelName = $request->channelName;
            $targetUid = $request->uid;

            $cacheKey = "agora_session_{$channelName}_{$targetUid}";
            $sessionData = Cache::get($cacheKey);

            if ($sessionData) {
                $sessionData['isMuted'] = !$sessionData['isMuted'];
                $ttl = isset($sessionData['expires_at']) ? ($sessionData['expires_at'] - now()->timestamp) : 86400;
                if ($ttl > 0) Cache::put($cacheKey, $sessionData, $ttl);

                // Notify the user
                $targetUser = User::find($targetUid);
                if ($targetUser && $targetUser->fcm_token) {
                    $action = $sessionData['isMuted'] ? 'mute_participant' : 'unmute_participant';
                    $title = $sessionData['isMuted'] ? 'You have been muted' : 'You have been unmuted';
                    $body = $sessionData['isMuted'] ? 'An admin has muted your microphone.' : 'An admin has unmuted your microphone.';

                    $this->firebaseService->sendNotification(
                        $targetUser->fcm_token,
                        $title,
                        $body,
                        ['action' => $action, 'channelName' => $channelName, 'userId' => (string)$targetUid]
                    );
                }

                $message = $sessionData['isMuted'] ? 'User muted successfully.' : 'User unmuted successfully.';
                return response()->json(['status' => true, 'message' => $message]);
            }

            return response()->json(['status' => false, 'message' => 'User session not found.'], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to update mute status.', 'error' => $e->getMessage()], 500);
        }
    }

    public function muteAll(Request $request)
    {
        try {
            $user = auth('api')->user();

            $request->validate([
                'channelName' => 'required|string',
                'shouldMute' => 'required|boolean',
            ]);

            $channelName = $request->channelName;
            $participantsKey = "agora_channel_participants_{$channelName}";
            $participants = Cache::get($participantsKey, []);
            $count = 0;
            $tokensToNotify = [];

            foreach ($participants as $uid) {
                if ($uid == $user->id) continue; // Don't mute/unmute the admin

                $cacheKey = "agora_session_{$channelName}_{$uid}";
                $sessionData = Cache::get($cacheKey);

                if ($sessionData) {
                    $sessionData['isMuted'] = $request->shouldMute;
                    $ttl = isset($sessionData['expires_at']) ? ($sessionData['expires_at'] - now()->timestamp) : 86400;
                    if ($ttl > 0) Cache::put($cacheKey, $sessionData, $ttl);
                    $count++;

                    $pUser = User::find($uid);
                    if ($pUser && $pUser->fcm_token) {
                        $tokensToNotify[] = $pUser->fcm_token;
                    }
                }
            }

            if (!empty($tokensToNotify)) {
                $action = $request->shouldMute ? 'mute_all' : 'unmute_all';
                $title = $request->shouldMute ? 'Channel Muted' : 'Channel Unmuted';
                $body = $request->shouldMute ? 'The admin has muted everyone in the channel.' : 'The admin has unmuted everyone in the channel.';

                $this->firebaseService->sendNotification(
                    $tokensToNotify,
                    $title,
                    $body,
                    ['action' => $action, 'channelName' => $channelName]
                );
            }

            $actionText = $request->shouldMute ? 'Muted' : 'Unmuted';
            return response()->json(['status' => true, 'message' => "$actionText $count users successfully."]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to update mute status for all.', 'error' => $e->getMessage()], 500);
        }
    }

    public function kickUser(Request $request)
    {
        try {
            $request->validate([
                'channelName' => 'required|string',
                'uid' => 'required|integer',
            ]);

            $channelName = $request->channelName;
            $targetUid = $request->uid;

            $cacheKey = "agora_session_{$channelName}_{$targetUid}";
            $sessionData = Cache::get($cacheKey);

            if ($sessionData) {
                $sessionData['kicked'] = true;
                $ttl = isset($sessionData['expires_at']) ? ($sessionData['expires_at'] - now()->timestamp) : 86400;
                if ($ttl > 0) Cache::put($cacheKey, $sessionData, $ttl);

                // Notify the user
                $targetUser = User::find($targetUid);
                if ($targetUser && $targetUser->fcm_token) {
                    $this->firebaseService->sendNotification(
                        $targetUser->fcm_token,
                        'You have been kicked',
                        'An admin has removed you from the channel.',
                        ['action' => 'kick_participant', 'channelName' => $channelName, 'userId' => (string)$targetUid]
                    );
                }

                return response()->json(['status' => true, 'message' => 'User kicked successfully.']);
            }

            return response()->json(['status' => false, 'message' => 'User session not found.'], 404);

        } catch (ValidationException $e) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to kick user.', 'error' => $e->getMessage()], 500);
        }
    }

    public function raiseHand(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user || $user->role !== 'user') {
                return response()->json(['status' => false, 'message' => 'Unauthorized.'], 401);
            }

            $request->validate([
                'channelName' => 'required|string'
            ]);

            $channelName = $request->channelName;
            $uid = $user->id;

            $admins = User::where('role', 'admin')->whereNotNull('fcm_token')->get();
            $tokens = $admins->pluck('fcm_token')->toArray();

            if (!empty($tokens)) {
                $this->firebaseService->sendNotification(
                    $tokens,
                    'A user raised his hand',
                    "{$user->name} raised his hand",
                    ['action' => 'raise_hand', 'channelName' => $channelName, 'userId' => (string)$uid]
                );
            }

            return response()->json(['status' => true, 'message' => 'User raised his hand successfully.']);

        } catch (ValidationException $e) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to raise hand.', 'error' => $e->getMessage()], 500);
        }
    }

    public function endSession(Request $request)
    {
        try {
            $user = auth('api')->user();

            $request->validate([
                'channelName' => 'required|string',
            ]);

            $channelName = $request->channelName;
            $participantsKey = "agora_channel_participants_{$channelName}";

            if ($user->role === 'admin') {
                // Admin: End session for everyone
                $participants = Cache::get($participantsKey, []);

                // Clear individual sessions
                foreach ($participants as $uid) {
                    Cache::forget("agora_session_{$channelName}_{$uid}");

                    $targetUser = User::find($uid);
                    if ($targetUser && $targetUser->fcm_token) {
                        $this->firebaseService->sendNotification(
                            $targetUser->fcm_token,
                            'Session has ended',
                            'The session has ended.',
                            ['action' => 'session_ended', 'channelName' => $channelName, 'userId' => (string)$uid]
                        );
                    }
                }

                // Clear participants list
                Cache::forget($participantsKey);

                return response()->json(['status' => true, 'message' => 'Session ended for all users.']);
            } else {
                // Regular User: Leave session
                $uid = $user->id;

                // Remove from participants list
                $participants = Cache::get($participantsKey, []);
                if (($key = array_search($uid, $participants)) !== false) {
                    unset($participants[$key]);
                    $ttl = isset($participants['expires_at']) ? ($participants['expires_at'] - now()->timestamp) : 86400;
                    if ($ttl > 0) Cache::put($participantsKey, array_values($participants), $ttl);
                }

                // Clear individual session
                Cache::forget("agora_session_{$channelName}_{$uid}");

                return response()->json(['status' => true, 'message' => 'You left the session successfully.']);
            }

        } catch (ValidationException $e) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to end session.', 'error' => $e->getMessage()], 500);
        }
    }
}
