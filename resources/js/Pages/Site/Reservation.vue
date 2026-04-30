<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link } from '@inertiajs/vue3';
import { useSiteI18n } from '@/composables/useSiteI18n';

defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    heroImageUrl: { type: String, default: null },
});

const { t } = useSiteI18n();
</script>

<template>
    <SiteLayout
        :title="t('reservationTitle')"
        :menus="menus"
        :brand-logos="brandLogos"
        shell-class="min-h-screen bg-[#1f1f22] text-white"
    >
        <main class="min-h-[100dvh] bg-[#1f1f22] text-white">
            <section
                class="relative flex min-h-[84vh] flex-col items-center overflow-hidden px-6 pb-14 pt-28 md:min-h-[88vh] md:pt-32"
            >
                <template v-if="heroImageUrl">
                    <img
                        :src="heroImageUrl"
                        alt=""
                        aria-hidden="true"
                        class="absolute inset-0 h-full w-full object-cover object-center opacity-45 blur-sm scale-110 md:hidden"
                    />
                    <img
                        :src="heroImageUrl"
                        alt=""
                        class="absolute inset-0 h-full w-full object-contain object-center md:object-cover"
                    />
                </template>
                <div v-else class="absolute inset-0 bg-gradient-to-b from-zinc-700 to-zinc-900" />
                <div class="absolute inset-0 bg-black/60" />

                <div class="relative z-10 mt-2 text-center">
                    <img
                        src="/logohitam.png"
                        alt="Justus Group"
                        class="mx-auto h-auto w-[190px] sm:w-[240px] md:w-[300px] lg:w-[360px]"
                    />
                    <h1
                        class="font-montserrat mt-5 text-3xl font-light uppercase leading-[1.08] tracking-[0.035em] sm:text-5xl md:text-6xl lg:text-7xl"
                    >
                        {{ t('reservationTitle') }}
                    </h1>
                    <p
                        class="mx-auto mt-3 max-w-4xl text-base font-light italic text-white/95 sm:mt-4 sm:text-xl md:text-2xl"
                    >
                        {{ t('reservationHeroLine1') }}
                        <br />
                        {{ t('reservationHeroLine2') }}
                    </p>
                </div>

                <div class="relative z-10 mt-14 flex w-full max-w-3xl flex-col gap-5">
                    <Link
                        href="/reservation/arrange"
                        class="rounded-xl bg-white/85 px-6 py-3 text-center text-3xl font-light tracking-[0.02em] text-zinc-800 transition hover:bg-white"
                    >
                        {{ t('arrangeDiningReservation') }}
                    </Link>
                    <Link
                        href="/reservation/status"
                        class="rounded-xl bg-white/85 px-6 py-3 text-center text-3xl font-light tracking-[0.02em] text-zinc-800 transition hover:bg-white"
                    >
                        {{ t('checkReservationStatus') }}
                    </Link>
                </div>
            </section>
        </main>
    </SiteLayout>
</template>
