<?php

namespace App\Http\Controllers;

use App\Services\YmsoftErpClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
        $validated = $request->validate([
            'reservation_number' => 'required|string|max:80',
            'recaptcha_token' => 'required|string|max:4096',
            'form_started_at' => 'required|integer',
            'company_website' => 'nullable|string|max:255',
        ]);
        if (trim((string) ($validated['company_website'] ?? '')) !== '') {
            Log::warning('Reservation status honeypot triggered', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Permintaan tidak valid.'], 422);
        }
        if ((time() - (int) $validated['form_started_at']) < 2) {
            Log::warning('Reservation status too-fast submit', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Permintaan terlalu cepat.'], 422);
        }

        $ipKey = 'reservation-status:ip:'.$request->ip();
        $numberKey = 'reservation-status:number:'.strtolower(trim((string) $validated['reservation_number']));
        if (RateLimiter::tooManyAttempts($ipKey, 30) || RateLimiter::tooManyAttempts($numberKey, 12)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan cek status. Coba lagi beberapa menit.',
            ], 429);
        }

        $captchaStatus = $this->verifyRecaptcha((string) $validated['recaptcha_token'], (string) $request->ip(), 'reservation_status_lookup');
        Log::info('Reservation status captcha evaluated', [
            'ip' => $request->ip(),
            'result' => $captchaStatus,
            'reservation_hash' => sha1(strtolower(trim((string) $validated['reservation_number']))),
        ]);
        if (! $captchaStatus['ok']) {
            return response()->json([
                'message' => 'Verifikasi keamanan gagal. Silakan coba lagi.',
            ], 422);
        }
        if (($captchaStatus['risk_level'] ?? 'low') !== 'low') {
            Log::warning('Reservation status medium-risk captcha pass', [
                'ip' => $request->ip(),
                'result' => $captchaStatus,
                'reservation_hash' => sha1(strtolower(trim((string) $validated['reservation_number']))),
            ]);
        }

        RateLimiter::hit($ipKey, 300);
        RateLimiter::hit($numberKey, 300);

        $query = [
            'reservation_number' => (string) $validated['reservation_number'],
        ];
        return $this->forwardJsonGet('reservations/status-by-number', $request, $query);
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
            'form_started_at' => 'required|integer',
            'company_website' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data reservasi tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        if (trim((string) ($validated['company_website'] ?? '')) !== '') {
            Log::warning('Reservation honeypot triggered', ['ip' => $request->ip()]);
            return response()->json([
                'success' => false,
                'message' => 'Permintaan tidak valid.',
            ], 422);
        }
        if ((time() - (int) $validated['form_started_at']) < 3) {
            Log::warning('Reservation too-fast submit', ['ip' => $request->ip()]);
            return response()->json([
                'success' => false,
                'message' => 'Permintaan terlalu cepat.',
            ], 422);
        }

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

        $captchaStatus = $this->verifyRecaptcha((string) $validated['recaptcha_token'], (string) $request->ip(), 'reservation_submit');
        Log::info('Reservation captcha evaluated', [
            'ip' => $request->ip(),
            'result' => $captchaStatus,
            'contact_hash' => sha1(strtolower(trim((string) ($validated['email'] ?? $validated['phone'])))),
        ]);
        if (! $captchaStatus['ok']) {
            return response()->json([
                'success' => false,
                'message' => 'Verifikasi keamanan gagal. Silakan coba lagi.',
            ], 422);
        }
        if (($captchaStatus['risk_level'] ?? 'low') !== 'low') {
            Log::warning('Reservation medium-risk captcha pass', [
                'ip' => $request->ip(),
                'result' => $captchaStatus,
                'contact_hash' => sha1(strtolower(trim((string) ($validated['email'] ?? $validated['phone'])))),
            ]);
        }

        RateLimiter::hit($ipKey, 300);
        RateLimiter::hit($contactKey, 300);

        unset($payload['recaptcha_token']);
        unset($payload['company_website'], $payload['form_started_at']);
        return $this->forwardJsonPost('reservations', $request, $payload);
    }

    public function checkoutSelfOrder(Request $request): Response
    {
        return $this->forwardJsonPost('self-order/checkout', $request);
    }

    public function storeSelfOrderStaging(Request $request): Response
    {
        $payload = $request->json()->all() ?: $request->all();

        $validator = validator($payload, [
            'reservation_id' => 'nullable|integer|min:1',
            'reservation_number' => 'nullable|string|max:100',
            'order_no' => 'nullable|string|max:100',
            'outlet_id' => 'required|integer|min:1',
            'outlet_code' => 'nullable|string|max:50',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:190',
            'order_channel' => 'nullable|string|max:50',
            'order_type' => 'nullable|string|max:50',
            'pax' => 'nullable|integer|min:1|max:200',
            'table_ids' => 'nullable|array',
            'table_ids.*' => 'integer|min:1',
            'notes' => 'nullable|string',
            'subtotal' => 'required|integer|min:0',
            'discount' => 'nullable|integer|min:0',
            'cashback' => 'nullable|integer|min:0',
            'dpp' => 'required|integer|min:0',
            'pb1' => 'required|integer|min:0',
            'service' => 'required|integer|min:0',
            'grand_total' => 'required|integer|min:0',
            'commfee' => 'nullable|numeric|min:0',
            'rounding' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.qty' => 'required|integer|min:1|max:9999',
            'items.*.price' => 'required|integer|min:0',
            'items.*.subtotal' => 'required|integer|min:0',
            'items.*.tally' => 'nullable|string|max:100',
            'items.*.modifiers' => 'nullable',
            'items.*.notes' => 'nullable|string',
            'items.*.b1g1_promo_id' => 'nullable|integer|min:1',
            'items.*.b1g1_status' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Payload self order staging tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $orderId = (string) Str::ulid();
        $orderNo = trim((string) ($validated['order_no'] ?? ''));
        if ($orderNo === '') {
            $orderNo = 'WSO-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
        }

        $tableIds = $validated['table_ids'] ?? [];
        $now = now();

        try {
            DB::transaction(function () use ($validated, $orderId, $orderNo, $tableIds, $now): void {
                DB::table('web_self_orders')->insert([
                    'id' => $orderId,
                    'reservation_id' => $validated['reservation_id'] ?? null,
                    'reservation_number' => $validated['reservation_number'] ?? null,
                    'order_no' => $orderNo,
                    'outlet_id' => $validated['outlet_id'],
                    'outlet_code' => $validated['outlet_code'] ?? null,
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'customer_email' => $validated['customer_email'] ?? null,
                    'order_channel' => $validated['order_channel'] ?? 'self_order_web',
                    'order_type' => $validated['order_type'] ?? 'dine_in',
                    'pax' => $validated['pax'] ?? null,
                    'table_ids_json' => json_encode(array_values($tableIds)),
                    'notes' => $validated['notes'] ?? null,
                    'subtotal' => $validated['subtotal'],
                    'discount' => $validated['discount'] ?? 0,
                    'cashback' => $validated['cashback'] ?? 0,
                    'dpp' => $validated['dpp'],
                    'pb1' => $validated['pb1'],
                    'service' => $validated['service'],
                    'grand_total' => $validated['grand_total'],
                    'commfee' => $validated['commfee'] ?? 0,
                    'rounding' => $validated['rounding'] ?? 0,
                    'status' => 'pending_sync',
                    'paid_status' => 'unpaid',
                    'sync_attempt_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($validated['items'] as $idx => $item) {
                    DB::table('web_self_order_items')->insert([
                        'id' => (string) Str::ulid(),
                        'web_self_order_id' => $orderId,
                        'item_id' => (string) $item['item_id'],
                        'item_name' => $item['item_name'],
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'subtotal' => $item['subtotal'],
                        'tally' => $item['tally'] ?? null,
                        'modifiers' => array_key_exists('modifiers', $item) ? json_encode($item['modifiers']) : null,
                        'notes' => $item['notes'] ?? null,
                        'b1g1_promo_id' => $item['b1g1_promo_id'] ?? null,
                        'b1g1_status' => $item['b1g1_status'] ?? null,
                        'created_at' => $now,
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Self order berhasil disimpan ke staging web.',
                'data' => [
                    'id' => $orderId,
                    'order_no' => $orderNo,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to store self order staging', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan self order ke staging web.',
            ], 500);
        }
    }

    private function forwardJsonGet(string $path, Request $request, ?array $overrideQuery = null): Response
    {
        $url = $this->erp->apiBaseUrl().'/'.ltrim($path, '/');
        try {
            $query = $overrideQuery ?? $request->query();
            $response = Http::timeout(30)
                ->acceptJson()
                ->get($url, $query);
        } catch (Throwable) {
            return response()->json([
                'message' => 'ERP tidak dapat dijangkau dari server. Periksa YMSOFTERP_API_URL dan koneksi jaringan.',
            ], 502);
        }

        return response($response->body(), $response->status())
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Pragma', 'no-cache');
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
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    private function verifyRecaptcha(string $token, string $ip, string $expectedAction): array
    {
        $secret = (string) config('services.recaptcha.secret_key', '');
        $hardBlockScore = (float) config('services.recaptcha.hard_block_score', 0.5);
        $mediumRiskScore = (float) config('services.recaptcha.medium_risk_score', 0.7);
        if ($mediumRiskScore < $hardBlockScore) {
            $mediumRiskScore = $hardBlockScore;
        }
        if ($secret === '' || $token === '') {
            return ['ok' => false, 'reason' => 'missing_secret_or_token'];
        }

        try {
            $res = Http::asForm()->timeout(10)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);
            $json = $res->json();
            if (! (bool) ($json['success'] ?? false)) {
                return ['ok' => false, 'reason' => 'google_unsuccess', 'error_codes' => $json['error-codes'] ?? []];
            }
            $action = (string) ($json['action'] ?? '');
            $score = (float) ($json['score'] ?? 0);
            if ($action !== '' && $action !== $expectedAction) {
                return ['ok' => false, 'reason' => 'action_mismatch', 'action' => $action, 'score' => $score];
            }
            if ($score < $hardBlockScore) {
                return ['ok' => false, 'reason' => 'low_score', 'action' => $action, 'score' => $score];
            }
            if ($score < $mediumRiskScore) {
                return ['ok' => true, 'reason' => 'passed_medium_risk', 'action' => $action, 'score' => $score, 'risk_level' => 'medium'];
            }
            return ['ok' => true, 'reason' => 'passed', 'action' => $action, 'score' => $score, 'risk_level' => 'low'];
        } catch (Throwable) {
            return ['ok' => false, 'reason' => 'verify_exception'];
        }
    }
}
