<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class YmsoftErpClient
{
    public function apiBaseUrl(): string
    {
        return rtrim((string) config('services.ymsofterp.api_base_url', 'http://127.0.0.1:8000/api'), '/');
    }

    public function webBaseUrl(): string
    {
        return preg_replace('#/api$#', '', $this->apiBaseUrl()) ?: $this->apiBaseUrl();
    }

    public function get(string $path, array $query = []): array
    {
        if (! $this->cacheEnabled()) {
            return $this->fetchApiGet($path, $query) ?? [];
        }

        $cacheKey = $this->cacheKey('api', $path, $query);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->fetchApiGet($path, $query);
        if ($result !== null) {
            Cache::put($cacheKey, $result, $this->cacheTtl($path));
        }

        return $result ?? [];
    }

    /**
     * Always fetch fresh (no cache). Use for frequently changing CMS data.
     */
    public function getFresh(string $path, array $query = []): array
    {
        return $this->fetchApiGet($path, $query) ?? [];
    }

    public function getFromWeb(string $path): array
    {
        if (! $this->cacheEnabled()) {
            return $this->fetchWebGet($path) ?? [];
        }

        $cacheKey = $this->cacheKey('web', $path);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->fetchWebGet($path);
        if ($result !== null) {
            Cache::put($cacheKey, $result, $this->cacheTtl($path));
        }

        return $result ?? [];
    }

    /**
     * @return array<string, mixed>|null Null when the HTTP request fails (not cached).
     */
    private function fetchApiGet(string $path, array $query = []): ?array
    {
        try {
            $url = $this->apiBaseUrl().'/'.ltrim($path, '/');
            if ($query !== []) {
                $url .= '?'.http_build_query($query);
            }
            $response = Http::timeout(20)
                ->acceptJson()
                ->get($url);
            if (! $response->ok()) {
                return null;
            }

            $json = $response->json();

            return is_array($json) ? $json : [];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null Null when the HTTP request fails (not cached).
     */
    private function fetchWebGet(string $path): ?array
    {
        try {
            $url = $this->webBaseUrl().'/'.ltrim($path, '/');
            $response = Http::timeout(20)
                ->acceptJson()
                ->get($url);
            if (! $response->ok()) {
                return null;
            }

            $json = $response->json();

            return is_array($json) ? $json : [];
        } catch (Throwable) {
            return null;
        }
    }

    private function cacheEnabled(): bool
    {
        return (bool) config('services.ymsofterp.cache_enabled', false);
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    private function cacheKey(string $channel, string $path, array $query = []): string
    {
        $baseUrl = $channel === 'web' ? $this->webBaseUrl() : $this->apiBaseUrl();
        $normalizedPath = ltrim($path, '/');
        $queryString = $query !== [] ? '?'.http_build_query($query) : '';

        return 'ymsoft_erp:'.$channel.':'.md5($baseUrl.'/'.$normalizedPath.$queryString);
    }

    private function cacheTtl(string $path): int
    {
        $normalizedPath = ltrim($path, '/');
        $shortPaths = config('services.ymsofterp.cache_short_paths', []);

        foreach ($shortPaths as $shortPath) {
            if (str_starts_with($normalizedPath, ltrim((string) $shortPath, '/'))) {
                return max(1, (int) config('services.ymsofterp.cache_ttl_short_seconds', 300));
            }
        }

        return max(1, (int) config('services.ymsofterp.cache_ttl_seconds', 600));
    }

    /**
     * Forward job application to ymsofterp public endpoint (multipart).
     *
     * @return array{success: bool, message?: string, raw_status?: int}
     */
    public function postJobVacancyApply(
        int $jobId,
        array $fields,
        \Illuminate\Http\UploadedFile $cvFile,
        \Illuminate\Http\UploadedFile $photoFile,
    ): array {
        $url = $this->webBaseUrl().'/api/job-vacancies/'.$jobId.'/apply';
        try {
            $cvPath = method_exists($cvFile, 'path') ? $cvFile->path() : $cvFile->getRealPath();
            $cvContents = @file_get_contents((string) $cvPath);
            if ($cvContents === false) {
                return [
                    'success' => false,
                    'message' => 'Gagal membaca file CV.',
                ];
            }

            $photoPath = method_exists($photoFile, 'path') ? $photoFile->path() : $photoFile->getRealPath();
            $photoContents = @file_get_contents((string) $photoPath);
            if ($photoContents === false) {
                return [
                    'success' => false,
                    'message' => 'Gagal membaca file foto.',
                ];
            }

            $response = Http::timeout(120)
                ->attach(
                    'cv_file',
                    $cvContents,
                    $cvFile->getClientOriginalName(),
                )
                ->attach(
                    'photo_file',
                    $photoContents,
                    $photoFile->getClientOriginalName(),
                )
                ->post($url, [
                    'full_name' => $fields['full_name'],
                    'email' => $fields['email'],
                    'phone' => $fields['phone'],
                    'domicile' => $fields['domicile'],
                    'last_education' => $fields['last_education'],
                    'birth_date' => $fields['birth_date'],
                    'cover_letter' => $fields['cover_letter'] ?? '',
                ]);

            $json = $response->json();
            if (is_array($json) && ($json['success'] ?? false) === true) {
                return [
                    'success' => true,
                    'message' => (string) ($json['message'] ?? 'Lamaran berhasil dikirim.'),
                    'raw_status' => $response->status(),
                ];
            }

            $message = is_array($json) && isset($json['message'])
                ? (string) $json['message']
                : ($response->body() !== '' ? $response->body() : 'Gagal kirim lamaran.');

            return [
                'success' => false,
                'message' => $message,
                'raw_status' => $response->status(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke server.',
            ];
        }
    }
}
