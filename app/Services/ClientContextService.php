<?php

namespace App\Services;

use Illuminate\Http\Request;

class ClientContextService
{
    public function fromRequest(Request $request): array
    {
        $userAgent = (string) $request->userAgent();
        $platform = $this->detectPlatform($userAgent);
        $deviceType = $this->detectDeviceType($userAgent);
        $browser = $this->detectBrowser($userAgent);

        return [
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'device_name' => $platform === 'Unknown' ? $deviceType : $platform,
            'browser_name' => $browser['name'],
            'browser_version' => $browser['version'],
        ];
    }

    private function detectDeviceType(string $userAgent): string
    {
        if (preg_match('/ipad|tablet/i', $userAgent) === 1) {
            return 'Tablet';
        }

        if (preg_match('/mobile|android|iphone|ipod/i', $userAgent) === 1) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    private function detectPlatform(string $userAgent): string
    {
        return match (true) {
            preg_match('/windows/i', $userAgent) === 1 => 'Windows',
            preg_match('/macintosh|mac os x/i', $userAgent) === 1 => 'macOS',
            preg_match('/iphone/i', $userAgent) === 1 => 'iPhone',
            preg_match('/ipad/i', $userAgent) === 1 => 'iPad',
            preg_match('/android/i', $userAgent) === 1 => 'Android',
            preg_match('/linux/i', $userAgent) === 1 => 'Linux',
            default => 'Unknown',
        };
    }

    private function detectBrowser(string $userAgent): array
    {
        $browsers = [
            'Edg' => 'Edge',
            'OPR' => 'Opera',
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
            'MSIE' => 'Internet Explorer',
            'Trident' => 'Internet Explorer',
        ];

        foreach ($browsers as $token => $name) {
            if (preg_match('/' . preg_quote($token, '/') . '[\/ ]([\d.]+)/i', $userAgent, $matches) === 1) {
                return [
                    'name' => $name,
                    'version' => $matches[1] ?? null,
                ];
            }
        }

        return [
            'name' => 'Unknown',
            'version' => null,
        ];
    }
}