<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private string $cacheKey = 'firebase_access_token';
    private string $credentialsPath;

    public function __construct()
    {
        $this->credentialsPath = storage_path(config('services.firebase.credentials_path'));

        if (!file_exists($this->credentialsPath)) {
            Log::critical('Firebase service account file not found', [
                'path' => $this->credentialsPath,
            ]);
            throw new \RuntimeException('Firebase credentials file not found');
        }
    }

    /**
     * Get a valid Firebase Access Token.
     * Tokens are cached for 50 minutes (lifetime is 1 hour).
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getAccessToken(): string
    {
        // 1. Try to get from secure cache
        if (Cache::has($this->cacheKey)) {
            return Cache::get($this->cacheKey);
        }

        try {
            // 2. Create Google Client with secure credentials
            $client = new Client();
            $client->setAuthConfig($this->credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            // 3. Generate new Access Token
            $tokenData = $client->fetchAccessTokenWithAssertion();

            if (isset($tokenData['access_token'])) {
                $accessToken = $tokenData['access_token'];

                // 4. Save in secure cache for 50 minutes
                Cache::put($this->cacheKey, $accessToken, now()->addMinutes(50));

                Log::info('Firebase access token generated successfully');
                return $accessToken;
            }

            throw new \Exception('No access token in response');

        } catch (\Exception $e) {
            Log::error('Failed to get Firebase access token', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Unable to get Firebase access token: ' . $e->getMessage());
        }
    }
}