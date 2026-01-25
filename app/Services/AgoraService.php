<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

require_once app_path('Services/Agora/AccessToken.php');

class AgoraService
{
    protected string $appId;
    protected string $appCertificate;

    public function __construct()
    {
        $this->appId = config('services.agora.app_id');
        $this->appCertificate = config('services.agora.app_certificate');

        Log::info('AgoraService initialized', [
            'appId' => $this->appId,
            'appCertificate' => $this->appCertificate ? 'set' : 'null'
        ]);
    }

    /**
     * توليد Token رسمي للتجربة أو الإنتاج
     */
    public function generateToken(string $channelName, int $uid = 0, int $expireTimeInSeconds = 3600)
    {
        Log::info("Generating Agora token", [
            'channelName' => $channelName,
            'uid' => $uid,
            'expireTime' => $expireTimeInSeconds
        ]);

        try {
            $accessToken = \AccessToken::init($this->appId, $this->appCertificate, $channelName, $uid);
            $expireTimestamp = time() + $expireTimeInSeconds;
            $accessToken->addPrivilege(\AccessToken::Privileges["kJoinChannel"], $expireTimestamp);
            $accessToken->addPrivilege(\AccessToken::Privileges["kPublishAudioStream"], $expireTimestamp);
            $accessToken->addPrivilege(\AccessToken::Privileges["kPublishVideoStream"], $expireTimestamp);

            $token = $accessToken->build();

            Log::info("Token generated successfully", ['token' => $token]);
            return $token;
        } catch (\Throwable $e) {
            Log::error("Error generating Agora token", [
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
