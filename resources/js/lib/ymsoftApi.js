/**
 * Browser-side ERP API helpers (parity with frontend/src/lib/ymsoft-api.ts).
 * baseApiUrl: same-origin prefix e.g. /proxy/ymsoft-api (Laravel → ERP), not the raw ERP host.
 */

function csrfToken() {
    if (typeof document === 'undefined') return '';
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function fetchDefaults() {
    return {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    };
}

export async function fetchReservationAvailabilityLayout(baseApiUrl, params) {
    try {
        const query = new URLSearchParams({
            outlet_id: String(params.outletId),
            reservation_date: params.reservationDate,
            reservation_time: params.reservationTime,
            number_of_guests: String(params.numberOfGuests),
        });
        if (params.smokingPreference) {
            query.set('smoking_preference', params.smokingPreference);
        }
        if (params.reservationDurationMinutes) {
            query.set('reservation_duration_minutes', String(params.reservationDurationMinutes));
        }

        const base = String(baseApiUrl || '').replace(/\/$/, '');
        const response = await fetch(`${base}/reservations/availability-layout?${query.toString()}`, {
            ...fetchDefaults(),
            cache: 'no-store',
        });
        if (!response.ok) {
            try {
                const errJson = await response.json();
                let firstField;
                if (errJson.errors) {
                    const keys = Object.keys(errJson.errors);
                    if (keys.length > 0) {
                        firstField = errJson.errors[keys[0]]?.[0];
                    }
                }
                return {
                    ok: false,
                    message:
                        firstField ||
                        errJson.message ||
                        `Availability check failed (HTTP ${response.status}).`,
                };
            } catch {
                return {
                    ok: false,
                    message: `Availability check failed (HTTP ${response.status}).`,
                };
            }
        }
        const json = await response.json();
        if (typeof json !== 'object' || json === null || !('data' in json) || typeof json.data !== 'object' || json.data === null) {
            return { ok: false, message: 'Invalid response from availability API.' };
        }
        return { ok: true, data: json.data };
    } catch {
        return { ok: false, message: 'Failed to connect to availability API.' };
    }
}

export async function fetchReservationStatusByNumber(baseApiUrl, reservationNumber) {
    try {
        const query = new URLSearchParams({
            reservation_number: reservationNumber.trim(),
        });
        const base = String(baseApiUrl || '').replace(/\/$/, '');
        const response = await fetch(`${base}/reservations/status-by-number?${query.toString()}`, {
            ...fetchDefaults(),
            cache: 'no-store',
        });
        const raw = await response.text();
        let json = {};
        try {
            json = JSON.parse(raw);
        } catch {
            json = {};
        }

        if (!response.ok) {
            return {
                ok: false,
                message:
                    (typeof json.message === 'string' && json.message) ||
                    `Status check failed (HTTP ${response.status}).`,
            };
        }

        return {
            ok: true,
            data: {
                reservation_number: String(json.reservation_number ?? ''),
                name: String(json.name ?? ''),
                phone: json.phone ?? null,
                outlet: json.outlet ?? null,
                reservation_date: json.reservation_date ?? null,
                reservation_time: json.reservation_time ?? null,
                number_of_guests: typeof json.number_of_guests === 'number' ? json.number_of_guests : null,
                smoking_preference: json.smoking_preference ?? null,
                status: String(json.status ?? ''),
                created_at: json.created_at ?? null,
            },
        };
    } catch {
        return { ok: false, message: 'Failed to connect to reservation API.' };
    }
}

export async function fetchSelfOrderMenu(baseApiUrl, outletId) {
    try {
        const base = String(baseApiUrl || '').replace(/\/$/, '');
        const response = await fetch(
            `${base}/self-order/menu?outlet_id=${encodeURIComponent(String(outletId))}`,
            { ...fetchDefaults(), cache: 'no-store' },
        );
        const raw = await response.text();
        let json = null;
        try {
            json = JSON.parse(raw);
        } catch {
            json = null;
        }

        if (!response.ok) {
            return {
                ok: false,
                message:
                    (json?.message && String(json.message)) ||
                    `Self order API error (HTTP ${response.status}).`,
            };
        }

        if (!json?.success || !json.data) {
            return {
                ok: false,
                message: json?.message || 'Failed to load self order menu.',
            };
        }
        return { ok: true, data: json.data };
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Unknown error';
        return { ok: false, message: `Failed to connect self order API. ${message}` };
    }
}

/** POST /reservations (needs CSRF on Laravel web routes). */
export async function createReservation(baseApiUrl, payload) {
    const base = String(baseApiUrl || '').replace(/\/$/, '');
    return fetch(`${base}/reservations`, {
        method: 'POST',
        ...fetchDefaults(),
        headers: {
            ...fetchDefaults().headers,
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(payload),
    });
}

export async function checkoutSelfOrder(baseApiUrl, payload) {
    try {
        const base = String(baseApiUrl || '').replace(/\/$/, '');
        const response = await fetch(`${base}/self-order/checkout`, {
            method: 'POST',
            ...fetchDefaults(),
            headers: {
                ...fetchDefaults().headers,
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(payload),
        });
        const json = await response.json();
        if (!response.ok || !json.success || !json.data) {
            return { ok: false, message: json.message || 'Failed to checkout self order.' };
        }
        return { ok: true, data: json.data };
    } catch {
        return { ok: false, message: 'Failed to checkout self order.' };
    }
}
