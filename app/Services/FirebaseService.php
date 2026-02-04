<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(base_path(config('services.firebase.credentials')));
        $this->client->addScope('https://www.googleapis.com/auth/firebase.messaging');
    }

    public function sendNotification($tokens, $title, $body, $data = [])
    {
        if (empty($tokens)) return;

        $tokens = is_array($tokens) ? $tokens : [$tokens];
        $accessToken = $this->getAccessToken();

        foreach ($tokens as $token) {
            Log::info("Sending Firebase notification to: {$token}", [
                'title' => $title, 
                'body' => $body
            ]);

            $message = [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ];

            if (!empty($data)) {
                $message['data'] = $data;
            }

            $response = Http::withToken($accessToken)
                ->post('https://fcm.googleapis.com/v1/projects/' . $this->getProjectId() . '/messages:send', [
                    'message' => $message
                ]);

            if ($response->failed()) {
                Log::error('Firebase Notification Failed', [
                    'token' => $token,
                    'status' => $response->status(),
                    'error' => $response->json()
                ]);
            } else {
                Log::info('Firebase Notification Sent Successfully', [
                    'token' => $token,
                    'response' => $response->json()
                ]);
            }
        }
    }

    protected function getAccessToken()
    {
        $this->client->fetchAccessTokenWithAssertion();
        $token = $this->client->getAccessToken();
        return $token['access_token'];
    }

    protected function getProjectId()
    {
        $credentials = json_decode(file_get_contents(base_path(config('services.firebase.credentials'))), true);
        return $credentials['project_id'];
    }
}
