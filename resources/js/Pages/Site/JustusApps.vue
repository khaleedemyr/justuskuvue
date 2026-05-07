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

const { t, lang, setLang, translateMenuLabel } = useSiteI18n();

const navItems = computed(() => {
    if (props.menus.length > 0) return props.menus;
    return ['HOME', 'BRAND', 'HOME SERVICE', 'JUSTUS APPS', "WHAT'S ON", 'CAREERS', 'RESERVATION', 'ABOUT'];
});
const translatedNavItems = computed(() => navItems.value.map((item) => translateMenuLabel(item)));
const brandMenuOpen = ref(false);
let brandMenuCloseTimer = null;

const visibleBlocks = computed(() => {
    const blocks = Array.isArray(props.pageData?.blocks) ? [...props.pageData.blocks] : [];
    return blocks
        .sort((a, b) => Number(a?.sort_order || 0) - Number(b?.sort_order || 0))
        .filter((block) => block?.title || block?.body || block?.image_url);
});

function isVideoHero() {
    const image = String(props.pageData?.hero_image_url || '');
    if (!image) return false;
    if (props.pageData?.hero_media_type === 'video') return true;
    return /\.(mp4|webm)(\?.*)?$/i.test(image);
}

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

onBeforeUnmount(() => {
    if (brandMenuCloseTimer) {
        clearTimeout(brandMenuCloseTimer);
        brandMenuCloseTimer = null;
    }
});
</script>

<template>
    <SiteLayout title="Justus Apps" :menus="menus" :brand-logos="brandLogos" :show-header="false">
        <main class="block w-full bg-black text-white">
            <section class="relative flex h-[60svh] min-h-[320px] w-full flex-col items-center justify-center overflow-hidden px-6 pb-12 pt-24 sm:h-[68svh] sm:min-h-[360px] sm:pb-14 sm:pt-28 md:h-[100dvh] md:min-h-[100dvh] md:max-h-[100dvh] md:pb-20 md:pt-32">
                <video
                    v-if="pageData?.hero_image_url && isVideoHero()"
                    :src="pageData.hero_image_url"
                    class="absolute inset-0 h-full w-full bg-black object-cover object-center"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="auto"
                />
                <template v-else-if="pageData?.hero_image_url">
                    <img
                        :src="pageData.hero_image_url"
                        class="absolute inset-0 h-full w-full bg-black object-cover object-center opacity-45 blur-sm scale-110 md:hidden"
                        alt=""
                        aria-hidden="true"
                    />
                    <img
                        :src="pageData.hero_image_url"
                        class="absolute inset-0 h-full w-full bg-black object-cover object-center"
                        alt="Justus Apps Hero"
                    />
                </template>
                <div v-else class="absolute inset-0 bg-zinc-900" />
                <div class="pointer-events-none absolute inset-0 bg-black/50" />
                <div class="relative z-10 mx-auto flex w-full max-w-7xl flex-1 flex-col items-center justify-center px-6 py-8 text-center sm:py-10" />
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
            </section>

            <section class="w-full max-w-none bg-[#2f2f35]">
                <div class="flex w-full max-w-none flex-col">
                    <div v-if="visibleBlocks.length === 0" class="w-full py-16 text-center text-white/70">
                        {{ t('noAppBlocks') }}
                    </div>
                    <article
                        v-for="(block, index) in visibleBlocks"
                        v-else
                        :key="block.id"
                        class="grid w-full grid-cols-1 gap-0 md:grid-cols-2 md:items-stretch"
                    >
                        <div class="min-w-0" :class="index % 2 === 0 ? 'md:order-1' : 'md:order-2'">
                            <article class="relative h-full min-h-[260px] w-full min-w-0 overflow-hidden bg-black md:min-h-[340px]">
                                <img
                                    v-if="block.image_url"
                                    :src="block.image_url"
                                    :alt="block.title || 'Justus Apps'"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="flex aspect-video w-full items-center justify-center bg-zinc-900 text-sm text-white/60">
                                    {{ t('memberPhoto') }}
                                </div>
                            </article>
                        </div>
                        <div class="min-w-0" :class="index % 2 === 0 ? 'md:order-2' : 'md:order-1'">
                            <article class="h-full min-h-[260px] w-full min-w-0 bg-[#47474d] text-white md:min-h-[340px]">
                                <div class="flex h-full min-h-0 w-full min-w-0 flex-col justify-center px-6 py-10 md:px-10 md:py-14">
                                    <h3 v-if="block.title" class="text-3xl font-semibold">{{ block.title }}</h3>
                                    <p v-if="block.body" class="mt-6 whitespace-pre-wrap text-xl leading-relaxed text-white/90">
                                        {{ block.body }}
                                    </p>
                                </div>
                            </article>
                        </div>
                    </article>
                </div>
            </section>

            <section class="bg-[#3f3f43] px-6 py-14 text-center">
                <h2 class="text-4xl font-semibold tracking-[0.08em] md:text-5xl">{{ t('experienceMore') }}</h2>
                <p class="mt-2 text-xl tracking-[0.06em] text-white/85 md:text-2xl">{{ t('downloadAppCta') }}</p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    <a
                        v-if="pageData?.playstore_url"
                        :href="pageData.playstore_url"
                        target="_blank"
                        rel="noreferrer"
                        class="transition hover:scale-[1.02]"
                    >
                        <img src="/btn_download_mobile_playstore.png" alt="Get it on Google Play" class="h-[56px] w-auto" />
                    </a>
                    <a
                        v-if="pageData?.appstore_url"
                        :href="pageData.appstore_url"
                        target="_blank"
                        rel="noreferrer"
                        class="transition hover:scale-[1.02]"
                    >
                        <img src="/btn_download_mobile_appstore.png" alt="Download on the App Store" class="h-[56px] w-auto" />
                    </a>
                </div>
            </section>
        </main>
    </SiteLayout>
</template>

