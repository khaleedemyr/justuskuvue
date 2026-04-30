<?php

namespace App\Http\Middleware;

use App\Services\YmsoftErpClient;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $erp = app(YmsoftErpClient::class);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            /**
             * Same-origin proxy (routes under /proxy/ymsoft-api). Browser → Laravel → ERP.
             * Direct ERP URL from the browser often fails (CORS / wrong host from client).
             */
            'ymsoftErpApiBaseUrl' => '/proxy/ymsoft-api',
            /** Web origin for /storage/... images (not the proxy prefix). */
            'ymsoftErpWebBaseUrl' => $erp->webBaseUrl(),
            'reservationCallCenterWa' => (string) config('services.ymsofterp.reservation_call_center_wa', ''),
        ];
    }
}
