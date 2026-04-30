<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';
import { fetchReservationStatusByNumber } from '@/lib/ymsoftApi';

defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
});

const page = usePage();
const { t, lang } = useSiteI18n();

const reservationNumber = ref('');
const loading = ref(false);
const error = ref(null);
const result = ref(null);

const baseUrl = computed(() => String(page.props.ymsoftErpApiBaseUrl || '').replace(/\/$/, ''));

const statusLabel = computed(() => {
    const status = (result.value?.status || '').toLowerCase();
    if (status === 'confirmed') return lang.value === 'id' ? 'Dikonfirmasi' : 'Confirmed';
    if (status === 'pending') return lang.value === 'id' ? 'Menunggu' : 'Pending';
    if (status === 'cancelled') return lang.value === 'id' ? 'Dibatalkan' : 'Cancelled';
    return result.value?.status || '-';
});

const statusClass = computed(() => {
    const status = (result.value?.status || '').toLowerCase();
    if (status === 'confirmed') return 'border-emerald-400/35 bg-emerald-500/15 text-emerald-100';
    if (status === 'pending') return 'border-amber-400/35 bg-amber-500/15 text-amber-100';
    if (status === 'cancelled') return 'border-rose-400/35 bg-rose-500/15 text-rose-100';
    return 'border-white/20 bg-white/5 text-white/85';
});

async function handleCheckStatus() {
    error.value = null;
    result.value = null;
    const normalized = reservationNumber.value.trim().toUpperCase();
    if (!normalized) {
        error.value = t('reservationStatusLookupEmpty');
        return;
    }
    loading.value = true;
    try {
        const res = await fetchReservationStatusByNumber(baseUrl.value, normalized);
        if (!res.ok) {
            const msg = (res.message || '').toLowerCase();
            error.value =
                msg.includes('tidak ditemukan') || msg.includes('not found')
                    ? t('reservationStatusLookupNotFound')
                    : res.message || t('reservationStatusLookupFailed');
            return;
        }
        result.value = res.data;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <SiteLayout
        :title="t('reservationStatusLookupTitle')"
        :menus="menus"
        :brand-logos="brandLogos"
        shell-class="min-h-screen bg-[#0b0b0f] text-white"
    >
        <main
            class="relative min-h-[100dvh] overflow-hidden bg-[#0b0b0f] px-4 py-16 text-white md:px-8 md:py-20"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_8%,rgba(251,191,36,0.14),transparent_45%)]"
            />

            <div
                class="relative mx-auto w-full max-w-3xl rounded-[2rem] border border-white/[0.08] bg-[#121215]/90 p-6 shadow-[0_28px_110px_-32px_rgba(0,0,0,0.85)] md:p-8"
            >
                <p class="text-center text-xs font-semibold uppercase tracking-[0.3em] text-white/55">
                    JUSTUS GROUP
                </p>
                <h1
                    class="font-montserrat mt-3 text-center text-2xl font-light tracking-wide text-white md:text-4xl"
                >
                    {{ t('reservationStatusLookupTitle') }}
                </h1>
                <p class="mx-auto mt-3 max-w-xl text-center text-sm text-white/65 md:text-base">
                    {{ t('reservationStatusLookupHint') }}
                </p>

                <div class="mt-7 flex flex-col gap-3 md:flex-row">
                    <input
                        :value="reservationNumber"
                        :placeholder="t('reservationStatusLookupPlaceholder')"
                        class="w-full rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-base text-white outline-none placeholder:text-white/35 focus:border-amber-400/45 focus:ring-2 focus:ring-amber-400/20"
                        @input="reservationNumber = $event.target.value.toUpperCase()"
                    />
                    <button
                        type="button"
                        :disabled="loading"
                        class="rounded-2xl bg-gradient-to-r from-amber-400 to-amber-600 px-6 py-3 text-sm font-semibold uppercase tracking-widest text-zinc-900 transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="handleCheckStatus"
                    >
                        {{ loading ? t('reservationWorkingShort') : t('reservationStatusLookupButton') }}
                    </button>
                </div>

                <p
                    v-if="error"
                    class="mt-5 rounded-xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
                >
                    {{ error }}
                </p>

                <div v-if="result" class="mt-6 rounded-2xl border border-white/10 bg-[#18181d] p-4 md:p-5">
                    <h2 class="font-montserrat text-lg font-medium text-white">
                        {{ t('reservationStatusResultTitle') }}
                    </h2>
                    <div class="mt-3 space-y-2 text-sm">
                        <div
                            class="flex items-center justify-between gap-2 border-b border-white/[0.06] py-2"
                        >
                            <span class="text-white/50">{{ t('reservationStatusNumber') }}</span>
                            <span class="font-semibold text-amber-200">{{ result.reservation_number }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 border-b border-white/[0.06] py-2"
                        >
                            <span class="text-white/50">{{ t('reservationName') }}</span>
                            <span class="font-medium text-white/90">{{ result.name || '-' }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 border-b border-white/[0.06] py-2"
                        >
                            <span class="text-white/50">{{ t('reservationOutlet') }}</span>
                            <span class="font-medium text-white/90">{{ result.outlet || '-' }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 border-b border-white/[0.06] py-2"
                        >
                            <span class="text-white/50">{{ t('reservationDate') }}</span>
                            <span class="font-medium text-white/90">{{ result.reservation_date || '-' }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 border-b border-white/[0.06] py-2"
                        >
                            <span class="text-white/50">{{ t('reservationTime') }}</span>
                            <span class="font-medium text-white/90">{{ result.reservation_time || '-' }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 border-b border-white/[0.06] py-2"
                        >
                            <span class="text-white/50">{{ t('reservationPax') }}</span>
                            <span class="font-medium text-white/90">{{ result.number_of_guests ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 py-2">
                            <span class="text-white/50">{{ t('reservationStatusCurrent') }}</span>
                            <span
                                class="rounded-full border px-3 py-1 text-xs font-semibold uppercase"
                                :class="statusClass"
                            >
                                {{ statusLabel }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-7 text-center">
                    <Link
                        href="/reservation"
                        class="inline-flex rounded-2xl border border-white/15 px-6 py-3 text-sm font-semibold uppercase tracking-widest text-white/85 transition hover:bg-white/[0.06]"
                    >
                        {{ t('reservationBack') }}
                    </Link>
                </div>
            </div>
        </main>
    </SiteLayout>
</template>
