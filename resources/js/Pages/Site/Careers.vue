<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    pageData: { type: Object, default: () => ({}) },
});
const { lang, setLang, translateMenuLabel } = useSiteI18n();

const cards = computed(() =>
    (props.pageData?.cards || []).filter((c) => c?.title || c?.image_url),
);

const primaryIsExternal = computed(() =>
    String(props.pageData?.primary_button_url || '').startsWith('http'),
);
const secondaryIsExternal = computed(() =>
    String(props.pageData?.secondary_button_url || '').startsWith('http'),
);
const wordingLines = computed(() =>
    String(props.pageData?.wording || '')
        .split('\n')
        .map((line) => line.trimEnd()),
);
const navItems = computed(() => {
    if (props.menus.length > 0) return props.menus;
    return ['HOME', 'BRAND', 'HOME SERVICE', 'JUSTUS APPS', "WHAT'S ON", 'CAREERS', 'RESERVATION', 'ABOUT'];
});
const translatedNavItems = computed(() => navItems.value.map((item) => translateMenuLabel(item)));
const brandMenuOpen = ref(false);
let brandMenuCloseTimer = null;

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

function openBrandMenu() {
    if (brandMenuCloseTimer) {
        clearTimeout(brandMenuCloseTimer);
        brandMenuCloseTimer = null;
    }
    brandMenuOpen.value = true;
}

function scheduleCloseBrandMenu() {
    if (brandMenuCloseTimer) clearTimeout(brandMenuCloseTimer);
    brandMenuCloseTimer = setTimeout(() => {
        brandMenuOpen.value = false;
        brandMenuCloseTimer = null;
    }, 120);
}

function isVideoHero() {
    const byType = String(props.pageData?.hero_image_type || '').toLowerCase() === 'video';
    if (byType) return true;
    return /\.(mp4|webm)(\?.*)?$/i.test(String(props.pageData?.hero_image_url || ''));
}

function isCompanyCultureLine(line) {
    return String(line || '').trim().toUpperCase() === 'COMPANY CULTURE';
}

onBeforeUnmount(() => {
    if (brandMenuCloseTimer) {
        clearTimeout(brandMenuCloseTimer);
        brandMenuCloseTimer = null;
    }
});
</script>

<template>
    <SiteLayout title="Careers" shell-class="min-h-screen bg-[#3f3f43] text-white" :menus="menus" :brand-logos="brandLogos" :show-header="false">
        <main class="min-h-[100dvh] bg-[#3f3f43] text-white">
            <section class="relative flex h-[100dvh] min-h-[100dvh] flex-col overflow-visible border-b border-white/10">
                <video
                    v-if="pageData?.hero_image_url && isVideoHero()"
                    :src="pageData.hero_image_url"
                    class="absolute inset-0 h-full w-full object-cover"
                    autoplay
                    muted
                    loop
                    playsinline
                />
                <img v-else-if="pageData?.hero_image_url" :src="pageData.hero_image_url" alt="Careers Hero" class="absolute inset-0 h-full w-full object-cover" />
                <div v-else class="absolute inset-0 bg-zinc-900" />
                <div class="absolute inset-0 bg-black/45" />
                <div class="relative z-10 flex flex-1 flex-col px-5 pb-20 pt-24 md:px-10 md:pb-24 md:pt-28">
                    <div class="flex justify-end">
                        <img src="/logohitam.png" alt="Justus Group" class="h-auto w-[140px] object-contain sm:w-[180px] md:w-[220px]" />
                    </div>
                    <div class="mt-auto flex flex-1 flex-col items-center justify-center text-center">
                        <h1 class="text-4xl font-semibold tracking-[0.12em] md:text-6xl">{{ pageData?.title }}</h1>
                        <p class="mt-3 text-xl italic text-white/90 md:text-4xl">{{ pageData?.subtitle }}</p>
                    </div>
                    <div class="absolute inset-x-0 bottom-0 z-[260] w-full border-y border-white/10 bg-black/75 backdrop-blur-md">
                        <div class="mx-auto flex w-full max-w-7xl items-center gap-3 overflow-x-auto px-4 py-3 [touch-action:pan-x] sm:justify-center sm:gap-4 sm:px-6 sm:py-4">
                            <nav class="flex shrink-0 flex-nowrap items-center gap-x-4 whitespace-nowrap text-[12px] tracking-wide text-white/90 sm:text-[14px] md:gap-x-6 md:text-[16px]">
                                <template v-for="(item, idx) in navItems" :key="item">
                                    <div
                                        v-if="String(item).trim().toUpperCase().includes('BRAND')"
                                        @mouseenter="openBrandMenu"
                                        @mouseleave="scheduleCloseBrandMenu"
                                    >
                                        <Link href="/brands" class="transition hover:text-white">{{ translatedNavItems[idx] }}</Link>
                                    </div>
                                    <Link v-else :href="menuToHref(item)" class="transition hover:text-white">
                                        {{ translatedNavItems[idx] }}
                                    </Link>
                                </template>
                            </nav>
                            <div class="ml-1 inline-flex shrink-0 items-center gap-1 rounded-full border border-white/25 bg-black/30 p-1 text-[10px] sm:ml-2 sm:text-[11px]">
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
                        <div
                            v-if="brandMenuOpen"
                            class="absolute left-0 right-0 top-full z-[300] bg-[#3f3f43] shadow-xl"
                            @mouseenter="openBrandMenu"
                            @mouseleave="scheduleCloseBrandMenu"
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
                </div>
            </section>

            <section class="mx-auto w-full max-w-7xl px-6 py-12 text-center md:py-16">
                <div class="mx-auto max-w-5xl space-y-2 text-white/90">
                    <template v-for="(line, idx) in wordingLines" :key="`wording-${idx}`">
                        <p v-if="isCompanyCultureLine(line)" class="pt-5 text-5xl font-bold leading-tight tracking-[0.04em] text-white md:text-7xl">
                            {{ line }}
                        </p>
                        <p v-else class="text-lg leading-relaxed md:text-[2rem]">
                            {{ line || '\u00A0' }}
                        </p>
                    </template>
                </div>
            </section>

            <section class="mx-auto w-full max-w-7xl px-6 pb-10 md:pb-14">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <article v-for="card in cards" :key="card.id" class="overflow-hidden bg-[#303033]">
                        <img
                            v-if="card.image_url"
                            :src="card.image_url"
                            :alt="card.title || `Card ${card.id}`"
                            class="h-[360px] w-full object-cover grayscale"
                        />
                        <div v-else class="flex h-[360px] items-center justify-center bg-zinc-700 text-sm text-white/60">
                            No image
                        </div>
                        <div
                            v-if="card.title"
                            class="bg-black/55 px-4 py-3 text-center text-4xl font-semibold uppercase tracking-[0.06em]"
                        >
                            {{ card.title }}
                        </div>
                    </article>
                </div>
            </section>

            <section
                class="mx-auto w-full max-w-7xl border border-white/10 bg-[#3a3a3d] px-6 py-10 text-center md:py-12"
            >
                <h2 class="whitespace-pre-line text-xl font-semibold uppercase tracking-[0.05em] md:text-3xl">
                    {{ pageData?.cta_title }}
                </h2>
                <p v-if="pageData?.cta_subtitle" class="mt-1 whitespace-pre-line text-sm uppercase tracking-[0.03em] text-white/85 md:text-xl">
                    {{ pageData.cta_subtitle }}
                </p>
                <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <template v-if="pageData?.primary_button_label">
                        <a
                            v-if="primaryIsExternal"
                            :href="pageData.primary_button_url || '#'"
                            target="_blank"
                            rel="noreferrer"
                            class="relative flex min-h-[160px] items-end justify-end overflow-hidden rounded-[1.75rem] bg-[#5a5a60] px-6 py-4 text-right text-3xl font-semibold text-white transition hover:scale-[1.01]"
                        >
                            <img v-if="pageData?.cta_image_1_url" :src="pageData.cta_image_1_url" alt="" class="absolute inset-0 h-full w-full object-cover" />
                            <div class="absolute inset-0 bg-black/30" />
                            <span class="relative whitespace-pre-line">{{ pageData.primary_button_label }}</span>
                        </a>
                        <Link
                            v-else
                            :href="pageData.primary_button_url || '#'"
                            class="relative flex min-h-[160px] items-end justify-end overflow-hidden rounded-[1.75rem] bg-[#5a5a60] px-6 py-4 text-right text-3xl font-semibold text-white transition hover:scale-[1.01]"
                        >
                            <img v-if="pageData?.cta_image_1_url" :src="pageData.cta_image_1_url" alt="" class="absolute inset-0 h-full w-full object-cover" />
                            <div class="absolute inset-0 bg-black/30" />
                            <span class="relative whitespace-pre-line">{{ pageData.primary_button_label }}</span>
                        </Link>
                    </template>
                    <template v-if="pageData?.secondary_button_label">
                        <a
                            v-if="secondaryIsExternal"
                            :href="pageData.secondary_button_url || '#'"
                            target="_blank"
                            rel="noreferrer"
                            class="relative flex min-h-[160px] items-end justify-end overflow-hidden rounded-[1.75rem] bg-[#5a5a60] px-6 py-4 text-right text-3xl font-semibold text-white transition hover:scale-[1.01]"
                        >
                            <img v-if="pageData?.cta_image_2_url" :src="pageData.cta_image_2_url" alt="" class="absolute inset-0 h-full w-full object-cover" />
                            <div class="absolute inset-0 bg-black/30" />
                            <span class="relative whitespace-pre-line">{{ pageData.secondary_button_label }}</span>
                        </a>
                        <Link
                            v-else
                            :href="pageData.secondary_button_url || '#'"
                            class="relative flex min-h-[160px] items-end justify-end overflow-hidden rounded-[1.75rem] bg-[#5a5a60] px-6 py-4 text-right text-3xl font-semibold text-white transition hover:scale-[1.01]"
                        >
                            <img v-if="pageData?.cta_image_2_url" :src="pageData.cta_image_2_url" alt="" class="absolute inset-0 h-full w-full object-cover" />
                            <div class="absolute inset-0 bg-black/30" />
                            <span class="relative whitespace-pre-line">{{ pageData.secondary_button_label }}</span>
                        </Link>
                    </template>
                </div>
            </section>
        </main>
    </SiteLayout>
</template>
