<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import SiteNavbar from '@/Components/SiteNavbar.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    banner: { type: Object, default: null },
    promoSlides: { type: Array, default: () => [] },
    blocks: { type: Array, default: () => [] },
    news: { type: Array, default: () => [] },
});

const heroRef = ref(null);
const navShellRef = ref(null);
const navShellDesktopRef = ref(null);
const siteNavRef = ref(null);
const pinned = ref(false);
const navHeight = ref(0);
let revealObserver = null;
const newsScrollerRef = ref(null);
let newsAutoSlideTimer = null;
const promoStep = ref(0);
let promoAutoplayTimer = null;
const promoViewportRef = ref(null);
const promoViewportWidth = ref(0);
/** Desktop md+: tampil 3 banner per slide */
const isDesktopPromoGrid = ref(false);
let promoMqCleanup = null;
const { t } = useSiteI18n();

const promoSlidesList = computed(() =>
    (Array.isArray(props.promoSlides) ? props.promoSlides : []).filter((s) => s && String(s.image || '').trim() !== ''),
);

const promoDesktopPages = computed(() => {
    const list = promoSlidesList.value;
    const pages = [];
    for (let i = 0; i < list.length; i += 3) {
        pages.push(list.slice(i, i + 3));
    }
    return pages;
});

const promoStepCount = computed(() => {
    const n = promoSlidesList.value.length;
    if (n === 0) return 0;
    if (isDesktopPromoGrid.value) {
        return Math.ceil(n / 3);
    }
    return n;
});

const promoMobileTranslate = computed(() => {
    const total = promoSlidesList.value.length;
    if (total <= 0) return 0;
    return promoStep.value * (100 / total);
});

const promoDesktopTranslate = computed(() => {
    const total = promoDesktopPages.value.length;
    if (total <= 0) return 0;
    return promoStep.value * (100 / total);
});

const promoMobileTranslatePx = computed(() => {
    if (promoViewportWidth.value <= 0) return 0;
    return promoStep.value * promoViewportWidth.value;
});

watch([promoSlidesList, promoStepCount], () => {
    const c = promoStepCount.value;
    if (c === 0) {
        promoStep.value = 0;
        return;
    }
    if (promoStep.value >= c) {
        promoStep.value = 0;
    }
});

const pairedBlocks = computed(() => {
    const rows = [];
    for (let i = 0; i < props.blocks.length; i += 2) {
        rows.push(props.blocks.slice(i, i + 2));
    }
    return rows;
});

function isMediaBlock(block) {
    if (!block) return false;
    const blockType = String(block.block_type || '').toLowerCase();
    if (blockType === 'video' || blockType === 'image') return true;
    return Boolean(block.video_url || block.image || block.image_url);
}

const mobileAlternatingBlocks = computed(() => {
    const source = Array.isArray(props.blocks) ? props.blocks : [];
    if (source.length <= 2) return source;

    const mediaBlocks = source.filter((block) => isMediaBlock(block));
    const textBlocks = source.filter((block) => !isMediaBlock(block));
    if (mediaBlocks.length === 0 || textBlocks.length === 0) return source;

    const output = [];
    let mediaIdx = 0;
    let textIdx = 0;
    let pickMedia = isMediaBlock(source[0]);

    while (mediaIdx < mediaBlocks.length || textIdx < textBlocks.length) {
        if (pickMedia) {
            if (mediaIdx < mediaBlocks.length) output.push(mediaBlocks[mediaIdx++]);
            else if (textIdx < textBlocks.length) output.push(textBlocks[textIdx++]);
        } else {
            if (textIdx < textBlocks.length) output.push(textBlocks[textIdx++]);
            else if (mediaIdx < mediaBlocks.length) output.push(mediaBlocks[mediaIdx++]);
        }
        pickMedia = !pickMedia;
    }

    return output;
});

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
    if (!heroRef.value) return;
    const isDesktop = typeof window !== 'undefined' && window.matchMedia('(min-width: 768px)').matches;
    const navEl = isDesktop ? navShellDesktopRef.value : navShellRef.value;
    navHeight.value = navEl?.offsetHeight || 0;
    const heroRect = heroRef.value.getBoundingClientRect();
    pinned.value = heroRect.bottom <= 0;
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

function stopPromoAutoplay() {
    if (promoAutoplayTimer) {
        clearInterval(promoAutoplayTimer);
        promoAutoplayTimer = null;
    }
}

function startPromoAutoplay() {
    stopPromoAutoplay();
    const c = promoStepCount.value;
    if (c <= 1) return;
    promoAutoplayTimer = window.setInterval(() => {
        const steps = promoStepCount.value;
        if (steps <= 1) return;
        promoStep.value = (promoStep.value + 1) % steps;
    }, 6000);
}

function goPromo(dir) {
    const c = promoStepCount.value;
    if (c === 0) return;
    promoStep.value = (promoStep.value + dir + c) % c;
    startPromoAutoplay();
}

function setPromo(i) {
    promoStep.value = i;
    startPromoAutoplay();
}

function syncPromoBreakpoint() {
    if (typeof window === 'undefined') return;
    isDesktopPromoGrid.value = window.matchMedia('(min-width: 768px)').matches;
}

function updatePromoViewportWidth() {
    promoViewportWidth.value = promoViewportRef.value?.clientWidth || 0;
}

onMounted(() => {
    updatePinned();
    syncPromoBreakpoint();
    updatePromoViewportWidth();
    const mq = window.matchMedia('(min-width: 768px)');
    mq.addEventListener('change', syncPromoBreakpoint);
    promoMqCleanup = () => mq.removeEventListener('change', syncPromoBreakpoint);

    window.addEventListener('scroll', updatePinned, { passive: true });
    window.addEventListener('resize', updatePinned);
    window.addEventListener('resize', updatePromoViewportWidth);

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
    startPromoAutoplay();
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', updatePinned);
    window.removeEventListener('resize', updatePinned);
    window.removeEventListener('resize', updatePromoViewportWidth);
    promoMqCleanup?.();
    promoMqCleanup = null;
    revealObserver?.disconnect();
    stopNewsAutoSlide();
    stopPromoAutoplay();
});
</script>

<template>
    <SiteLayout title="Home" :show-header="false">
        <main class="font-montserrat w-full overflow-x-hidden bg-black text-white">
            <div class="flex h-[100dvh] max-h-[100dvh] flex-col overflow-hidden md:block md:h-auto md:max-h-none">
            <div
                id="home-hero"
                ref="heroRef"
                class="relative flex min-h-0 w-full flex-1 flex-col overflow-hidden bg-black md:h-[100dvh] md:min-h-[100dvh] md:max-h-[100dvh] md:flex-none"
            >
                <template v-if="banner?.image && isVideoBanner()">
                    <video
                        class="absolute inset-0 h-full w-full bg-black object-cover object-center"
                        :src="banner.image"
                        autoplay
                        muted
                        loop
                        playsinline
                    />
                </template>
                <template v-else-if="banner?.image">
                    <img
                        :src="banner.image"
                        :alt="banner?.title || 'Head Banner'"
                        class="absolute inset-0 h-full w-full bg-black object-cover object-center opacity-45 blur-sm scale-110"
                    />
                    <img
                        :src="banner.image"
                        :alt="banner?.title || 'Head Banner'"
                        class="absolute inset-0 h-full w-full bg-black object-cover object-center"
                    />
                </template>
                <div v-else class="absolute inset-0 bg-zinc-900" />

                <div class="pointer-events-none absolute inset-0 bg-black/50" />

                <button
                    v-if="!pinned"
                    type="button"
                    class="absolute left-4 top-4 z-[120] inline-flex items-center justify-center rounded-md border border-white/20 bg-black/50 p-2 text-white/90 backdrop-blur-sm transition hover:bg-black/70 hover:text-white md:hidden"
                    aria-label="Menu"
                    @click="siteNavRef?.toggleMobileMenu()"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="relative z-10 flex min-h-0 flex-1 flex-col">
                    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col items-center justify-center px-6 py-8 text-center sm:py-10">
                        <img
                            src="/logohitam.png"
                            alt="Justus Group"
                            class="mb-4 h-auto w-[220px] object-contain sm:mb-5 sm:w-[260px] md:mb-6 md:w-[320px]"
                        />
                        <h1
                            class="hero-title font-normal uppercase leading-tight tracking-[0.035em]"
                            style="font-family: 'Montserrat', Arial, Helvetica, sans-serif; font-size: 44px"
                        >
                            {{ banner?.title || 'CRAFTED GUEST JOURNEY' }}
                        </h1>
                        <p
                            class="hero-subtitle mt-3 font-normal italic leading-tight text-white/90"
                            style="font-family: 'Montserrat', Arial, Helvetica, sans-serif; font-size: 20px"
                        >
                            {{ banner?.subtitle || 'Warm Caring Hospitality Experiences' }}
                        </p>
                    </div>
                </div>

                <div
                    ref="navShellDesktopRef"
                    :class="pinned ? 'md:fixed md:inset-x-0 md:top-0 md:bottom-auto' : 'md:absolute md:inset-x-0 md:bottom-0'"
                    class="pointer-events-none z-30 hidden w-full border-y border-white/10 bg-black/75 backdrop-blur-md md:block"
                >
                    <div class="pointer-events-auto">
                        <SiteNavbar :menus="menus" :brand-logos="brandLogos" variant="bar" :mobile-bar-at-top="true" />
                    </div>
                </div>
            </div>

            <div
                ref="navShellRef"
                :class="pinned ? 'fixed inset-x-0 top-0 z-40' : 'relative z-30'"
                class="w-full border-y border-white/10 bg-black/75 backdrop-blur-md md:hidden"
            >
                <SiteNavbar
                    ref="siteNavRef"
                    :menus="menus"
                    :brand-logos="brandLogos"
                    variant="bar"
                    :mobile-bar-at-top="pinned"
                    :delegate-mobile-menu-button="!pinned"
                />
            </div>
            </div>
            <div aria-hidden class="shrink-0" :style="{ height: pinned ? `${navHeight}px` : '0px' }" />

            <section v-if="promoSlidesList.length > 0" class="relative w-full max-w-none border-y border-white/10 bg-[#1b1b1f]">
                <!-- Mobile: 1 banner per slide; Desktop md+: 3 banner per slide -->
                <div class="relative w-full">
                    <div ref="promoViewportRef" class="relative overflow-hidden">
                        <!-- Mobile strip -->
                        <div
                            class="flex transition-transform duration-500 ease-out md:hidden"
                            :style="{ width: `${Math.max(promoViewportWidth * (promoSlidesList.length || 1), promoViewportWidth)}px`, transform: `translate3d(-${promoMobileTranslatePx}px, 0, 0)` }"
                        >
                            <div
                                v-for="slide in promoSlidesList"
                                :key="slide.id"
                                class="shrink-0"
                                :style="{ width: `${promoViewportWidth}px` }"
                            >
                                <a
                                    v-if="slide.link_url"
                                    :href="slide.link_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block w-full focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/80"
                                >
                                    <img
                                        :src="slide.image"
                                        :alt="slide.title || 'Promo'"
                                        class="block h-auto w-full max-h-[min(52svh,400px)] object-contain object-center sm:max-h-[min(48svh,460px)]"
                                        loading="lazy"
                                    />
                                </a>
                                <img
                                    v-else
                                    :src="slide.image"
                                    :alt="slide.title || 'Promo'"
                                    class="block h-auto w-full max-h-[min(52svh,400px)] object-contain object-center sm:max-h-[min(48svh,460px)]"
                                    loading="lazy"
                                />
                            </div>
                        </div>
                        <!-- Desktop: halaman berisi grid 3 kolom -->
                        <div
                            class="hidden transition-transform duration-500 ease-out md:flex"
                            :style="{ transform: `translateX(-${promoDesktopTranslate}%)` }"
                        >
                            <div
                                v-for="(page, pageIdx) in promoDesktopPages"
                                :key="`promo-page-${pageIdx}`"
                                class="min-w-full shrink-0 px-1 py-2 md:px-1 md:py-2"
                            >
                                <div class="mx-auto grid w-full max-w-[1920px] grid-cols-3 gap-1 md:gap-1 lg:gap-1.5">
                                    <div
                                        v-for="slide in page"
                                        :key="slide.id"
                                        class="flex min-h-0 min-w-0 flex-col justify-center"
                                    >
                                        <a
                                            v-if="slide.link_url"
                                            :href="slide.link_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="block w-full focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/80"
                                        >
                                            <img
                                                :src="slide.image"
                                                :alt="slide.title || 'Promo'"
                                                class="block h-[min(34vh,320px)] w-full object-cover object-center lg:h-[min(38vh,380px)]"
                                                loading="lazy"
                                            />
                                        </a>
                                        <img
                                            v-else
                                            :src="slide.image"
                                            :alt="slide.title || 'Promo'"
                                            class="block h-[min(34vh,320px)] w-full object-cover object-center lg:h-[min(38vh,380px)]"
                                            loading="lazy"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button
                            v-if="promoStepCount > 1"
                            type="button"
                            class="absolute left-2 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-black/45 text-lg text-white backdrop-blur-sm transition hover:bg-black/65 md:left-4 md:h-10 md:w-10"
                            aria-label="Slide sebelumnya"
                            @click="goPromo(-1)"
                        >
                            ‹
                        </button>
                        <button
                            v-if="promoStepCount > 1"
                            type="button"
                            class="absolute right-2 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-black/45 text-lg text-white backdrop-blur-sm transition hover:bg-black/65 md:right-4 md:h-10 md:w-10"
                            aria-label="Slide berikutnya"
                            @click="goPromo(1)"
                        >
                            ›
                        </button>
                    </div>
                    <div
                        v-if="promoStepCount > 1"
                        class="flex justify-center gap-2 py-3"
                    >
                        <button
                            v-for="i in promoStepCount"
                            :key="i"
                            type="button"
                            class="h-2 rounded-full transition"
                            :class="i - 1 === promoStep ? 'w-6 bg-amber-400/90' : 'w-2 bg-white/35'"
                            :aria-label="`Promo slide ${i}`"
                            @click="setPromo(i - 1)"
                        />
                    </div>
                </div>
            </section>

            <section class="w-full bg-[#2f2f35]">
                <div class="flex w-full flex-col">
                    <template v-if="pairedBlocks.length === 0">
                        <div class="w-full py-16 text-center text-white/70">{{ t('noHomeBlocks') }}</div>
                    </template>
                    <template v-else>
                        <div
                            v-for="(pair, rowIndex) in pairedBlocks"
                            :key="`desktop-row-${rowIndex}`"
                            class="home-reveal hidden w-full gap-0 md:grid md:grid-cols-2 md:items-stretch"
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
                                    <h3 v-if="block.title" class="font-montserrat text-[14px] font-bold leading-[1.35]">{{ block.title }}</h3>
                                    <p
                                        v-if="block.body"
                                        class="font-montserrat mt-4 whitespace-pre-wrap text-[14px] font-normal leading-[1.5] sm:mt-5 md:mt-6"
                                        :class="block.bg_variant === 'light' ? 'text-[#111118]/90' : 'text-white/90'"
                                    >
                                        {{ block.body }}
                                    </p>
                                </template>
                            </article>
                        </div>
                        <div class="flex w-full flex-col md:hidden">
                            <article
                                v-for="(block, blockIndex) in mobileAlternatingBlocks"
                                :key="`mobile-block-${block.id || blockIndex}`"
                                class="home-reveal min-w-0"
                                :class="blockArticleClass(block)"
                                data-reveal
                                :style="{ transitionDelay: `${blockIndex * 60}ms` }"
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
                                    <h3 v-if="block.title" class="font-montserrat text-[14px] font-bold leading-[1.35]">{{ block.title }}</h3>
                                    <p
                                        v-if="block.body"
                                        class="font-montserrat mt-4 whitespace-pre-wrap text-[14px] font-normal leading-[1.5] sm:mt-5 md:mt-6"
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

<style scoped>
.hero-title {
    font-family: 'Montserrat', Arial, Helvetica, sans-serif !important;
    font-size: 44px !important;
}

.hero-subtitle {
    font-family: 'Montserrat', Arial, Helvetica, sans-serif !important;
    font-size: 20px !important;
}
</style>

