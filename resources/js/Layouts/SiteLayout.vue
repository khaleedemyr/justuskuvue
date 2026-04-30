<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
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

const navItems = computed(() => {
    if (props.menus.length > 0) {
        return props.menus;
    }
    return ['HOME', 'BRAND', 'HOME SERVICE', 'JUSTUS APPS', "WHAT'S ON", 'CAREERS', 'RESERVATION', 'ABOUT'];
});

const { lang, setLang, t, translateMenuLabel } = useSiteI18n();

const translatedNavItems = computed(() => navItems.value.map((item) => translateMenuLabel(item)));

function menuToHref(label) {
    const key = String(label || '').trim().toUpperCase();
    if (key === 'HOME') return '/';
    if (key.includes('HOME SERVICE')) return '/home-service';
    if (key === 'BRAND') return '/brands';
    if (key === 'JUSTUS APPS') return '/justus-apps';
    if (key === "WHAT'S ON") return '/whats-on';
    if (key === 'CAREERS') return '/careers';
    if (key === 'RESERVATION') return '/reservation';
    if (key === 'ABOUT') return '/about';
    return '#';
}

function brandHref(brand) {
    const key = String(brand?.slug || brand?.title || '').trim();
    return key ? `/brands?brand=${encodeURIComponent(key)}` : '/brands';
}

</script>

<template>
    <Head :title="title" />

    <div :class="shellClass">
        <header v-if="showHeader" class="sticky top-0 z-30">
            <nav class="border-b border-white/20 bg-black/45 backdrop-blur-md">
                <div class="relative mx-auto w-full max-w-7xl">
                    <div class="flex w-full items-center justify-start gap-x-4 overflow-x-auto whitespace-nowrap px-4 py-4 text-sm tracking-wide [touch-action:pan-x] sm:justify-center sm:gap-x-5 sm:text-base md:gap-x-6 md:text-lg">
                        <template v-for="(item, idx) in navItems" :key="item">
                            <div v-if="String(item).trim().toUpperCase().includes('BRAND')" class="group">
                                <Link href="/brands" class="text-white/90 transition hover:text-white">{{ translatedNavItems[idx] }}</Link>
                                <div
                                    class="pointer-events-none fixed left-0 right-0 top-[58px] z-[100] max-h-0 overflow-hidden bg-[#3f3f43] opacity-0 shadow-xl transition-all duration-200 group-hover:pointer-events-auto group-hover:max-h-[360px] group-hover:opacity-100"
                                >
                                    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-center gap-6 px-6 py-10">
                                        <Link
                                            v-for="brand in brandLogos"
                                            :key="brand.id"
                                            :href="brandHref(brand)"
                                            class="flex h-[80px] w-[160px] items-center justify-center px-1 transition hover:scale-105 md:h-[96px] md:w-[210px]"
                                        >
                                            <img :src="brand.logo" :alt="brand.title || 'Brand Logo'" class="h-full w-full object-contain" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <Link v-else :href="menuToHref(item)" class="text-white/90 transition hover:text-white">
                                {{ translatedNavItems[idx] }}
                            </Link>
                        </template>
                        <div class="ml-2 inline-flex shrink-0 items-center gap-1 rounded-full border border-white/25 bg-black/30 p-1 text-[10px] sm:text-[11px]">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-full px-2 py-1 transition"
                                :class="lang === 'id' ? 'bg-white/20 text-white' : 'text-white/75 hover:text-white'"
                                @click="setLang('id')"
                            >
                                <span aria-hidden>🇮🇩</span> ID
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-full px-2 py-1 transition"
                                :class="lang === 'en' ? 'bg-white/20 text-white' : 'text-white/75 hover:text-white'"
                                @click="setLang('en')"
                            >
                                <span aria-hidden>🇬🇧</span> EN
                            </button>
                        </div>
                    </div>
                </div>
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
                        <img src="/logohitam.png" alt="Justus Group" class="h-auto w-[200px] sm:w-[240px]" />
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

