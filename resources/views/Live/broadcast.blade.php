<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live - {{ $channelName }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white; 
            min-height: 100vh;
        }
        
        .container {
            display: flex;
            height: 100vh;
            padding: 20px;
            gap: 20px;
        }
        
        .main-content {
            flex: 3;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow-y: auto;
        }
        
        .header {
            background: rgba(0, 0, 0, 0.3);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        
        .player-wrapper {
            background: #000;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            aspect-ratio: 16/9;
        }
        
        .player-wrapper:hover {
            border-color: #4cc9f0;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        .player-wrapper.admin {
            border-color: #28a745;
        }
        
        .player-wrapper.hand-raised {
            border-color: #ffd700;
            animation: pulse 2s infinite;
        }
        
        .player-wrapper.video-disabled {
            border-color: #ff6b00;
        }
        
        .player-wrapper.muted {
            border-color: #ff4757;
        }
        
        @keyframes pulse {
            0% { border-color: #ffd700; }
            50% { border-color: #ff9500; }
            100% { border-color: #ffd700; }
        }
        
        .player-div {
            width: 100%;
            height: 100%;
        }
        
        .video-placeholder {
            width: 100%;
            height: 100%;
            background: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .controls {
            position: absolute;
            bottom: 0;
            width: 100%;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            display: flex;
            justify-content: center;
            padding: 10px;
            gap: 5px;
        }
        
        .tag {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0,0,0,0.7);
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #4cc9f0, #4361ee);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #f72585, #b5179e);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #ff9e00, #ff6b00);
            color: white;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            opacity: 0.9;
        }
        
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .file-upload {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border: 2px dashed rgba(255, 255, 255, 0.2);
        }
        
        .file-list {
            margin-top: 10px;
        }
        
        .file-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .participants-list {
            margin-top: 20px;
        }
        
        .participant-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .participant-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .hand-icon {
            color: #ffd700;
            animation: wave 1s infinite;
        }
        
        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-20deg); }
            75% { transform: rotate(20deg); }
        }
        
        .muted-icon {
            color: #ff4757;
        }
        
        .video-disabled-icon {
            color: #ff6b00;
        }
        
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .online { background: #2ecc71; }
        .offline { background: #e74c3c; }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.9);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #4cc9f0;
            display: none;
            z-index: 1000;
        }
        
        .screen-share-container {
            width: 100%;
            height: 300px;
            background: #000;
            border-radius: 10px;
            margin-top: 15px;
            overflow: hidden;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-video"></i> Channel: {{ $channelName }}</h1>
                <p>Your ID: <strong>{{ $uid }}</strong> | 
                   Role: <span style="color:{{ $isAdmin ? '#28a745' : '#4cc9f0' }}">{{ $isAdmin ? 'Teacher (Admin)' : 'Student' }}</span></p>
                
                <div style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                    <button class="btn-primary" id="join-btn">
                        <i class="fas fa-sign-in-alt"></i> Join Stream
                    </button>
                    <button class="btn-danger" id="leave-btn" disabled>
                        <i class="fas fa-sign-out-alt"></i> Leave
                    </button>
                    
                    @if($isAdmin)
                    <button class="btn-warning" id="mute-all-btn" disabled>
                        <i class="fas fa-volume-mute"></i> Mute All
                    </button>
                    <button class="btn-success" id="unmute-all-btn" disabled>
                        <i class="fas fa-volume-up"></i> Unmute All
                    </button>
                    <button class="btn-warning" id="disable-all-video-btn" disabled>
                        <i class="fas fa-video-slash"></i> Disable All Video
                    </button>
                    <button class="btn-secondary" id="share-screen-btn" disabled>
                        <i class="fas fa-desktop"></i> Share Screen
                    </button>
                    @endif
                    
                    <button class="btn-secondary" id="raise-hand-btn" disabled>
                        <i class="fas fa-hand-paper"></i> Raise Hand
                    </button>
                </div>
            </div>
            
            <div class="video-grid">
                <div id="local-player-wrapper" class="player-wrapper" style="display:none;">
                    <div class="tag">
                        <i class="fas fa-user"></i> You
                        @if($isAdmin)<span style="color:#28a745; margin-left:5px;"><i class="fas fa-crown"></i></span>@endif
                    </div>
                    <div id="local-player" class="player-div"></div>
                    <div class="controls">
                        <button class="btn-secondary" id="toggle-video">
                            <i class="fas fa-video"></i> Camera
                        </button>
                        <button class="btn-secondary" id="toggle-audio">
                            <i class="fas fa-microphone"></i> Mic
                        </button>
                    </div>
                </div>
                
                @if($isAdmin)
                <div id="screen-share-container" class="screen-share-container" style="display:none;">
                    <div class="tag" style="background: #4361ee;">
                        <i class="fas fa-desktop"></i> Screen Sharing
                    </div>
                    <div id="screen-share-player" class="player-div"></div>
                </div>
                @endif
                
                <div id="remote-players" style="display: contents;"></div>
            </div>
        </div>
        
        <div class="sidebar">
            <!-- قسم الأدمن فقط -->
            @if($isAdmin)
            <div id="admin-panel">
                <h3><i class="fas fa-users"></i> Participants ({{ count($participants) }})</h3>
                <div class="participants-list" id="participants-list"></div>
                
                <div class="file-upload">
                    <h4><i class="fas fa-file-upload"></i> Share Files</h4>
                    <input type="file" id="file-input" multiple style="width:100%; padding:10px; background:rgba(255,255,255,0.1); border-radius:5px;">
                    <div class="file-list" id="file-list"></div>
                </div>
            </div>
            @else
            <!-- قسم الطالب -->
            <div id="student-panel">
                <h3><i class="fas fa-info-circle"></i> Class Information</h3>
                <div style="padding: 15px; background: rgba(255,255,255,0.05); border-radius: 10px; margin-top: 10px;">
                    <p><i class="fas fa-chalkboard-teacher"></i> <strong>Teacher is online</strong></p>
                    <p><i class="fas fa-hand-paper"></i> Click "Raise Hand" to ask questions</p>
                    <p><i class="fas fa-microphone"></i> Wait for teacher to unmute you</p>
                    <p><i class="fas fa-user"></i> Your ID: <strong>{{ $uid }}</strong></p>
                </div>
            </div>
            @endif
            
            <div style="margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px;">
                <h4><i class="fas fa-info-circle"></i> Instructions</h4>
                <ul style="margin-top: 10px; padding-left: 20px; font-size: 14px;">
                    <li>Click "Join Stream" to enter the class</li>
                    @if(!$isAdmin)
                    <li>You can only see the teacher</li>
                    <li>Use "Raise Hand" to request to speak</li>
                    <li>Teacher controls your audio/video</li>
                    @else
                    <li>You see all students</li>
                    <li>Control student audio/video</li>
                    <li>Share files and screen</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        const appId = "{{ $appId }}";
        const token = "{{ $token }}";
        const channelName = "{{ $channelName }}";
        const uid = {{ $uid }};
        const isAdmin = {{ $isAdmin ? 'true' : 'false' }};
        const adminUid = {{ $adminUid }};
        
        let client = null;
        let localTracks = [];
        let screenTrack = null;
        let remoteUsers = {};
        let sharedFiles = [];
        let isSharingScreen = false;

        // عناصر DOM
        const elements = {
            joinBtn: document.getElementById('join-btn'),
            leaveBtn: document.getElementById('leave-btn'),
            raiseHandBtn: document.getElementById('raise-hand-btn'),
            muteAllBtn: document.getElementById('mute-all-btn'),
            unmuteAllBtn: document.getElementById('unmute-all-btn'),
            disableAllVideoBtn: document.getElementById('disable-all-video-btn'),
            shareScreenBtn: document.getElementById('share-screen-btn'),
            toggleVideo: document.getElementById('toggle-video'),
            toggleAudio: document.getElementById('toggle-audio'),
            participantsList: document.getElementById('participants-list'),
            fileInput: document.getElementById('file-input'),
            fileList: document.getElementById('file-list'),
            notification: document.getElementById('notification'),
            screenShareContainer: document.getElementById('screen-share-container'),
            screenSharePlayer: document.getElementById('screen-share-player')
        };

        // تهيئة Agora
        async function initAgora() {
            client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
            
            // تعريف الأحداث
            client.on("user-published", handleUserPublished);
            client.on("user-unpublished", handleUserUnpublished);
            client.on("user-left", handleUserLeft);
        }

        // الانضمام للقناة
        async function joinChannel() {
            try {
                await client.join(appId, channelName, token, uid);
                
                // إنشاء وعرض التراكات المحلية
                localTracks = await AgoraRTC.createMicrophoneAndCameraTracks();
                document.getElementById('local-player-wrapper').style.display = 'block';
                localTracks[1].play('local-player');
                await client.publish(localTracks);
                
                // تحديث حالة الأزرار
                elements.joinBtn.disabled = true;
                elements.leaveBtn.disabled = false;
                elements.raiseHandBtn.disabled = false;
                
                if (isAdmin) {
                    elements.muteAllBtn.disabled = false;
                    elements.unmuteAllBtn.disabled = false;
                    elements.disableAllVideoBtn.disabled = false;
                    elements.shareScreenBtn.disabled = false;
                }
                
                showNotification('Successfully joined the channel!', 'success');
                
                // الأدمن فقط يبدأ بتحديث قائمة المشاركين
                if (isAdmin) {
                    startParticipantsPolling();
                }
                
            } catch (error) {
                console.error('Join channel failed:', error);
                showNotification('Failed to join channel: ' + error.message, 'error');
            }
        }

        // التعامل مع نشر المستخدم
        async function handleUserPublished(user, mediaType) {
            try {
                await client.subscribe(user, mediaType);
                
                // منطق العرض:
                // 1. الأدمن يرى الجميع
                // 2. الطلاب يرون فقط الأدمن
                const shouldShow = isAdmin || user.uid == adminUid;
                
                if (mediaType === "video" && shouldShow) {
                    renderUser(user);
                }
                
                if (mediaType === "audio") {
                    user.audioTrack.play();
                    
                    // إذا كان المستخدم muted، إيقاف الصوت
                    if (remoteUsers[user.uid]?.isMuted) {
                        user.audioTrack.setVolume(0);
                    }
                }
                
                // حفظ المستخدم
                if (!remoteUsers[user.uid]) {
                    remoteUsers[user.uid] = {
                        user: user,
                        isMuted: false,
                        videoEnabled: true
                    };
                }
                
            } catch (error) {
                console.error('Subscribe failed:', error);
            }
        }

        // التعامل مع إلغاء نشر المستخدم
        function handleUserUnpublished(user) {
            const wrapper = document.getElementById(`wrapper-${user.uid}`);
            if (wrapper) {
                wrapper.remove();
            }
        }

        // التعامل مع مغادرة المستخدم
        function handleUserLeft(user) {
            handleUserUnpublished(user);
            delete remoteUsers[user.uid];
            if (isAdmin) updateParticipantsList();
        }

        // عرض مستخدم
        function renderUser(user) {
            if (document.getElementById(`wrapper-${user.uid}`)) {
                // إذا كان موجوداً بالفعل، تأكد من أن الفيديو يعمل
                const player = document.getElementById(`player-${user.uid}`);
                if (player && user.videoTrack && remoteUsers[user.uid]?.videoEnabled !== false) {
                    user.videoTrack.play(`player-${user.uid}`);
                }
                return;
            }
            
            const wrapper = document.createElement('div');
            wrapper.id = `wrapper-${user.uid}`;
            wrapper.className = `player-wrapper ${user.uid == adminUid ? 'admin' : ''}`;
            
            const isVideoEnabled = remoteUsers[user.uid]?.videoEnabled !== false;
            
            if (isVideoEnabled) {
                wrapper.innerHTML = `
                    <div class="tag">
                        <i class="fas ${user.uid == adminUid ? 'fa-crown' : 'fa-user'}"></i>
                        ${user.uid == adminUid ? 'Teacher' : 'Student ' + user.uid}
                        ${remoteUsers[user.uid]?.isMuted ? '<i class="fas fa-volume-mute muted-icon" style="margin-left:5px;"></i>' : ''}
                    </div>
                    <div id="player-${user.uid}" class="player-div"></div>
                    <div class="controls">
                        ${isAdmin && user.uid != uid ? `
                            <button class="btn-secondary" onclick="muteUser(${user.uid})">
                                <i class="fas fa-volume-mute"></i> ${remoteUsers[user.uid]?.isMuted ? 'Unmute' : 'Mute'}
                            </button>
                            <button class="btn-secondary" onclick="disableUserVideo(${user.uid})">
                                <i class="fas fa-video-slash"></i> Disable Video
                            </button>
                            <button class="btn-danger" onclick="kickUser(${user.uid})">
                                <i class="fas fa-user-slash"></i> Kick
                            </button>
                        ` : ''}
                    </div>
                `;
                
                if (user.videoTrack) {
                    user.videoTrack.play(`player-${user.uid}`);
                }
            } else {
                wrapper.className += ' video-disabled';
                wrapper.innerHTML = `
                    <div class="tag">
                        <i class="fas fa-user-slash"></i>
                        Student ${user.uid} (Video Disabled)
                        ${remoteUsers[user.uid]?.isMuted ? '<i class="fas fa-volume-mute muted-icon" style="margin-left:5px;"></i>' : ''}
                    </div>
                    <div class="video-placeholder">
                        <i class="fas fa-video-slash" style="font-size: 50px; color: #666; margin-bottom: 10px;"></i>
                        <div style="color: #999; font-size: 14px;">Video disabled by teacher</div>
                    </div>
                    <div class="controls">
                        ${isAdmin && user.uid != uid ? `
                            <button class="btn-secondary" onclick="muteUser(${user.uid})">
                                <i class="fas fa-volume-mute"></i> ${remoteUsers[user.uid]?.isMuted ? 'Unmute' : 'Mute'}
                            </button>
                            <button class="btn-success" onclick="enableUserVideo(${user.uid})">
                                <i class="fas fa-video"></i> Enable Video
                            </button>
                            <button class="btn-danger" onclick="kickUser(${user.uid})">
                                <i class="fas fa-user-slash"></i> Kick
                            </button>
                        ` : ''}
                    </div>
                `;
            }
            
            document.getElementById('remote-players').appendChild(wrapper);
        }

        // Mute مستخدم
        async function muteUser(targetUid) {
            try {
                const response = await fetch('/agora/mute', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        channel: channelName,
                        targetUid: targetUid
                    })
                });
                
                if (response.ok) {
                    // تبديل حالة Mute
                    const isCurrentlyMuted = remoteUsers[targetUid]?.isMuted;
                    
                    if (remoteUsers[targetUid] && remoteUsers[targetUid].user.audioTrack) {
                        if (isCurrentlyMuted) {
                            remoteUsers[targetUid].user.audioTrack.setVolume(100);
                            remoteUsers[targetUid].isMuted = false;
                            showNotification(`User ${targetUid} unmuted`, 'success');
                        } else {
                            remoteUsers[targetUid].user.audioTrack.setVolume(0);
                            remoteUsers[targetUid].isMuted = true;
                            showNotification(`User ${targetUid} muted`, 'warning');
                        }
                        
                        // تحديث الواجهة
                        const wrapper = document.getElementById(`wrapper-${targetUid}`);
                        if (wrapper) {
                            const tag = wrapper.querySelector('.tag');
                            if (isCurrentlyMuted) {
                                tag.innerHTML = tag.innerHTML.replace('<i class="fas fa-volume-mute muted-icon" style="margin-left:5px;"></i>', '');
                            } else {
                                tag.innerHTML += '<i class="fas fa-volume-mute muted-icon" style="margin-left:5px;"></i>';
                            }
                            
                            // تحديث زر Mute
                            const muteBtn = wrapper.querySelector('.controls button:first-child');
                            if (muteBtn) {
                                muteBtn.innerHTML = `<i class="fas fa-volume-mute"></i> ${isCurrentlyMuted ? 'Mute' : 'Unmute'}`;
                            }
                        }
                        
                        updateParticipantsList();
                    }
                }
            } catch (error) {
                console.error('Mute failed:', error);
                showNotification('Failed to mute user', 'error');
            }
        }

        // Mute للكل
        async function muteAll() {
            if (!confirm('Mute all students?')) return;
            
            try {
                const response = await fetch('/agora/mute', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        channel: channelName,
                        muteAll: true
                    })
                });
                
                if (response.ok) {
                    // إيقاف الصوت للجميع
                    Object.keys(remoteUsers).forEach(uid => {
                        if (remoteUsers[uid] && remoteUsers[uid].user.audioTrack && uid != uid) {
                            remoteUsers[uid].user.audioTrack.setVolume(0);
                            remoteUsers[uid].isMuted = true;
                            
                            // تحديث الواجهة
                            const wrapper = document.getElementById(`wrapper-${uid}`);
                            if (wrapper) {
                                const tag = wrapper.querySelector('.tag');
                                if (!tag.innerHTML.includes('fa-volume-mute')) {
                                    tag.innerHTML += '<i class="fas fa-volume-mute muted-icon" style="margin-left:5px;"></i>';
                                }
                                
                                const muteBtn = wrapper.querySelector('.controls button:first-child');
                                if (muteBtn) {
                                    muteBtn.innerHTML = '<i class="fas fa-volume-mute"></i> Unmute';
                                }
                            }
                        }
                    });
                    
                    showNotification('All students muted', 'warning');
                    updateParticipantsList();
                }
            } catch (error) {
                console.error('Mute all failed:', error);
                showNotification('Failed to mute all users', 'error');
            }
        }

        // Unmute للكل
        async function unmuteAll() {
            try {
                // استعادة الصوت للجميع
                Object.keys(remoteUsers).forEach(uid => {
                    if (remoteUsers[uid] && remoteUsers[uid].user.audioTrack && uid != uid) {
                        remoteUsers[uid].user.audioTrack.setVolume(100);
                        remoteUsers[uid].isMuted = false;
                        
                        // تحديث الواجهة
                        const wrapper = document.getElementById(`wrapper-${uid}`);
                        if (wrapper) {
                            const tag = wrapper.querySelector('.tag');
                            tag.innerHTML = tag.innerHTML.replace('<i class="fas fa-volume-mute muted-icon" style="margin-left:5px;"></i>', '');
                            
                            const muteBtn = wrapper.querySelector('.controls button:first-child');
                            if (muteBtn) {
                                muteBtn.innerHTML = '<i class="fas fa-volume-mute"></i> Mute';
                            }
                        }
                    }
                });
                
                showNotification('All students unmuted', 'success');
                updateParticipantsList();
            } catch (error) {
                console.error('Unmute all failed:', error);
                showNotification('Failed to unmute all users', 'error');
            }
        }

        // إيقاف كاميرا مستخدم
        async function disableUserVideo(targetUid) {
            try {
                const response = await fetch('/agora/disable-video', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        channel: channelName,
                        targetUid: targetUid
                    })
                });
                
                if (response.ok) {
                    // تحديث حالة الفيديو
                    if (remoteUsers[targetUid]) {
                        remoteUsers[targetUid].videoEnabled = false;
                        
                        // تحديث الواجهة
                        const wrapper = document.getElementById(`wrapper-${targetUid}`);
                        if (wrapper) {
                            wrapper.className += ' video-disabled';
                            wrapper.innerHTML = `
                                <div class="tag">
                                    <i class="fas fa-user-slash"></i>
                                    Student ${targetUid} (Video Disabled)
                                    ${remoteUsers[targetUid]?.isMuted ? '<i class="fas fa-volume-mute muted-icon" style="margin-left:5px;"></i>' : ''}
                                </div>
                                <div class="video-placeholder">
                                    <i class="fas fa-video-slash" style="font-size: 50px; color: #666; margin-bottom: 10px;"></i>
                                    <div style="color: #999; font-size: 14px;">Video disabled by teacher</div>
                                </div>
                                <div class="controls">
                                    <button class="btn-secondary" onclick="muteUser(${targetUid})">
                                        <i class="fas fa-volume-mute"></i> ${remoteUsers[targetUid]?.isMuted ? 'Unmute' : 'Mute'}
                                    </button>
                                    <button class="btn-success" onclick="enableUserVideo(${targetUid})">
                                        <i class="fas fa-video"></i> Enable Video
                                    </button>
                                    <button class="btn-danger" onclick="kickUser(${targetUid})">
                                        <i class="fas fa-user-slash"></i> Kick
                                    </button>
                                </div>
                            `;
                        }
                        
                        showNotification(`User ${targetUid} video disabled`, 'warning');
                        updateParticipantsList();
                    }
                }
            } catch (error) {
                console.error('Disable video failed:', error);
                showNotification('Failed to disable video', 'error');
            }
        }

        // تشغيل كاميرا مستخدم
        async function enableUserVideo(targetUid) {
            try {
                // تحديث حالة الفيديو
                if (remoteUsers[targetUid]) {
                    remoteUsers[targetUid].videoEnabled = true;
                    
                    // تحديث الواجهة
                    const wrapper = document.getElementById(`wrapper-${targetUid}`);
                    if (wrapper && remoteUsers[targetUid].user) {
                        wrapper.className = wrapper.className.replace(' video-disabled', '');
                        wrapper.innerHTML = `
                            <div class="tag">
                                <i class="fas fa-user"></i>
                                Student ${targetUid}
                                ${remoteUsers[targetUid]?.isMuted ? '<i class="fas fa-volume-mute muted-icon" style="margin-left:5px;"></i>' : ''}
                            </div>
                            <div id="player-${targetUid}" class="player-div"></div>
                            <div class="controls">
                                <button class="btn-secondary" onclick="muteUser(${targetUid})">
                                    <i class="fas fa-volume-mute"></i> ${remoteUsers[targetUid]?.isMuted ? 'Unmute' : 'Mute'}
                                </button>
                                <button class="btn-secondary" onclick="disableUserVideo(${targetUid})">
                                    <i class="fas fa-video-slash"></i> Disable Video
                                </button>
                                <button class="btn-danger" onclick="kickUser(${targetUid})">
                                    <i class="fas fa-user-slash"></i> Kick
                                </button>
                            </div>
                        `;
                        
                        // إعادة تشغيل الفيديو
                        if (remoteUsers[targetUid].user.videoTrack) {
                            remoteUsers[targetUid].user.videoTrack.play(`player-${targetUid}`);
                        }
                    }
                    
                    showNotification(`User ${targetUid} video enabled`, 'success');
                    updateParticipantsList();
                }
            } catch (error) {
                console.error('Enable video failed:', error);
                showNotification('Failed to enable video', 'error');
            }
        }

        // إيقاف كاميرا للكل
        async function disableAllVideo() {
            if (!confirm('Disable video for all students?')) return;
            
            try {
                const response = await fetch('/agora/disable-video', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        channel: channelName,
                        disableAll: true
                    })
                });
                
                if (response.ok) {
                    // إيقاف الفيديو للجميع
                    Object.keys(remoteUsers).forEach(uid => {
                        if (uid != uid) {
                            disableUserVideo(uid);
                        }
                    });
                    
                    showNotification('All students video disabled', 'warning');
                }
            } catch (error) {
                console.error('Disable all video failed:', error);
                showNotification('Failed to disable all videos', 'error');
            }
        }

        // طرد مستخدم
        async function kickUser(targetUid) {
            if (!confirm(`Kick user ${targetUid}?`)) return;
            
            try {
                const response = await fetch('/agora/kick', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        channel: channelName,
                        targetUid: targetUid
                    })
                });
                
                if (response.ok) {
                    // إزالة المستخدم من الواجهة
                    const wrapper = document.getElementById(`wrapper-${targetUid}`);
                    if (wrapper) {
                        wrapper.remove();
                    }
                    
                    // إزالة المستخدم من التتبع
                    delete remoteUsers[targetUid];
                    
                    showNotification(`User ${targetUid} kicked`, 'error');
                    updateParticipantsList();
                }
            } catch (error) {
                console.error('Kick failed:', error);
                showNotification('Failed to kick user', 'error');
            }
        }

        // مشاركة الشاشة
        async function shareScreen() {
            if (isSharingScreen) {
                // إيقاف مشاركة الشاشة
                await client.unpublish(screenTrack);
                screenTrack.close();
                elements.screenShareContainer.style.display = 'none';
                elements.shareScreenBtn.innerHTML = '<i class="fas fa-desktop"></i> Share Screen';
                isSharingScreen = false;
            } else {
                try {
                    // بدء مشاركة الشاشة
                    screenTrack = await AgoraRTC.createScreenVideoTrack();
                    await client.publish(screenTrack);
                    screenTrack.play('screen-share-player');
                    elements.screenShareContainer.style.display = 'block';
                    elements.shareScreenBtn.innerHTML = '<i class="fas fa-stop"></i> Stop Sharing';
                    isSharingScreen = true;
                    showNotification('Screen sharing started', 'success');
                } catch (error) {
                    console.error('Screen sharing failed:', error);
                    showNotification('Screen sharing failed: ' + error.message, 'error');
                }
            }
        }

        // مشاركة ملفات
        function handleFileUpload() {
            const files = elements.fileInput.files;
            
            for (let file of files) {
                sharedFiles.push({
                    name: file.name,
                    size: (file.size / 1024).toFixed(2) + ' KB',
                    type: file.type,
                    url: URL.createObjectURL(file),
                    uploadedAt: new Date().toLocaleTimeString()
                });
                
                // عرض الملف في القائمة
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <div>
                        <i class="fas ${getFileIcon(file.type)}"></i> ${file.name}
                        <span style="font-size:12px; color:#ccc; margin-left:10px;">${(file.size/1024).toFixed(2)} KB</span>
                    </div>
                    <a href="${URL.createObjectURL(file)}" download="${file.name}" class="btn-secondary" style="padding:5px 10px;">
                        <i class="fas fa-download"></i>
                    </a>
                `;
                elements.fileList.appendChild(fileItem);
            }
            
            showNotification(`${files.length} file(s) uploaded`, 'success');
            elements.fileInput.value = '';
        }

        function getFileIcon(fileType) {
            if (fileType.includes('pdf')) return 'fa-file-pdf';
            if (fileType.includes('word') || fileType.includes('document')) return 'fa-file-word';
            if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'fa-file-excel';
            if (fileType.includes('image')) return 'fa-file-image';
            if (fileType.includes('video')) return 'fa-file-video';
            if (fileType.includes('audio')) return 'fa-file-audio';
            return 'fa-file';
        }

        // تحديث قائمة المشاركين (للأدمن فقط)
        async function updateParticipantsList() {
            if (!isAdmin) return;
            
            try {
                const response = await fetch(`/agora/participants/${channelName}`);
                const participants = await response.json();
                
                elements.participantsList.innerHTML = '';
                
                Object.values(participants).forEach(participant => {
                    const item = document.createElement('div');
                    item.className = 'participant-item';
                    
                    item.innerHTML = `
                        <div>
                            <span class="status-indicator online"></span>
                            <strong>${participant.name}</strong>
                            ${participant.isAdmin ? '<span style="color:#28a745; margin-left:5px;"><i class="fas fa-crown"></i></span>' : ''}
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            ${participant.raisedHand ? '<i class="fas fa-hand-paper hand-icon"></i>' : ''}
                            ${participant.isMuted ? '<i class="fas fa-volume-mute muted-icon"></i>' : ''}
                            ${!participant.videoEnabled ? '<i class="fas fa-video-slash video-disabled-icon"></i>' : ''}
                            <span style="font-size:12px; color:#ccc;">ID: ${participant.uid}</span>
                        </div>
                    `;
                    
                    elements.participantsList.appendChild(item);
                });
                
            } catch (error) {
                console.error('Failed to update participants:', error);
            }
        }

        // بدء تحديث قائمة المشاركين (للأدمن فقط)
        function startParticipantsPolling() {
            if (isAdmin) {
                updateParticipantsList();
                setInterval(updateParticipantsList, 3000);
            }
        }

        // عرض الإشعارات
        function showNotification(message, type = 'info') {
            const notification = elements.notification;
            notification.textContent = message;
            
            const colors = {
                success: '#2ecc71',
                error: '#e74c3c',
                warning: '#f39c12',
                info: '#3498db'
            };
            
            notification.style.borderLeftColor = colors[type] || colors.info;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // المغادرة
        async function leaveChannel() {
            try {
                if (localTracks) {
                    localTracks.forEach(track => {
                        track.stop();
                        track.close();
                    });
                    localTracks = [];
                }
                
                if (screenTrack) {
                    screenTrack.stop();
                    screenTrack.close();
                }
                
                if (client) {
                    await client.leave();
                }
                
                location.reload();
                
            } catch (error) {
                console.error('Leave failed:', error);
            }
        }

        // تبديل الكاميرا
        function toggleVideo() {
            if (localTracks[1]) {
                localTracks[1].setEnabled(!localTracks[1].enabled);
                elements.toggleVideo.innerHTML = localTracks[1].enabled ? 
                    '<i class="fas fa-video"></i> Camera' : 
                    '<i class="fas fa-video-slash"></i> Camera';
            }
        }

        // تبديل المايكروفون
        function toggleAudio() {
            if (localTracks[0]) {
                localTracks[0].setEnabled(!localTracks[0].enabled);
                elements.toggleAudio.innerHTML = localTracks[0].enabled ? 
                    '<i class="fas fa-microphone"></i> Mic' : 
                    '<i class="fas fa-microphone-slash"></i> Mic';
            }
        }

        // رفع اليد
        async function raiseHand() {
            try {
                const response = await fetch('/agora/raise-hand', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        uid: uid,
                        channel: channelName,
                        action: 'raise'
                    })
                });
                
                if (response.ok) {
                    showNotification('Hand raised! Waiting for teacher...', 'success');
                    elements.raiseHandBtn.innerHTML = '<i class="fas fa-hand-paper"></i> Lower Hand';
                    elements.raiseHandBtn.onclick = lowerHand;
                }
            } catch (error) {
                console.error('Raise hand failed:', error);
            }
        }

        // خفض اليد
        async function lowerHand() {
            try {
                const response = await fetch('/agora/raise-hand', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        uid: uid,
                        channel: channelName,
                        action: 'lower'
                    })
                });
                
                if (response.ok) {
                    showNotification('Hand lowered', 'info');
                    elements.raiseHandBtn.innerHTML = '<i class="fas fa-hand-paper"></i> Raise Hand';
                    elements.raiseHandBtn.onclick = raiseHand;
                }
            } catch (error) {
                console.error('Lower hand failed:', error);
            }
        }

        // تعريف الأحداث
        function setupEventListeners() {
            elements.joinBtn.addEventListener('click', joinChannel);
            elements.leaveBtn.addEventListener('click', leaveChannel);
            elements.raiseHandBtn.addEventListener('click', raiseHand);
            
            if (isAdmin) {
                if (elements.muteAllBtn) elements.muteAllBtn.addEventListener('click', muteAll);
                if (elements.unmuteAllBtn) elements.unmuteAllBtn.addEventListener('click', unmuteAll);
                if (elements.disableAllVideoBtn) elements.disableAllVideoBtn.addEventListener('click', disableAllVideo);
                if (elements.shareScreenBtn) elements.shareScreenBtn.addEventListener('click', shareScreen);
            }
            
            if (elements.toggleVideo) elements.toggleVideo.addEventListener('click', toggleVideo);
            if (elements.toggleAudio) elements.toggleAudio.addEventListener('click', toggleAudio);
            if (elements.fileInput) elements.fileInput.addEventListener('change', handleFileUpload);
        }

        // تهيئة التطبيق
        async function init() {
            await initAgora();
            setupEventListeners();
            
            // إخفاء أزرار الأدمن من الطلاب
            if (!isAdmin) {
                if (elements.muteAllBtn) elements.muteAllBtn.style.display = 'none';
                if (elements.unmuteAllBtn) elements.unmuteAllBtn.style.display = 'none';
                if (elements.disableAllVideoBtn) elements.disableAllVideoBtn.style.display = 'none';
                if (elements.shareScreenBtn) elements.shareScreenBtn.style.display = 'none';
                if (elements.fileInput) elements.fileInput.style.display = 'none';
            }
            
            console.log('Application initialized. UID:', uid, 'Admin:', isAdmin);
        }

        // بدء التطبيق
        document.addEventListener('DOMContentLoaded', init);

        // جعل الدوال متاحة عالمياً
        window.kickUser = kickUser;
        window.muteUser = muteUser;
        window.disableUserVideo = disableUserVideo;
        window.enableUserVideo = enableUserVideo;
        window.raiseHand = raiseHand;
        window.lowerHand = lowerHand;
    </script>
</body>
</html>