<?php

namespace App\Services;

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

    public function get(string $path): array
    {
        try {
            $url = $this->apiBaseUrl().'/'.ltrim($path, '/');
            $response = Http::timeout(20)
                ->acceptJson()
                ->get($url);
            if (! $response->ok()) {
                return [];
            }

            $json = $response->json();
            return is_array($json) ? $json : [];
        } catch (Throwable) {
            return [];
        }
    }

    public function getFromWeb(string $path): array
    {
        try {
            $url = $this->webBaseUrl().'/'.ltrim($path, '/');
            $response = Http::timeout(20)
                ->acceptJson()
                ->get($url);
            if (! $response->ok()) {
                return [];
            }

            $json = $response->json();
            return is_array($json) ? $json : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Forward job application to ymsofterp public endpoint (multipart).
     *
     * @return array{success: bool, message?: string, raw_status?: int}
     */
    public function postJobVacancyApply(int $jobId, array $fields, \Illuminate\Http\UploadedFile $cvFile): array
    {
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

            $response = Http::timeout(120)
                ->attach(
                    'cv_file',
                    $cvContents,
                    $cvFile->getClientOriginalName(),
                )
                ->post($url, [
                    'full_name' => $fields['full_name'],
                    'email' => $fields['email'],
                    'phone' => $fields['phone'],
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

