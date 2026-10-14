<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

class ReservationSiteSettings
{
    private const CACHE_KEY = 'justuskunest:reservation_public_settings';

    private const CACHE_TTL_SECONDS = 30;

    public function __construct(private readonly YmsoftErpClient $erp)
    {
    }

    /**
     * @return array{guest_table_selection_enabled: bool, reservation_maintenance_enabled: bool}
     */
    public function all(bool $fresh = false): array
    {
        $fallback = [
            'guest_table_selection_enabled' => true,
            'reservation_maintenance_enabled' => (bool) config(
                'services.ymsofterp.reservation_maintenance_enabled',
                true
            ),
        ];

        try {
            if ($fresh) {
                Cache::forget(self::CACHE_KEY);
            }

            $payload = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
                return $this->erp->getFresh('reservations/public-settings');
            });

            if (! is_array($payload) || $payload === []) {
                return $fallback;
            }

            return [
                'guest_table_selection_enabled' => array_key_exists('guest_table_selection_enabled', $payload)
                    ? filter_var($payload['guest_table_selection_enabled'], FILTER_VALIDATE_BOOLEAN)
                    : $fallback['guest_table_selection_enabled'],
                'reservation_maintenance_enabled' => array_key_exists('reservation_maintenance_enabled', $payload)
                    ? filter_var($payload['reservation_maintenance_enabled'], FILTER_VALIDATE_BOOLEAN)
                    : $fallback['reservation_maintenance_enabled'],
            ];
        } catch (Throwable) {
            return $fallback;
        }
    }

    public function isMaintenanceEnabled(bool $fresh = false): bool
    {
        return (bool) ($this->all($fresh)['reservation_maintenance_enabled'] ?? true);
    }

    public function isGuestTableSelectionEnabled(bool $fresh = false): bool
    {
        return (bool) ($this->all($fresh)['guest_table_selection_enabled'] ?? true);
    }
}
