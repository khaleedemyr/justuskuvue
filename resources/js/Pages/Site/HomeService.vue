<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    heroImageUrl: { type: String, default: null },
    landing: { type: Object, default: () => ({}) },
});

const { t } = useSiteI18n();

const pageTitle = computed(() => {
    const h = String(props.landing?.hero_title || '').trim();
    return h || t('homeService');
});

const heroTitle = computed(() => String(props.landing?.hero_title || '').trim() || t('homeService'));

const heroSubtitleParagraphs = computed(() => {
    const raw = String(props.landing?.hero_subtitle || '').trim();
    if (!raw) return [];
    return raw.split(/\n\s*\n/).map((p) => p.trim()).filter(Boolean);
});

const blocks = computed(() =>
    Array.isArray(props.landing?.content_blocks) ? props.landing.content_blocks.filter(Boolean) : [],
);

const collageImages = computed(() =>
    Array.isArray(props.landing?.collage_images) ? props.landing.collage_images.filter(Boolean) : [],
);

const galleryCard = computed(() => props.landing?.gallery_card || null);
const menuCard = computed(() => props.landing?.menu_card || null);
const cta = computed(() => props.landing?.cta || null);

const menuCardHref = computed(() => {
    const u = String(menuCard.value?.url || '').trim();
    return u || '/home-service/menu';
});

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
</script>

<template>
    <SiteLayout :title="pageTitle" :menus="menus" :brand-logos="brandLogos">
        <main class="font-montserrat bg-[#1a1a1c] text-white">
            <!-- Hero -->
            <section class="relative flex min-h-[72vh] flex-col overflow-hidden md:min-h-[78vh]">
                <img
                    v-if="heroImageUrl"
                    :src="heroImageUrl"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div v-else class="absolute inset-0 bg-gradient-to-b from-zinc-800 to-zinc-950" />
                <div class="absolute inset-0 bg-black/50" />

                <div class="relative z-10 flex flex-1 flex-col px-5 pb-10 pt-24 md:px-10 md:pb-14 md:pt-28">
                    <div class="flex justify-end">
                        <img src="/logohitam.png" alt="Justus Group" class="h-auto w-[140px] object-contain sm:w-[180px] md:w-[220px]" />
                    </div>

                    <div class="mt-auto flex flex-1 flex-col items-center justify-center text-center">
                        <h1 class="text-3xl font-semibold uppercase tracking-[0.18em] text-white drop-shadow md:text-5xl md:tracking-[0.22em]">
                            {{ heroTitle }}
                        </h1>
                        <div v-if="heroSubtitleParagraphs.length" class="mt-6 max-w-4xl space-y-4 text-sm font-light leading-relaxed text-white/95 md:text-lg md:leading-relaxed">
                            <p v-for="(para, idx) in heroSubtitleParagraphs" :key="idx" class="whitespace-pre-line">
                                {{ para }}
                            </p>
                        </div>
                    </div>
                </div>
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
                                    class="h-full w-full bg-black object-contain object-center md:object-cover"
                                    :src="block.video_url"
                                    controls
                                    playsinline
                                    preload="metadata"
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
            <section v-if="collageImages.length" class="border-t border-white/10 bg-black px-1 py-1 md:px-2 md:py-2">
                <div class="mx-auto grid max-w-[1920px] grid-cols-2 gap-1 sm:grid-cols-3 md:grid-cols-4 md:gap-2">
                    <img
                        v-for="(src, ci) in collageImages"
                        :key="`collage-${ci}`"
                        :src="src"
                        alt=""
                        class="h-36 w-full object-cover sm:h-44 md:h-52"
                        loading="lazy"
                    />
                </div>
            </section>

            <!-- Gallery + Menu cards -->
            <section
                v-if="galleryCard?.image_url || menuCard?.image_url"
                class="border-t border-white/10 bg-[#2a2a2e] px-4 py-12 md:px-8 md:py-16"
            >
                <div class="mx-auto grid max-w-5xl gap-6 md:grid-cols-2 md:gap-10">
                    <template v-if="galleryCard?.image_url">
                        <a
                            v-if="galleryCard.url && isExternalHref(galleryCard.url)"
                            :href="galleryCard.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group relative block overflow-hidden rounded-lg border border-white/15 bg-black/40 shadow-xl"
                        >
                            <img :src="galleryCard.image_url" alt="" class="aspect-[16/10] w-full object-cover transition duration-300 group-hover:scale-[1.02]" />
                            <div class="absolute inset-x-0 bottom-0 bg-[#4a4a52]/95 py-4 text-center text-sm font-semibold uppercase tracking-[0.2em] text-white">
                                {{ galleryCard.label || t('gallery') }}
                            </div>
                        </a>
                        <Link
                            v-else-if="galleryCard.url"
                            :href="galleryCard.url"
                            class="group relative block overflow-hidden rounded-lg border border-white/15 bg-black/40 shadow-xl"
                        >
                            <img :src="galleryCard.image_url" alt="" class="aspect-[16/10] w-full object-cover transition duration-300 group-hover:scale-[1.02]" />
                            <div class="absolute inset-x-0 bottom-0 bg-[#4a4a52]/95 py-4 text-center text-sm font-semibold uppercase tracking-[0.2em] text-white">
                                {{ galleryCard.label || t('gallery') }}
                            </div>
                        </Link>
                        <div
                            v-else
                            class="group relative overflow-hidden rounded-lg border border-white/15 bg-black/40 shadow-xl"
                        >
                            <img :src="galleryCard.image_url" alt="" class="aspect-[16/10] w-full object-cover" />
                            <div class="absolute inset-x-0 bottom-0 bg-[#4a4a52]/95 py-4 text-center text-sm font-semibold uppercase tracking-[0.2em] text-white">
                                {{ galleryCard.label || t('gallery') }}
                            </div>
                        </div>
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
        </main>
    </SiteLayout>
</template>
