<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    heroImageUrl: { type: String, default: null },
});

const page = usePage();
const { t } = useSiteI18n();

const whatsAppHref = computed(() => {
    const digits = String(page.props.reservationCallCenterWa || '').replace(/\D/g, '');
    if (!digits) {
        return null;
    }

    const text = encodeURIComponent(t('reservationMaintenanceWhatsAppPrefill'));
    return `https://wa.me/${digits}?text=${text}`;
});
</script>

<template>
    <SiteLayout
        :title="t('reservationMaintenanceTitle')"
        :menus="menus"
        :brand-logos="brandLogos"
        shell-class="min-h-screen bg-[#1f1f22] text-white"
    >
        <main class="min-h-[100dvh] bg-[#1f1f22] text-white">
            <section
                class="relative flex min-h-[84vh] flex-col items-center justify-center overflow-hidden px-6 pb-14 pt-28 md:min-h-[88vh] md:pt-32"
            >
                <template v-if="heroImageUrl">
                    <img
                        :src="heroImageUrl"
                        alt=""
                        aria-hidden="true"
                        class="absolute inset-0 h-full w-full object-cover object-center opacity-35 blur-sm scale-110"
                    />
                </template>
                <div v-else class="absolute inset-0 bg-gradient-to-b from-zinc-800 to-zinc-950" />
                <div class="absolute inset-0 bg-black/65" />

                <div
                    class="relative z-10 w-full max-w-2xl rounded-[2rem] border border-white/10 bg-[#121215]/90 p-8 text-center shadow-[0_28px_110px_-32px_rgba(0,0,0,0.85)] md:p-10"
                >
                    <p
                        class="font-montserrat text-xs font-medium uppercase tracking-[0.35em] text-amber-300/90"
                    >
                        {{ t('reservationMaintenanceBadge') }}
                    </p>
                    <h1
                        class="font-montserrat mt-4 text-3xl font-light uppercase leading-tight tracking-[0.04em] md:text-4xl"
                    >
                        {{ t('reservationMaintenanceTitle') }}
                    </h1>
                    <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-white/80 md:text-lg">
                        {{ t('reservationMaintenanceLead') }}
                    </p>
                    <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-white/60 md:text-base">
                        {{ t('reservationMaintenanceBody') }}
                    </p>
                    <p class="mx-auto mt-6 max-w-xl text-sm italic text-white/55">
                        {{ t('reservationMaintenanceApology') }}
                    </p>

                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a
                            v-if="whatsAppHref"
                            :href="whatsAppHref"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-11 items-center justify-center rounded-full border border-white/20 bg-white px-6 py-2.5 text-sm font-medium uppercase tracking-widest text-[#1f1f22] transition hover:bg-white/90"
                        >
                            {{ t('reservationMaintenanceContact') }}
                        </a>
                        <Link
                            href="/"
                            class="inline-flex min-h-11 items-center justify-center rounded-full border border-white/20 px-6 py-2.5 text-sm font-medium uppercase tracking-widest text-white transition hover:border-white/40 hover:bg-white/5"
                        >
                            {{ t('backToHome') }}
                        </Link>
                    </div>
                </div>
            </section>
        </main>
    </SiteLayout>
</template>
