<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import SiteHeroNavBar from '@/Components/SiteHeroNavBar.vue';
import SiteHeroBannerText from '@/Components/SiteHeroBannerText.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    heroImageUrl: { type: String, default: null },
    heroTitle: { type: String, default: null },
    heroSubtitle: { type: String, default: null },
    landing: { type: Object, default: () => ({}) },
});

const { t } = useSiteI18n();

const pageTitle = computed(() => {
    const h = String(props.heroTitle || props.landing?.hero_title || '').trim();
    return h || t('homeService');
});

const heroTitle = computed(() => String(props.heroTitle || props.landing?.hero_title || '').trim() || t('homeService'));

const heroSubtitleParagraphs = computed(() => {
    const raw = String(props.heroSubtitle || props.landing?.hero_subtitle || '').trim();
    if (!raw) return [];
    return raw.split(/\n\s*\n/).map((p) => p.trim()).filter(Boolean);
});

const blocks = computed(() =>
    Array.isArray(props.landing?.content_blocks) ? props.landing.content_blocks.filter(Boolean) : [],
);

const collageImages = computed(() =>
    Array.isArray(props.landing?.collage_images) ? props.landing.collage_images.filter(Boolean) : [],
);
function simpleHash(str) {
    let h = 0;
    for (let i = 0; i < str.length; i += 1) h = ((h << 5) - h + str.charCodeAt(i)) | 0;
    return Math.abs(h);
}
const collageTiles = computed(() =>
    [...collageImages.value]
        .map((src) => ({ src, score: simpleHash(src) }))
        .sort((a, b) => a.score - b.score)
        .map((x, idx) => ({
            src: x.src,
            aspectClass: ['aspect-[4/3]', 'aspect-square', 'aspect-[3/4]', 'aspect-[16/10]', 'aspect-[5/4]'][idx % 5],
        })),
);
const lightboxImages = ref([]);
const lightboxIndex = ref(0);

const bookingCard = computed(() => props.landing?.gallery_card || null);
const menuCard = computed(() => props.landing?.menu_card || null);
const cta = computed(() => props.landing?.cta || null);
const menuCardHref = computed(() => {
    const u = String(menuCard.value?.url || '').trim();
    return u || '/home-service/menu';
});
const bookingCardHref = computed(() => {
    const u = String(bookingCard.value?.url || '').trim();
    return u || '/reservation';
});

function bookingDisplayLabel(label) {
    const raw = String(label || '').trim();
    if (!raw) return 'BOOKING & RESERVATION';
    if (/gallery/i.test(raw)) return 'BOOKING & RESERVATION';
    return raw;
}

function youtubeEmbedSrc(url) {
    const u = String(url || '').trim();
    if (!u) return '';
    const embed = u.match(/youtube\.com\/embed\/([\w-]+)/i);
    if (embed) return `https://www.youtube.com/embed/${embed[1]}`;
    const watch = u.match(/[?&]v=([\w-]{6,})/);
    if (watch) return `https://www.youtube.com/embed/${watch[1]}`;
    const shortU = u.match(/youtu\.be\/([\w-]+)/);
    if (shortU) return `https://www.youtube.com/embed/${shortU[1]}`;
    return '';
}

function isDirectVideo(url) {
    return /\.(mp4|webm)(\?|$)/i.test(String(url || ''));
}

function isExternalHref(url) {
    return /^https?:\/\//i.test(String(url || '').trim());
}

function isVideoHero() {
    const src = String(props.heroImageUrl || '');
    if (!src) return false;
    return /\.(mp4|webm)(\?.*)?$/i.test(src);
}

const lightboxOpen = computed(() => lightboxImages.value.length > 0);
const currentLightboxImage = computed(() => lightboxImages.value[lightboxIndex.value] || '');

function openLightbox(image) {
    if (!image) return;
    lightboxImages.value = collageImages.value.slice();
    lightboxIndex.value = Math.max(0, lightboxImages.value.indexOf(image));
}

function closeLightbox() {
    lightboxImages.value = [];
    lightboxIndex.value = 0;
}

function nextImage() {
    if (lightboxImages.value.length <= 1) return;
    lightboxIndex.value = (lightboxIndex.value + 1) % lightboxImages.value.length;
}

function prevImage() {
    if (lightboxImages.value.length <= 1) return;
    lightboxIndex.value = (lightboxIndex.value - 1 + lightboxImages.value.length) % lightboxImages.value.length;
}

function onKeyDown(event) {
    if (!lightboxOpen.value) return;
    if (event.key === 'Escape') closeLightbox();
    if (event.key === 'ArrowRight') nextImage();
    if (event.key === 'ArrowLeft') prevImage();
}

onMounted(() => window.addEventListener('keydown', onKeyDown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeyDown);
});
</script>

<template>
    <SiteLayout :title="pageTitle" :menus="menus" :brand-logos="brandLogos" :show-header="false">
        <main class="font-montserrat bg-[#1a1a1c] text-white">
            <!-- Hero -->
            <section class="relative flex h-[100dvh] min-h-[100dvh] flex-col overflow-visible">
                <video
                    v-if="heroImageUrl && isVideoHero()"
                    :src="heroImageUrl"
                    class="absolute inset-0 h-full w-full object-cover"
                    autoplay
                    muted
                    loop
                    playsinline
                />
                <img
                    v-else-if="heroImageUrl"
                    :src="heroImageUrl"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div v-else class="absolute inset-0 bg-gradient-to-b from-zinc-800 to-zinc-950" />
                <div class="absolute inset-0 bg-black/50" />

                <div class="relative z-10 flex min-h-0 flex-1 flex-col pb-20 md:pb-24">
                    <SiteHeroBannerText
                        :title="heroTitle"
                        :subtitle="heroSubtitleParagraphs[0] || ''"
                        subtitle-wrap
                    >
                        <p
                            v-for="(para, idx) in heroSubtitleParagraphs.slice(1)"
                            :key="`hero-sub-${idx}`"
                            class="hero-subtitle mt-2 hidden font-normal italic text-white/90 md:mt-3 md:block"
                        >
                            {{ para }}
                        </p>
                    </SiteHeroBannerText>
                </div>
                <SiteHeroNavBar :menus="menus" :brand-logos="brandLogos" />
            </section>

            <!-- Alternating blocks — ukuran & tipografi mengikuti Home Blocks (Home.vue) -->
            <section v-if="blocks.length" class="w-full bg-[#2f2f35]">
                <div
                    v-for="(block, idx) in blocks"
                    :key="`hs-block-${idx}`"
                    class="w-full gap-0 md:grid md:grid-cols-2 md:items-stretch"
                >
                    <div
                        class="flex h-full min-h-[360px] flex-col justify-center px-6 py-12 md:min-h-[520px] md:px-10 md:py-16"
                        :class="[
                            block.text_on_left ? 'md:order-1' : 'md:order-2',
                            'min-w-0 bg-[#47474d] text-white',
                        ]"
                    >
                        <h2 v-if="block.title" class="font-montserrat text-[14px] font-bold leading-[1.35]">
                            {{ block.title }}
                        </h2>
                        <p
                            v-if="block.body"
                            class="font-montserrat mt-4 whitespace-pre-wrap text-[14px] font-normal leading-[1.5] text-white/90 sm:mt-5 md:mt-6"
                        >
                            {{ block.body }}
                        </p>
                    </div>
                    <div
                        class="relative h-full min-h-[360px] overflow-hidden bg-black md:min-h-[520px]"
                        :class="block.text_on_left ? 'md:order-2' : 'md:order-1'"
                    >
                        <template v-if="youtubeEmbedSrc(block.video_url)">
                            <iframe
                                :src="youtubeEmbedSrc(block.video_url)"
                                class="absolute inset-0 h-full w-full"
                                title="Video"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            />
                            <div class="pointer-events-none absolute inset-0 bg-black/10" />
                        </template>
                        <template v-else-if="isDirectVideo(block.video_url)">
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
                        <div
                            v-else
                            class="flex h-full min-h-[360px] items-center justify-center bg-zinc-900 text-sm text-white/50 md:min-h-[520px]"
                        >
                            {{ t('noVideoUploaded') }}
                        </div>
                        <div
                            v-if="block.caption"
                            class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/35 px-4"
                        >
                            <p class="text-center text-xs font-semibold uppercase leading-snug tracking-[0.18em] text-white drop-shadow md:text-sm md:tracking-[0.22em]">
                                {{ block.caption }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Collage -->
            <section v-if="collageTiles.length" class="border-t border-white/10 bg-black px-1 py-1 md:px-2 md:py-2">
                <div class="mx-auto max-w-[1920px] columns-2 gap-1 sm:columns-3 md:columns-4 md:gap-2">
                    <button
                        v-for="(tile, ci) in collageTiles"
                        :key="`collage-${ci}`"
                        type="button"
                        class="group relative mb-1 block w-full break-inside-avoid overflow-hidden md:mb-2"
                        @click="openLightbox(tile.src)"
                    >
                        <img
                            :src="tile.src"
                            alt=""
                            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                            :class="tile.aspectClass"
                            loading="lazy"
                        />
                    </button>
                </div>
            </section>

            <!-- Booking & Menu cards -->
            <section
                v-if="bookingCard?.image_url || menuCard?.image_url"
                class="border-t border-white/10 bg-[#2a2a2e] px-4 py-12 md:px-8 md:py-16"
            >
                <div class="mx-auto grid max-w-5xl gap-6 md:grid-cols-2 md:gap-10">
                    <template v-if="bookingCard?.image_url">
                        <a
                            v-if="isExternalHref(bookingCardHref)"
                            :href="bookingCardHref"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group relative block overflow-hidden rounded-lg border border-white/15 bg-black/40 shadow-xl"
                        >
                            <img :src="bookingCard.image_url" alt="" class="aspect-[16/10] w-full object-cover transition duration-300 group-hover:scale-[1.02]" />
                            <div class="absolute inset-x-0 bottom-0 bg-[#4a4a52]/95 py-4 text-center text-sm font-semibold uppercase tracking-[0.2em] text-white">
                                {{ bookingDisplayLabel(bookingCard.label) }}
                            </div>
                        </a>
                        <Link
                            v-else
                            :href="bookingCardHref"
                            class="group relative block overflow-hidden rounded-lg border border-white/15 bg-black/40 shadow-xl"
                        >
                            <img :src="bookingCard.image_url" alt="" class="aspect-[16/10] w-full object-cover transition duration-300 group-hover:scale-[1.02]" />
                            <div class="absolute inset-x-0 bottom-0 bg-[#4a4a52]/95 py-4 text-center text-sm font-semibold uppercase tracking-[0.2em] text-white">
                                {{ bookingDisplayLabel(bookingCard.label) }}
                            </div>
                        </Link>
                    </template>

                    <template v-if="menuCard?.image_url">
                        <a
                            v-if="isExternalHref(menuCardHref)"
                            :href="menuCardHref"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group relative block overflow-hidden rounded-lg border border-white/15 bg-black/40 shadow-xl"
                        >
                            <img :src="menuCard.image_url" alt="" class="aspect-[16/10] w-full object-cover transition duration-300 group-hover:scale-[1.02]" />
                            <div class="absolute inset-x-0 bottom-0 bg-[#4a4a52]/95 py-4 text-center text-sm font-semibold uppercase tracking-[0.2em] text-white">
                                {{ menuCard.label || t('menu') }}
                            </div>
                        </a>
                        <Link
                            v-else
                            :href="menuCardHref"
                            class="group relative block overflow-hidden rounded-lg border border-white/15 bg-black/40 shadow-xl"
                        >
                            <img :src="menuCard.image_url" alt="" class="aspect-[16/10] w-full object-cover transition duration-300 group-hover:scale-[1.02]" />
                            <div class="absolute inset-x-0 bottom-0 bg-[#4a4a52]/95 py-4 text-center text-sm font-semibold uppercase tracking-[0.2em] text-white">
                                {{ menuCard.label || t('menu') }}
                            </div>
                        </Link>
                    </template>
                </div>
            </section>

            <!-- CTA -->
            <section v-if="cta?.label || cta?.url" class="border-t border-white/10 bg-[#1a1a1c] px-6 py-12 text-center md:py-16">
                <a
                    v-if="cta.url && isExternalHref(cta.url)"
                    :href="cta.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex min-w-[260px] items-center justify-center rounded-full bg-[#5c5c66] px-10 py-4 text-sm font-semibold uppercase tracking-[0.2em] text-white shadow-lg transition hover:bg-[#6e6e78]"
                >
                    {{ cta.label || t('reservation') }}
                </a>
                <Link
                    v-else
                    :href="cta.url || '/reservation'"
                    class="inline-flex min-w-[260px] items-center justify-center rounded-full bg-[#5c5c66] px-10 py-4 text-sm font-semibold uppercase tracking-[0.2em] text-white shadow-lg transition hover:bg-[#6e6e78]"
                >
                    {{ cta.label || t('reservation') }}
                </Link>
            </section>

            <section class="border-t border-white/10 bg-[#1a1a1c] px-6 py-10">
                <div class="mx-auto flex max-w-6xl flex-wrap justify-center gap-4">
                    <Link
                        href="/"
                        class="inline-flex items-center gap-2 rounded-full border border-white/25 px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white/90 transition hover:border-white/50 hover:bg-white/10"
                    >
                        <span aria-hidden>←</span>
                        {{ t('backToHome') }}
                    </Link>
                    <Link
                        href="/home-service/menu"
                        class="inline-flex items-center gap-2 rounded-full border border-amber-400/50 bg-amber-400/10 px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-amber-100 transition hover:bg-amber-400/20"
                    >
                        {{ t('homeServiceMenu') }}
                    </Link>
                </div>
            </section>

            <div
                v-if="lightboxOpen"
                class="fixed inset-0 z-[120] flex items-center justify-center bg-black/90 p-4"
                @click.self="closeLightbox"
            >
                <button
                    type="button"
                    class="absolute right-4 top-4 z-10 rounded-full border border-white/30 bg-black/40 px-3 py-1.5 text-sm text-white hover:bg-black/70"
                    aria-label="Close lightbox"
                    @click="closeLightbox"
                >
                    ✕
                </button>
                <button
                    v-if="lightboxImages.length > 1"
                    type="button"
                    class="absolute left-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-white/30 bg-black/40 px-3 py-2 text-xl text-white hover:bg-black/70 md:left-5"
                    aria-label="Previous image"
                    @click="prevImage"
                >
                    ‹
                </button>
                <img
                    :src="currentLightboxImage"
                    alt=""
                    class="max-h-[88vh] w-auto max-w-[94vw] rounded object-contain"
                />
                <button
                    v-if="lightboxImages.length > 1"
                    type="button"
                    class="absolute right-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-white/30 bg-black/40 px-3 py-2 text-xl text-white hover:bg-black/70 md:right-5"
                    aria-label="Next image"
                    @click="nextImage"
                >
                    ›
                </button>
            </div>
        </main>
    </SiteLayout>
</template>
