<?php

namespace App\Http\Controllers;

use App\Services\YmsoftErpClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Forwards browser reservation/self-order calls to ymsofterp (same-origin → server → ERP).
 * Avoids CORS and unreachable ERP hosts from the user's browser.
 */
class ErpSiteProxyController extends Controller
{
    public function __construct(private readonly YmsoftErpClient $erp)
    {
    }

    public function availabilityLayout(Request $request): Response
    {
        return $this->forwardJsonGet('reservations/availability-layout', $request);
    }

    public function statusByNumber(Request $request): Response
    {
        return $this->forwardJsonGet('reservations/status-by-number', $request);
    }

    public function selfOrderMenu(Request $request): Response
    {
        return $this->forwardJsonGet('self-order/menu', $request);
    }

    public function storeReservation(Request $request): Response
    {
        $payload = $request->json()->all() ?: $request->all();

        $validator = validator($payload, [
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:40',
            'email' => 'nullable|email|max:190',
            'outlet_id' => 'required|integer|min:1',
            'reservation_date' => 'required|date_format:Y-m-d',
            'reservation_time' => 'required|date_format:H:i',
            'number_of_guests' => 'required|integer|min:1|max:40',
            'selected_table_ids' => 'required|array|min:1',
            'selected_table_ids.*' => 'integer|min:1',
            'recaptcha_token' => 'required|string|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data reservasi tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $ipKey = 'reservation:ip:'.$request->ip();
        $contactKey = 'reservation:contact:'.strtolower(trim((string) ($validated['email'] ?? $validated['phone'])));
        if (RateLimiter::tooManyAttempts($ipKey, 20) || RateLimiter::tooManyAttempts($contactKey, 8)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan reservasi. Coba lagi beberapa menit.',
            ], 429);
        }

        try {
            $reservationAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                $validated['reservation_date'].' '.$validated['reservation_time'],
                config('app.timezone')
            );
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal atau jam reservasi tidak valid.',
            ], 422);
        }

        if ($reservationAt->lt(now()->addMinutes(15)) || $reservationAt->gt(now()->addDays(90))) {
            return response()->json([
                'success' => false,
                'message' => 'Jam reservasi tidak valid. Pilih waktu yang tersedia.',
            ], 422);
        }

        if (! $this->verifyRecaptcha((string) $validated['recaptcha_token'], (string) $request->ip(), 'reservation_submit')) {
            return response()->json([
                'success' => false,
                'message' => 'Verifikasi keamanan gagal. Silakan coba lagi.',
            ], 422);
        }

        RateLimiter::hit($ipKey, 300);
        RateLimiter::hit($contactKey, 300);

        unset($payload['recaptcha_token']);
        return $this->forwardJsonPost('reservations', $request, $payload);
    }

    public function checkoutSelfOrder(Request $request): Response
    {
        return $this->forwardJsonPost('self-order/checkout', $request);
    }

    private function forwardJsonGet(string $path, Request $request): Response
    {
        $url = $this->erp->apiBaseUrl().'/'.ltrim($path, '/');
        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->get($url, $request->query());
        } catch (Throwable) {
            return response()->json([
                'message' => 'ERP tidak dapat dijangkau dari server. Periksa YMSOFTERP_API_URL dan koneksi jaringan.',
            ], 502);
        }

        return response($response->body(), $response->status())
            ->header('Content-Type', 'application/json');
    }

    private function forwardJsonPost(string $path, Request $request, ?array $overridePayload = null): Response
    {
        $url = $this->erp->apiBaseUrl().'/'.ltrim($path, '/');
        try {
            $payload = $overridePayload ?? ($request->json()->all() ?: $request->all());
            $response = Http::timeout(60)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan reservasi sedang mengalami gangguan. Silakan coba lagi.',
            ], 502);
        }

        if ($response->status() >= 500) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi gangguan sistem. Silakan coba lagi beberapa saat.',
            ], 502);
        }

        return response($response->body(), $response->status())
            ->header('Content-Type', 'application/json');
    }

    private function verifyRecaptcha(string $token, string $ip, string $expectedAction): bool
    {
        $secret = (string) config('services.recaptcha.secret_key', '');
        if ($secret === '' || $token === '') {
            return false;
        }

        try {
            $res = Http::asForm()->timeout(10)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);
            $json = $res->json();
            if (! (bool) ($json['success'] ?? false)) {
                return false;
            }
            $action = (string) ($json['action'] ?? '');
            $score = (float) ($json['score'] ?? 0);
            if ($action !== '' && $action !== $expectedAction) {
                return false;
            }
            return $score >= 0.3;
        } catch (Throwable) {
            return false;
        }
    }
}
