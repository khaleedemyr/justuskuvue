<?php

namespace App\Support;

use App\Services\YmsoftErpClient;

final class SiteMediaUrl
{
    public static function resize(string $remoteUrl, int $width): string
    {
        $remoteUrl = trim($remoteUrl);
        if ($remoteUrl === '' || ! self::isAllowedRemoteUrl($remoteUrl)) {
            return $remoteUrl;
        }

        $width = max(320, min(2400, $width));
        $encoded = rtrim(strtr(base64_encode($remoteUrl), '+/', '-_'), '=');

        return url('/m/'.$width.'/'.$encoded);
    }

    public static function isAllowedRemoteUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $allowedHosts = array_filter(array_unique([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            parse_url(app(YmsoftErpClient::class)->webBaseUrl(), PHP_URL_HOST),
        ]));

        return in_array($host, $allowedHosts, true);
    }
}
