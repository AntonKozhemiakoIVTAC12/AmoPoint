<?php

namespace App\Support;

/**
 * Минималистичный парсер User-Agent.
 * Намеренно не тянем `jenssegers/agent` или `whichbrowser/parser`,
 * чтобы не плодить зависимости ради трёх полей.
 */
class UserAgentParser
{
    /**
     * @return array{device: string, browser: string, os: string}
     */
    public static function parse(?string $ua): array
    {
        $ua = (string) $ua;

        return [
            'device' => self::detectDevice($ua),
            'browser' => self::detectBrowser($ua),
            'os' => self::detectOs($ua),
        ];
    }

    private static function detectDevice(string $ua): string
    {
        if ($ua === '') {
            return 'unknown';
        }
        if (preg_match('/iPad|Tablet|Kindle|Silk/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/Mobile|Android|iPhone|iPod|Opera Mini|IEMobile/i', $ua)) {
            return 'mobile';
        }
        if (preg_match('/bot|spider|crawler|slurp|preview/i', $ua)) {
            return 'bot';
        }
        return 'desktop';
    }

    private static function detectBrowser(string $ua): string
    {
        $patterns = [
            'Edge' => '/Edg\//i',
            'Opera' => '/OPR\/|Opera/i',
            'Chrome' => '/Chrome\//i',
            'Firefox' => '/Firefox\//i',
            'Safari' => '/Safari\//i',
            'IE' => '/MSIE |Trident\//i',
        ];
        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $ua)) {
                return $name;
            }
        }
        return 'other';
    }

    private static function detectOs(string $ua): string
    {
        $patterns = [
            'iOS' => '/iPhone|iPad|iPod/i',
            'Android' => '/Android/i',
            'Windows' => '/Windows NT/i',
            'macOS' => '/Mac OS X|Macintosh/i',
            'Linux' => '/Linux/i',
        ];
        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $ua)) {
                return $name;
            }
        }
        return 'other';
    }
}
