<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FcmPushService
{
    public function isConfigured(): bool
    {
        $projectId = config('fcm.project_id');
        $path = $this->serviceAccountPath();

        return filled($projectId) && $path !== null && is_readable($path);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        $tokens = DeviceToken::query()
            ->where('user_id', $user->id)
            ->pluck('token');

        $sent = 0;
        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        if (! $this->isConfigured()) {
            Log::info('FCM skipped: credentials not configured.', [
                'token_suffix' => substr($token, -8),
            ]);

            return false;
        }

        try {
            $accessToken = $this->accessToken();
            $projectId = config('fcm.project_id');

            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => collect($data)->map(fn ($v) => (string) $v)->all(),
                    'android' => [
                        'priority' => 'high',
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    $payload
                );

            if (! $response->successful()) {
                Log::warning('FCM send failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'token_suffix' => substr($token, -8),
                ]);

                if (in_array($response->status(), [404, 410], true)) {
                    DeviceToken::query()->where('token', $token)->delete();
                }

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FCM send exception: '.$e->getMessage(), [
                'token_suffix' => substr($token, -8),
            ]);

            return false;
        }
    }

    private function serviceAccountPath(): ?string
    {
        $configured = config('fcm.service_account');
        if (! filled($configured)) {
            return null;
        }

        if (is_file($configured)) {
            return $configured;
        }

        $relative = base_path($configured);
        if (is_file($relative)) {
            return $relative;
        }

        return null;
    }

    private function accessToken(): string
    {
        $path = $this->serviceAccountPath();
        if ($path === null) {
            throw new RuntimeException('FCM service account file not found.');
        }

        /** @var array{client_email?: string, private_key?: string, token_uri?: string} $sa */
        $sa = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if (empty($sa['client_email']) || empty($sa['private_key'])) {
            throw new RuntimeException('Invalid FCM service account JSON.');
        }

        $now = time();
        $claim = [
            'iss' => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $sa['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $jwt = $this->encodeJwt($claim, $sa['private_key']);

        $response = Http::asForm()->post($claim['aud'], [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful() || empty($response->json('access_token'))) {
            throw new RuntimeException('Failed to obtain FCM access token: '.$response->body());
        }

        return (string) $response->json('access_token');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encodeJwt(array $payload, string $privateKey): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
        $signingInput = implode('.', $segments);

        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new RuntimeException('Unable to parse FCM private key.');
        }

        $signature = '';
        if (! openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign FCM JWT.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
