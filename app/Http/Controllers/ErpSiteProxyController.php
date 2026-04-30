<?php

namespace App\Http\Controllers;

use App\Services\YmsoftErpClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
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
        return $this->forwardJsonPost('reservations', $request);
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

    private function forwardJsonPost(string $path, Request $request): Response
    {
        $url = $this->erp->apiBaseUrl().'/'.ltrim($path, '/');
        try {
            $response = Http::timeout(60)
                ->acceptJson()
                ->asJson()
                ->post($url, $request->json()->all() ?: $request->all());
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'ERP tidak dapat dijangkau dari server. Periksa YMSOFTERP_API_URL dan koneksi jaringan.',
            ], 502);
        }

        return response($response->body(), $response->status())
            ->header('Content-Type', 'application/json');
    }
}
