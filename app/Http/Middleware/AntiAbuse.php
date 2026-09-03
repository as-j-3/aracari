<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Mail;
use App\Models\SecurityIncident;

class AntiAbuse
{
    private const MAX_SUSPICIOUS_ATTEMPTS = 50;
    private const BLOCK_DURATION = 3600;
    private const MAX_REQUEST_SIZE = 10 * 1024 * 1024;


    private const SENSITIVE_PATHS = [
        '.env', '.git', 'wp-admin', 'wp-login', 'phpmyadmin',
        'adminer', 'config/', 'backup', 'sql', 'phpinfo',
        'storage/logs', 'vendor/', 'composer.json', 'package.json',
        'xmlrpc.php', 'docker-compose', 'Dockerfile', '.htaccess',
        'nginx.conf', 'license.txt', 'readme.html', /*'.well-known',*/ 
        'server-status','artisan', 'api/debug', 'telescope', 'horizon', 'nova',
        '.vscode','.idea','node_modules', 'CHANGELOG','composer.lock','yarn.lock',
        'package-lock.json',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تجاوز الـ middleware كاملاً في بيئة التست لتجنب تعارض User-Agent
        if (app()->environment('testing') || str_starts_with($request->path(), 'storage/')) {
            return $next($request);
        }

        $clientIp = $request->ip();
        $userAgent = $request->userAgent() ?? '';
        $path = $request->path();

        if ($this->isSensitivePath($path)) {
           $this->blockIpPermanently($clientIp, 'sensitive_path');

            $details = [
                'ip' => $clientIp,
                'path' => $path,
                'method' => $request->method(),
                'user_agent' => $userAgent,
            ];
            $this->sendCriticalAlert("محاولة الوصول لملف حساس: {$path}", $details);
            Log::alert('Sensitive path scan detected - IP blocked immediately', $details);

            return $this->blockResponse('Access denied', 403);
        }

        if ($this->isBlocked($clientIp)) {
            Log::warning('Temporarily blocked IP attempted access', [
                'ip' => $clientIp,
                'user_agent' => $userAgent,
                'endpoint' => $request->path(),
            ]);
            return $this->blockResponse('تم حظر طلبك مؤقتاً. يرجى المحاولة لاحقاً', 429);
        }

        if (empty($userAgent)) {
            $this->logAndTrack($request, 'missing_user_agent');
            SecurityIncident::create([
                'incident_type' => 'missing_agent',
                'severity' => 'medium',
                'ip_address' => $clientIp,
                'user_id' => $request->user()?->id,
                'request_method' => $request->method(),
                'request_path' => $path,
                'was_blocked' => true,
                'occurred_at' => now(),
        ]);
            return $this->blockResponse('Access denied', 403);
        }

        if ($this->isSuspiciousUserAgent($userAgent)) {
            $details = [
                'ip' => $clientIp,
                'path' => $path,
                'method' => $request->method(),
                'user_agent' => $userAgent,
            ];
            $this->sendCriticalAlert("User Agent مشبوه: {$userAgent}", $details);
            $this->logAndTrack($request, 'suspicious_user_agent');

            SecurityIncident::create([
                'incident_type' => 'suspicious_agent',
                'severity' => 'high',
                'ip_address' => $clientIp,
                'user_id' => $request->user()?->id,
                'user_agent' => $userAgent,
                'request_method' => $request->method(),
                'request_path' => $path,
                'was_blocked' => true,
                'occurred_at' => now(),
            ]);

            return $this->blockResponse('Access denied', 403);
        }

        $contentLength = (int) $request->header('content-length', 0);
        if ($contentLength > self::MAX_REQUEST_SIZE) {
            $this->logAndTrack($request, 'large_request');
            SecurityIncident::create([
                'incident_type' => 'large_payload',
                'severity' => 'low',
                'ip_address' => $clientIp,
                'user_id' => $request->user()?->id,
                'user_agent' => $userAgent,
                'request_method' => $request->method(),
                'request_path' => $path,
                'was_blocked' => true,
                'occurred_at' => now(),
            ]);
            return $this->blockResponse('Request too large', 413);
        }

        return $next($request);
    }


    private function isSensitivePath(string $path): bool
    {
        foreach (self::SENSITIVE_PATHS as $sensitivePath) {
            if (stripos($path, $sensitivePath) !== false) {
                return true;
            }
        }
        return false;
    }


    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            'Go-http-client', 'python-requests', 'python-urllib',
            'curl/', 'wget/', 'nikto', 'sqlmap', 'nmap',
            'gobuster', 'dirbuster', 'burpsuite', 'python/',
            'Java/', 'Ruby/', 'Perl/', 'PHP/',
            'lua-resty-http', 'HTTPie/', 'faraday', 'ZAP',
            'NESSUS', 'OpenVAS', 'masscan', 'hydra', 'medusa',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true; // مشبوه
            }
        }

        $allowedPatterns = [
            'Mozilla', 'PostmanRuntime', 'Dart', 'okhttp',
            'CFNetwork', 'Googlebot', 'Bingbot', 'AhrefsBot',
            'crawler', 'facebook', 'whatsapp',
        ];

        foreach ($allowedPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return false; 
            }
        }

        return true;
    }

   private function isBlocked(string $ip): bool
    {
        if (Cache::has('blocked_ip:' . $ip)) {
            return true;
        }
        return SecurityIncident::where('ip_address', $ip)
            ->where('was_blocked', true)
            ->where('severity', 'critical')
            ->exists();
    }



    private function blockIpPermanently(string $ip, string $reason): void
    {
        SecurityIncident::create([
            'incident_type' => $reason,
            'severity' => 'critical',
            'ip_address' => $ip,
            'user_id' => request()->user()?->id,
            'user_agent' => request()->userAgent(),
            'request_method' => request()->method(),
            'request_path' => request()->path(),
            'was_blocked' => true,
            'occurred_at' => now(),
        ]);

        Cache::put('blocked_ip:' . $ip, true, now()->addMonth());

        Log::alert('IP permanently blocked (DB & 1 Month Cache)', ['ip' => $ip, 'reason' => $reason]);
    }

    private function logAndTrack(Request $request, string $reason): void
    {
        $ip = $request->ip();
        $userId = $request->user()?->id;

        $key = $userId
            ? 'suspicious:user:' . $userId
            : 'suspicious:ip:' . $ip;

        RateLimiter::hit($key, self::BLOCK_DURATION);
        $attempts = RateLimiter::attempts($key);

        Log::warning('Suspicious activity detected', [
            'ip' => $ip,
            'user_id' => $userId,
            'user_agent' => $request->userAgent(),
            'reason' => $reason,
            'endpoint' => $request->method() . ' ' . $request->path(),
            'attempt_count' => $attempts,
        ]);

        if ($attempts >= self::MAX_SUSPICIOUS_ATTEMPTS) {
            SecurityIncident::create([
                'incident_type' => 'excessive_attempts',
                'severity' => 'medium',
                'ip_address' => $ip,
                'user_id' => $userId,
                'user_agent' => $request->userAgent(),
                'request_method' => $request->method(),
                'request_path' => $request->path(),
                'attempt_count' => $attempts,
                'was_blocked' => true,
                'occurred_at' => now(),
            ]);
            if ($userId) {
                Log::alert('User blocked due to suspicious activity', ['user_id' => $userId]);
                Cache::put('blocked_user:' . $userId, true, self::BLOCK_DURATION);
            } else {
                $this->blockIpPermanently($ip, 'excessive_attempts');
            }
            RateLimiter::clear($key);
        }
    }

    private function blockResponse(string $message, int $code): Response
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $code);
        }
        abort($code, $message);
    }


    private function sendCriticalAlert(string $reason, array $details): void
    {
        $alertKey = 'alert_sent:' . md5($reason);

        if (Cache::has($alertKey)) {
            Log::info('Alert already sent within the hour, skipping', ['reason' => $reason]);
            return;
        }

        try {
            $adminEmails = explode(',', env('ADMIN_ALERT_EMAIL', 'your_email@gmail.com'));

            $body = "تقرير أمني - نظام أراكاري\n";
            $body .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $body .= "السبب: {$reason}\n";
            $body .= "IP: {$details['ip']}\n";
            $body .= "المسار: {$details['path']}\n";
            $body .= "الوقت: " . now()->format('Y-m-d H:i:s') . "\n\n";
            $body .= "تم حظر الطلب تلقائياً.\n";
            $body .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $body .= "Aracari Security";

            Mail::raw($body, function ($message) use ($adminEmails) {
                foreach ($adminEmails as $email) {
                    $message->to(trim($email));
                }
                $message->subject('تقرير أمني - أراكاري');
            });

            Cache::put($alertKey, true, 3600);
            Log::info('Security alert email sent', ['reason' => $reason, 'ip' => $details['ip']]);

        } catch (\Exception $e) {
            Log::error('Failed to send alert email', ['error' => $e->getMessage()]);
        }
    }
}