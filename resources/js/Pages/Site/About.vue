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

const sections = computed(() => (Array.isArray(props.pageData?.sections) ? props.pageData.sections : []));
const storySection = computed(() => sections.value.find((s) => s?.id === 'our-story') || sections.value[0] || null);
const philosophySection = computed(() => sections.value.find((s) => s?.id === 'brand-philosophy') || sections.value[1] || null);
const bottomSection = computed(() => sections.value.find((s) => s?.id === 'vision-mission') || sections.value[2] || null);
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

onBeforeUnmount(() => {
    if (brandMenuCloseTimer) {
        clearTimeout(brandMenuCloseTimer);
        brandMenuCloseTimer = null;
    }
});
</script>

<template>
    <SiteLayout title="About" :menus="menus" :brand-logos="brandLogos" :show-header="false">
        <main class="min-h-[100dvh] bg-[#3f3f43] text-white">
            <section class="relative min-h-[44vh] overflow-visible border-b border-white/10 pb-16">
                <template v-if="pageData?.hero_image_url">
                    <img
                        :src="pageData.hero_image_url"
                        alt=""
                        aria-hidden="true"
                        class="absolute inset-0 h-full w-full object-cover object-center opacity-45 blur-sm scale-110 md:hidden"
                    />
                    <img
                        :src="pageData.hero_image_url"
                        alt="About Hero"
                        class="absolute inset-0 h-full w-full object-contain object-center md:object-cover"
                    />
                </template>
                <div v-else class="absolute inset-0 bg-zinc-900" />
                <div class="absolute inset-0 bg-black/45" />

                <div class="relative z-10 mx-auto flex min-h-[44vh] w-full max-w-7xl flex-col items-center justify-center px-6 py-12 text-center">
                    <div class="mb-6 w-full text-left">
                        <Link
                            href="/"
                            class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-black/35 px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white/90 transition hover:border-white/60 hover:bg-black/55 hover:text-white md:text-sm"
                        >
                            <span aria-hidden>←</span>
                            {{ t('backToHome') }}
                        </Link>
                    </div>
                    <h1 class="text-4xl font-semibold tracking-[0.08em] md:text-6xl">{{ pageData?.title || 'OUR STORY' }}</h1>
                    <p class="mt-3 text-2xl italic text-white/90 md:text-4xl">{{ pageData?.subtitle || '' }}</p>
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
            </section>

            <section class="mx-auto w-full max-w-7xl px-6 py-10 md:py-12">
                <article v-if="storySection" class="mb-10">
                    <p class="whitespace-pre-line text-2xl leading-relaxed text-white/88 [text-align:justify] [text-justify:inter-word]">
                        {{ storySection.content }}
                    </p>
                </article>

                <article v-if="philosophySection">
                    <img
                        v-if="philosophySection.image_url"
                        :src="philosophySection.image_url"
                        :alt="philosophySection.title"
                        class="h-auto w-full rounded-sm border border-white/10 object-cover"
                    />
                    <h2 class="mt-8 text-4xl font-medium text-white/95">{{ philosophySection.title }}</h2>
                    <p class="mt-4 whitespace-pre-line text-2xl leading-relaxed text-white/88 [text-align:justify] [text-justify:inter-word]">
                        {{ philosophySection.content }}
                    </p>
                </article>
            </section>

            <section
                v-if="bottomSection"
                class="mx-auto grid w-full max-w-7xl grid-cols-1 gap-8 px-6 pb-14 md:grid-cols-[42%_1fr]"
            >
                <div>
                    <img
                        v-if="bottomSection.image_url"
                        :src="bottomSection.image_url"
                        :alt="bottomSection.title"
                        class="h-full min-h-[260px] w-full object-cover"
                    />
                    <div v-else class="flex min-h-[260px] items-center justify-center bg-zinc-900 text-sm text-white/60">
                        {{ t('noImage') }}
                    </div>
                </div>
                <article>
                    <h2 class="text-5xl font-semibold uppercase">{{ bottomSection.title }}</h2>
                    <p v-if="bottomSection.subtitle" class="mt-2 text-2xl text-white/90">{{ bottomSection.subtitle }}</p>
                    <p class="mt-4 whitespace-pre-line text-2xl leading-relaxed text-white/90 [text-align:justify] [text-justify:inter-word]">
                        {{ bottomSection.content }}
                    </p>
                </article>
            </section>
        </main>
    </SiteLayout>
</template>
