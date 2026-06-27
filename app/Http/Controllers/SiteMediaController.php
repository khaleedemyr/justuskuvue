<?php

namespace App\Http\Controllers;

use App\Support\SiteMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class SiteMediaController extends Controller
{
    public function show(Request $request, int $width, string $encoded): Response
    {
        $width = max(320, min(2400, $width));
        $remoteUrl = base64_decode(strtr($encoded, '-_', '+/'), true);
        if (! is_string($remoteUrl) || $remoteUrl === '' || ! SiteMediaUrl::isAllowedRemoteUrl($remoteUrl)) {
            abort(404);
        }

        $cacheKey = 'site_media:'.md5($width.'|'.$remoteUrl);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $this->webpResponse($cached);
        }

        $response = Http::timeout(25)->withHeaders([
            'Accept' => 'image/*',
        ])->get($remoteUrl);

        if (! $response->ok()) {
            abort(404);
        }

        $binary = $response->body();
        if ($binary === '') {
            abort(404);
        }

        $webp = $this->toWebp($binary, $width);
        if ($webp === null) {
            return response($binary, 200, [
                'Content-Type' => (string) ($response->header('Content-Type') ?: 'image/jpeg'),
                'Cache-Control' => 'public, max-age=86400, immutable',
            ]);
        }

        Cache::put($cacheKey, $webp, now()->addDay());

        return $this->webpResponse($webp);
    }

    private function webpResponse(string $webp): Response
    {
        return response($webp, 200, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=604800, immutable',
            'Vary' => 'Accept',
        ]);
    }

    private function toWebp(string $binary, int $targetWidth): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return null;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return null;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW <= 0 || $srcH <= 0) {
            imagedestroy($source);

            return null;
        }

        if ($srcW <= $targetWidth) {
            ob_start();
            imagewebp($source, null, $this->webpQuality($targetWidth));
            $webp = ob_get_clean() ?: null;
            imagedestroy($source);

            return $webp;
        }

        $targetHeight = (int) round($srcH * ($targetWidth / $srcW));
        $dest = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($dest === false) {
            imagedestroy($source);

            return null;
        }

        imagecopyresampled($dest, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcW, $srcH);
        imagedestroy($source);

        ob_start();
        imagewebp($dest, null, $this->webpQuality($targetWidth));
        $webp = ob_get_clean() ?: null;
        imagedestroy($dest);

        return $webp;
    }

    private function webpQuality(int $targetWidth): int
    {
        return $targetWidth <= 768 ? 68 : 80;
    }
}
