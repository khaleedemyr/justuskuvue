<?php

namespace App\Http\Middleware;

use App\Services\ReservationSiteSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockReservationWhenMaintenance
{
    public function __construct(private readonly ReservationSiteSettings $reservationSettings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->reservationSettings->isMaintenanceEnabled()) {
            return $next($request);
        }

        $message = (string) config(
            'services.ymsofterp.reservation_maintenance_api_message',
            'Our online reservation service is temporarily unavailable. Please try again later.'
        );

        return response()->json(['message' => $message], 503);
    }
}
