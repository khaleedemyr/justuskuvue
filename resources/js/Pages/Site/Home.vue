<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    banner: { type: Object, default: null },
    blocks: { type: Array, default: () => [] },
    news: { type: Array, default: () => [] },
});

const heroRef = ref(null);
const navShellRef = ref(null);
const pinned = ref(false);
const navHeight = ref(0);
let revealObserver = null;
const newsScrollerRef = ref(null);
let newsAutoSlideTimer = null;
const brandMenuOpen = ref(false);
let brandMenuCloseTimer = null;
const { lang, setLang, t, translateMenuLabel } = useSiteI18n();

const navItems = computed(() => {
    if (props.menus.length > 0) return props.menus;
    return ['HOME', 'BRAND', 'HOME SERVICE', 'JUSTUS APPS', "WHAT'S ON", 'CAREERS', 'RESERVATION', 'ABOUT'];
});
const translatedNavItems = computed(() => navItems.value.map((item) => translateMenuLabel(item)));

const pairedBlocks = computed(() => {
    const rows = [];
    for (let i = 0; i < props.blocks.length; i += 2) {
        rows.push(props.blocks.slice(i, i + 2));
    }
    return rows;
});

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
    if (brandMenuCloseTimer) {
        clearTimeout(brandMenuCloseTimer);
    }
    brandMenuCloseTimer = window.setTimeout(() => {
        brandMenuOpen.value = false;
        brandMenuCloseTimer = null;
    }, 220);
}

function isVideoBanner() {
    const image = String(props.banner?.image || '');
    if (!image) return false;
    if (props.banner?.headMediaType === 'video' || props.banner?.headIsVideo) return true;
    return /\.(mp4|webm)(\?.*)?$/i.test(image);
}

function blockShellClass(block) {
    return block?.bg_variant === 'light'
        ? 'bg-[#efefef] text-[#111118]'
        : 'bg-[#47474d] text-white';
}

function blockArticleClass(block) {
    if (block?.block_type === 'video') {
        return 'relative h-full min-h-[360px] overflow-hidden bg-black md:min-h-[520px]';
    }
    return `flex h-full min-h-[360px] flex-col justify-center px-6 py-12 md:px-10 md:py-16 md:min-h-[520px] ${blockShellClass(block)}`;
}

function updatePinned() {
    if (!heroRef.value || !navShellRef.value) return;
    navHeight.value = navShellRef.value.offsetHeight || 0;
    const heroRect = heroRef.value.getBoundingClientRect();
    const navTop = heroRect.bottom - (navShellRef.value.offsetHeight || 1);
    pinned.value = navTop <= 0.5;
}

function scrollNewsBy(dir) {
    const el = newsScrollerRef.value;
    if (!el) return;
    const card = el.querySelector('[data-news-card]');
    const step = card ? card.clientWidth + 24 : 360;
    el.scrollBy({ left: step * dir, behavior: 'smooth' });
}

function startNewsAutoSlide() {
    if (newsAutoSlideTimer || !newsScrollerRef.value) return;
    newsAutoSlideTimer = window.setInterval(() => {
        const el = newsScrollerRef.value;
        if (!el) return;
        const atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 8;
        if (atEnd) {
            el.scrollTo({ left: 0, behavior: 'smooth' });
            return;
        }
        scrollNewsBy(1);
    }, 5000);
}

function stopNewsAutoSlide() {
    if (newsAutoSlideTimer) {
        clearInterval(newsAutoSlideTimer);
        newsAutoSlideTimer = null;
    }
}

onMounted(() => {
    updatePinned();
    window.addEventListener('scroll', updatePinned, { passive: true });
    window.addEventListener('resize', updatePinned);

    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver?.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.18 },
    );

    document.querySelectorAll('[data-reveal]').forEach((el) => revealObserver?.observe(el));
    startNewsAutoSlide();
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', updatePinned);
    window.removeEventListener('resize', updatePinned);
    revealObserver?.disconnect();
    stopNewsAutoSlide();
    if (brandMenuCloseTimer) {
        clearTimeout(brandMenuCloseTimer);
    }
});
</script>

<template>
    <SiteLayout title="Home" :show-header="false">
        <main class="w-full bg-black text-white">
            <div ref="heroRef" class="relative flex h-[100dvh] min-h-[100dvh] max-h-[100dvh] w-full flex-col overflow-visible bg-black">
                <video
                    v-if="banner?.image && isVideoBanner()"
                    class="absolute inset-0 h-full w-full bg-black object-contain object-center md:object-cover"
                    :src="banner.image"
                    autoplay
                    muted
                    loop
                    playsinline
                />
                <img
                    v-else-if="banner?.image"
                    :src="banner.image"
                    :alt="banner?.title || 'Head Banner'"
                    class="absolute inset-0 h-full w-full bg-black object-contain object-center md:object-cover"
                />
                <div v-else class="absolute inset-0 bg-zinc-900" />

                <div class="pointer-events-none absolute inset-0 bg-black/50" />

                <div class="relative z-10 flex min-h-0 flex-1 flex-col">
                    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col items-center justify-center px-6 py-8 text-center sm:py-10">
                        <h1 class="text-4xl font-semibold uppercase tracking-[0.035em] md:text-6xl">
                            {{ banner?.title || 'CRAFTED GUEST JOURNEY' }}
                        </h1>
                        <p class="mt-3 text-lg text-white/90 md:text-2xl">
                            {{ banner?.subtitle || 'Warm Caring Hospitality Experiences' }}
                        </p>
                    </div>
                </div>

                <div
                    ref="navShellRef"
                    :class="pinned ? 'fixed inset-x-0 top-0 z-40' : 'absolute inset-x-0 bottom-0 z-30'"
                    class="relative w-full border-y border-white/10 bg-black/75 backdrop-blur-md"
                >
                    <div class="mx-auto flex w-full max-w-7xl items-center justify-center gap-4 px-6 py-4">
                        <nav class="flex flex-wrap items-center justify-center gap-x-4 text-sm tracking-wide text-white/90 sm:gap-x-5 sm:text-base md:gap-x-6 md:text-lg">
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
                <div
                    v-if="brandMenuOpen"
                    class="absolute left-0 right-0 top-full z-[200] bg-[#3f3f43] shadow-xl"
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
            <div aria-hidden class="shrink-0" :style="{ height: pinned ? `${navHeight}px` : '0px' }" />

            <section class="w-full bg-[#2f2f35]">
                <div class="flex w-full flex-col">
                    <template v-if="pairedBlocks.length === 0">
                        <div class="w-full py-16 text-center text-white/70">{{ t('noHomeBlocks') }}</div>
                    </template>
                    <template v-else>
                        <div
                            v-for="(pair, rowIndex) in pairedBlocks"
                            :key="`row-${rowIndex}`"
                            class="home-reveal grid w-full grid-cols-1 gap-0 md:grid-cols-2 md:items-stretch"
                            data-reveal
                            :style="{ transitionDelay: `${rowIndex * 90}ms` }"
                        >
                            <article
                                v-for="block in pair"
                                :key="block.id"
                                class="min-w-0"
                                :class="blockArticleClass(block)"
                            >
                                <template v-if="block.block_type === 'video'">
                                    <template v-if="block.video_url">
                                        <div class="absolute inset-0">
                                            <video
                                                class="h-full w-full bg-black object-cover object-center"
                                                :src="block.video_url"
                                                autoplay
                                                muted
                                                loop
                                                playsinline
                                                preload="auto"
                                            />
                                        </div>
                                        <div class="pointer-events-none absolute inset-0 bg-black/10" />
                                    </template>
                                    <div v-else class="flex h-full min-h-[360px] items-center justify-center bg-zinc-900 text-sm text-white/50 md:min-h-[520px]">
                                        {{ t('noVideoUploaded') }}
                                    </div>
                                </template>
                                <template v-else>
                                    <h3 v-if="block.title" class="text-3xl font-semibold md:text-4xl">{{ block.title }}</h3>
                                    <p
                                        v-if="block.body"
                                        class="mt-6 whitespace-pre-wrap text-xl leading-relaxed"
                                        :class="block.bg_variant === 'light' ? 'text-[#111118]/90' : 'text-white/90'"
                                    >
                                        {{ block.body }}
                                    </p>
                                </template>
                            </article>
                        </div>
                    </template>
                </div>
            </section>

            <section class="w-full bg-[#333333] px-6 pb-8 pt-16 text-white md:pb-10">
                <div class="w-full">
                    <h2 class="text-left text-3xl font-bold uppercase tracking-[0.04em] md:text-4xl">JUST-NEWS</h2>
                    <div class="mt-3 h-px w-24 bg-gradient-to-r from-amber-400/80 to-transparent" />

                    <div class="relative mt-8">
                        <button
                            type="button"
                            class="absolute -left-1 top-[32%] z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/25 bg-black/50 text-lg text-white backdrop-blur-sm transition hover:scale-105 hover:bg-black/70 md:flex"
                            @click="scrollNewsBy(-1)"
                        >
                            ‹
                        </button>
                        <button
                            type="button"
                            class="absolute -right-1 top-[32%] z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/25 bg-black/50 text-lg text-white backdrop-blur-sm transition hover:scale-105 hover:bg-black/70 md:flex"
                            @click="scrollNewsBy(1)"
                        >
                            ›
                        </button>
                    <div
                        ref="newsScrollerRef"
                        class="just-news-scroller flex w-full items-stretch gap-6 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                        @mouseenter="stopNewsAutoSlide"
                        @mouseleave="startNewsAutoSlide"
                    >
                        <Link
                            v-for="item in news"
                            :key="item.id"
                            :href="`/news/${item.id}`"
                            data-news-card
                            class="home-reveal group flex min-h-0 w-[min(22rem,calc(100vw-3rem))] shrink-0 snap-center flex-col self-stretch overflow-hidden bg-black/30 ring-1 ring-white/5 transition duration-500 hover:-translate-y-1 hover:ring-white/10 md:w-[calc((100%-1.5rem)/2)] md:min-w-[calc((100%-1.5rem)/2)] md:max-w-[calc((100%-1.5rem)/2)] lg:w-[calc((100%-3rem)/3)] lg:min-w-[calc((100%-3rem)/3)] lg:max-w-[calc((100%-3rem)/3)]"
                            data-reveal
                        >
                            <div class="relative aspect-[16/10] w-full overflow-hidden bg-zinc-800">
                                <img v-if="item.image" :src="item.image" :alt="item.title" class="h-full w-full object-cover transition group-hover:scale-105" />
                                <div v-else class="flex h-full items-center justify-center text-sm text-white/40">{{ t('noImage') }}</div>
                            </div>
                            <div class="flex min-h-0 flex-1 flex-col px-3 py-4 text-center">
                                <h3 class="line-clamp-3 min-h-[4.5rem] text-base font-semibold leading-snug text-white">
                                    {{ item.title }}
                                </h3>
                                <p class="mt-2 line-clamp-2 min-h-[2.85rem] text-sm leading-relaxed text-white/80">
                                    {{ (item.content || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() || '\u00a0' }}
                                </p>
                                <div class="mt-auto flex flex-col items-center gap-2 pt-3">
                                    <span class="text-xs text-white/45">{{ item.published_at || '' }}</span>
                                    <span class="text-xs font-medium uppercase tracking-wider text-amber-400/90">
                                        {{ t('readMore') }} →
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>
                    </div>
                </div>
            </section>
        </main>
    </SiteLayout>
</template>

