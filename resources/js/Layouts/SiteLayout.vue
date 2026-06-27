<script setup>
import { Head } from '@inertiajs/vue3';
import SiteNavbar from '@/Components/SiteNavbar.vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

defineProps({
    title: { type: String, required: true },
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    showHeader: { type: Boolean, default: true },
    showFooter: { type: Boolean, default: true },
    /** Root wrapper classes (Next parity: some pages use bg-[#3f3f43] / bg-[#2f2f35]) */
    shellClass: {
        type: String,
        default: 'min-h-screen bg-[#0f1117] text-white',
    },
});

const { t } = useSiteI18n();
</script>

<template>
    <Head :title="title" />

    <div :class="shellClass">
        <header v-if="showHeader" class="sticky top-0 z-[280] overflow-visible">
            <nav class="overflow-visible border-b border-white/20 bg-black/45 backdrop-blur-md">
                <SiteNavbar :menus="menus" :brand-logos="brandLogos" variant="header" />
            </nav>
        </header>

        <main>
            <slot />
        </main>

        <footer v-if="showFooter" class="w-full bg-[#333333] text-white">
            <div class="mx-auto mb-10 h-px max-w-lg bg-gradient-to-r from-transparent via-amber-400/35 to-transparent sm:mb-12" />
            <div class="w-full px-6 py-12 sm:px-8 md:py-14">
                <div class="flex w-full flex-col gap-10 md:flex-row md:items-start md:justify-between md:gap-12">
                    <div class="flex shrink-0 flex-col items-start">
                        <img src="/logohitam.png" alt="Justus Group" width="240" height="78" class="h-auto w-[200px] sm:w-[240px]" loading="lazy" decoding="async" />
                        <p class="footer-brand mt-6 text-xs font-light uppercase tracking-[0.2em] text-white/90 sm:text-sm">{{ t('craftedGuestJourney') }}</p>
                    </div>
                    <div class="flex max-w-xl flex-col items-start md:items-end md:text-right">
                        <h3 class="text-lg font-semibold text-white md:text-xl">{{ t('connect') }}</h3>
                        <address class="mt-4 space-y-1 text-sm not-italic leading-relaxed text-white/85 sm:text-base">
                            <p>PT Yuditama Mandiri</p>
                            <p>Jl. Pinus Raya No.30, RW.32, Babakan Penghulu,</p>
                            <p>Kec. Cinambo, Kota Bandung, Jawa Barat 40193</p>
                        </address>
                    </div>
                </div>
            </div>
            <div class="w-full border-t border-white/20">
                <div class="flex w-full flex-col gap-2 px-6 py-4 text-xs text-white/75 sm:flex-row sm:items-center sm:justify-between sm:px-8 sm:text-sm">
                    <p>{{ t('copyright') }}</p>
                    <p>{{ t('allRightsReserved') }}</p>
                </div>
            </div>
        </footer>
    </div>
</template>
