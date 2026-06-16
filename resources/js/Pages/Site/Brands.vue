<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import SiteHeroNavBar from '@/Components/SiteHeroNavBar.vue';
import SiteHeroBannerText from '@/Components/SiteHeroBannerText.vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    heroImageUrl: { type: String, default: null },
    initialBrand: { type: String, default: '' },
    brands: { type: Array, default: () => [] },
});
const { t } = useSiteI18n();

function normalizeText(input) {
    return String(input || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, ' ');
}

function normalizeKey(input) {
    return normalizeText(input).replace(/\s+/g, '-');
}

function brandLogoKey(logo) {
    return normalizeKey(logo?.slug || logo?.title || '');
}

/** Deteksi keluarga brand dari nama outlet (urutan: spesifik dulu). */
function detectOutletFamily(outletName) {
    const n = normalizeText(outletName);
    if (!n) return null;
    if (n.includes('tempayan')) return 'tempayan';
    if (n.includes('melt')) return 'melt';
    if (n.includes('asian grill') || n.includes('asian-grill') || /\bage\b/.test(n)) return 'asian-grill';
    if (n.includes('burger')) return 'burger';
    if (n.includes('steakhouse') || n.includes('justus')) return 'steakhouse';
    return null;
}

/** Cocokkan keluarga outlet ke key grup dari brandLogos (slug/title CMS, bukan slug hardcoded). */
function resolveOutletBrandKey(outletName, brandLogos) {
    const family = detectOutletFamily(outletName);
    if (!family) return '';

    for (const logo of brandLogos) {
        const key = brandLogoKey(logo);
        if (!key) continue;

        const title = normalizeText(logo?.title || '');
        const slug = normalizeKey(logo?.slug || '');

        if (family === 'tempayan' && (title.includes('tempayan') || slug.includes('tempayan'))) return key;
        if (family === 'melt' && (title.includes('melt') || slug.includes('melt'))) return key;
        if (
            family === 'asian-grill'
            && ((title.includes('asian') && title.includes('grill')) || slug.includes('asian-grill'))
        ) {
            return key;
        }
        if (family === 'burger' && (title.includes('burger') || slug.includes('burger'))) return key;
        if (family === 'steakhouse') {
            const isBurgerBrand = title.includes('burger') || slug.includes('burger');
            const isSteakhouseBrand =
                (title.includes('justus') && title.includes('steakhouse'))
                || slug.includes('steakhouse')
                || (title.includes('justus') && !isBurgerBrand && !title.includes('asian') && !title.includes('melt') && !title.includes('tempayan'));
            if (isSteakhouseBrand) return key;
        }
    }

    return '';
}

function buildMapUrl(outlet) {
    if (outlet?.url_places) return outlet.url_places;
    if (outlet?.lat && outlet?.long) {
        return `https://www.google.com/maps?q=${encodeURIComponent(`${outlet.lat},${outlet.long}`)}`;
    }
    return null;
}

/** Pastikan label tidak bergantung pada teks lama dari API (mis. masih "Speed Wi-fi"). */
const FACILITY_LABEL_BY_KEY = {
    wifi: 'Free Wi-Fi',
    smoking_area: 'Smoking Area',
    mushola: 'Mushola',
    meeting_room: 'Meeting Room',
    valet_parking: 'Free Valet Parking',
    dedicated_event_space: 'Dedicated Event Space',
};

function facilityDisplayLabel(f) {
    const k = String(f?.key || '').trim();
    if (k && FACILITY_LABEL_BY_KEY[k]) {
        return FACILITY_LABEL_BY_KEY[k];
    }
    const legacy = String(f?.name || '');
    if (/speed\s*wi-?fi/i.test(legacy)) {
        return 'Free Wi-Fi';
    }
    return legacy;
}

const selectedBrand = computed(() => normalizeKey(props.initialBrand || ''));

const selectedBrandKey = computed(() => {
    if (!selectedBrand.value) return '';
    const byLogo = props.brandLogos.find((logo) => {
        const slugKey = normalizeKey(logo?.slug || '');
        const titleKey = normalizeKey(logo?.title || '');
        return slugKey === selectedBrand.value || titleKey === selectedBrand.value;
    });
    if (byLogo) {
        return normalizeKey(byLogo.slug || byLogo.title || '');
    }
    return selectedBrand.value;
});

const groups = computed(() => {
    const base = props.brandLogos.map((logo) => ({
        key: normalizeKey(logo?.slug || logo?.title || ''),
        label: String(logo?.title || logo?.slug || '').trim(),
        logo: logo?.logo || null,
        items: [],
    })).filter((g) => g.key);

    const byKey = new Map(base.map((g) => [g.key, g]));
    props.brands.forEach((outlet) => {
        const familyKey = resolveOutletBrandKey(outlet?.name || '', props.brandLogos);
        if (familyKey && byKey.has(familyKey)) {
            byKey.get(familyKey).items.push(outlet);
        }
    });
    return base;
});

const activeKey = ref('');
const lightboxImages = ref([]);
const lightboxIndex = ref(0);

const activeGroup = computed(() => groups.value.find((g) => g.key === activeKey.value) || null);
const activeBrandMeta = computed(() => props.brandLogos.find((logo) => normalizeKey(logo?.slug || logo?.title || '') === activeKey.value) || null);
const heroTitle = computed(() => String(activeBrandMeta.value?.hero_title || '').trim());
const heroSubtitle = computed(() => String(activeBrandMeta.value?.hero_subtitle || '').trim());
const heroMediaUrl = computed(() => String(activeBrandMeta.value?.hero_media_url || props.heroImageUrl || '').trim());
const lightboxOpen = computed(() => lightboxImages.value.length > 0);
const currentLightboxImage = computed(() => lightboxImages.value[lightboxIndex.value] || '');

function isHeroVideo() {
    return /\.(mp4|webm)(\?.*)?$/i.test(heroMediaUrl.value);
}

watch(
    () => [groups.value, selectedBrandKey.value],
    () => {
        if (groups.value.length === 0) {
            activeKey.value = '';
            return;
        }
        const key = selectedBrandKey.value || groups.value[0].key;
        activeKey.value = groups.value.find((g) => g.key === key)?.key || groups.value[0].key;
    },
    { immediate: true },
);

function openLightbox(images) {
    lightboxImages.value = images;
    lightboxIndex.value = 0;
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
    <SiteLayout title="Brands" :menus="menus" :brand-logos="brandLogos" :show-header="false">
        <section class="relative flex h-[100dvh] min-h-[100dvh] flex-col overflow-visible" aria-label="Brand Hero">
            <video
                v-if="heroMediaUrl && isHeroVideo()"
                :src="heroMediaUrl"
                class="absolute inset-0 h-full w-full object-cover"
                autoplay
                muted
                loop
                playsinline
            />
            <img v-else-if="heroMediaUrl" :src="heroMediaUrl" alt="" class="absolute inset-0 h-full w-full object-cover" />
            <div v-else class="absolute inset-0 bg-zinc-900" />
            <div class="absolute inset-0 bg-black/40" />

            <div class="relative z-10 flex min-h-0 flex-1 flex-col pb-20 md:pb-24">
                <SiteHeroBannerText :title="heroTitle" :subtitle="heroSubtitle" />
            </div>

            <SiteHeroNavBar :menus="menus" :brand-logos="brandLogos" />
        </section>

        <section class="border-t border-white/10 bg-[#3f3f43] px-6 py-10">
            <div class="mx-auto flex w-full max-w-6xl flex-nowrap items-center justify-center gap-3 overflow-x-auto px-1 py-1 sm:gap-4 md:gap-5">
                <button
                    v-for="g in groups"
                    :key="g.key"
                    type="button"
                    :title="g.label"
                    @click="activeKey = g.key"
                    class="flex h-[72px] w-[120px] shrink-0 items-center justify-center px-1 transition sm:h-[80px] sm:w-[140px] md:h-[96px] md:w-[180px] lg:w-[210px]"
                    :class="activeKey === g.key ? 'opacity-100' : 'opacity-80 hover:scale-105 hover:opacity-100'"
                >
                    <img v-if="g.logo" :src="g.logo" :alt="g.label" class="h-full w-full object-contain" />
                    <span v-else class="text-center text-sm font-semibold text-white">{{ g.label }}</span>
                </button>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-3 py-4 md:px-6 md:py-6">
            <article
                v-for="(outlet, idx) in activeGroup?.items || []"
                :key="outlet.id"
                class="mb-4 grid overflow-hidden border border-white/20 bg-[#efefef] text-black md:mb-5 md:grid-cols-2"
            >
                <div :class="idx % 2 === 1 ? 'order-2' : 'order-1'">
                    <img
                        v-if="(outlet.gallery && outlet.gallery[0] && outlet.gallery[0].image) || outlet.logo"
                        :src="(outlet.gallery && outlet.gallery[0] && outlet.gallery[0].image) || outlet.logo"
                        :alt="outlet.name"
                        class="h-[260px] w-full object-cover md:h-full"
                    />
                    <div v-else class="flex h-[260px] items-center justify-center bg-zinc-200 text-zinc-500">{{ t('noImage') }}</div>
                </div>
                <div class="flex flex-col justify-between gap-5 px-6 py-5 md:px-8 md:py-7" :class="idx % 2 === 1 ? 'order-1' : 'order-2'">
                    <div>
                        <h2 class="text-xl font-semibold uppercase tracking-[0.04em] md:text-2xl">{{ outlet.name }}</h2>
                        <p v-if="outlet.address" class="mt-2 whitespace-pre-line text-sm font-light leading-7 tracking-[0.01em] md:text-base">
                            {{ outlet.address }}
                        </p>
                    </div>

                    <div v-if="outlet.facility && outlet.facility.length" class="grid grid-cols-2 gap-3">
                        <div v-for="f in outlet.facility.slice(0, 8)" :key="`${outlet.id}-${f.key}`" class="flex items-center gap-2">
                            <img :src="f.image" :alt="f.name" class="h-7 w-7 rounded bg-black object-contain p-1" />
                            <span class="text-[11px] font-light uppercase leading-tight tracking-[0.04em] md:text-xs">{{ facilityDisplayLabel(f) }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-5 border-t border-black/20 pt-3 text-xs font-semibold uppercase tracking-[0.08em] md:text-sm">
                        <button
                            v-if="outlet.gallery && outlet.gallery.length"
                            type="button"
                            class="uppercase hover:underline"
                            @click="openLightbox(outlet.gallery.map((g) => g.image).filter(Boolean))"
                        >
                            {{ t('gallery') }}
                        </button>
                        <a v-if="outlet.pdf_menu" :href="outlet.pdf_menu" target="_blank" rel="noreferrer" class="hover:underline">{{ t('menu') }}</a>
                        <a v-if="buildMapUrl(outlet)" :href="buildMapUrl(outlet)" target="_blank" rel="noreferrer" class="hover:underline">{{ t('googleMap') }}</a>
                    </div>
                </div>
            </article>

            <p v-if="activeGroup && activeGroup.items.length === 0" class="py-12 text-center text-white/70">
                {{ t('noOutletForBrand') }}
            </p>
        </section>

        <div class="pb-10 text-center">
            <Link
                href="/"
                class="inline-flex items-center gap-2 rounded-full border border-white/30 px-5 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white/90 transition hover:border-white/60 hover:text-white"
            >
                <span aria-hidden>←</span>
                {{ t('backToHome') }}
            </Link>
        </div>

        <div
            v-if="lightboxOpen"
            class="fixed inset-0 z-[120] flex items-center justify-center bg-black/85 p-4"
            role="dialog"
            aria-modal="true"
            @click="closeLightbox"
        >
            <button
                type="button"
                class="absolute right-4 top-4 rounded-full border border-white/40 px-3 py-1 text-sm text-white hover:bg-white/10"
                :aria-label="t('close')"
                @click.stop="closeLightbox"
            >
                {{ t('close') }}
            </button>
            <template v-if="lightboxImages.length > 1">
                <button
                    type="button"
                    class="absolute left-4 rounded-full border border-white/40 bg-black/40 px-3 py-2 text-white hover:bg-white/10"
                    aria-label="Previous image"
                    @click.stop="prevImage"
                >
                    ←
                </button>
                <button
                    type="button"
                    class="absolute right-4 rounded-full border border-white/40 bg-black/40 px-3 py-2 text-white hover:bg-white/10"
                    aria-label="Next image"
                    @click.stop="nextImage"
                >
                    →
                </button>
            </template>
            <div class="max-h-[88vh] w-full max-w-5xl" @click.stop>
                <img :src="currentLightboxImage" :alt="`Gallery ${lightboxIndex + 1}`" class="max-h-[82vh] w-full rounded-md object-contain" />
                <p class="mt-3 text-center text-xs text-white/80">
                    {{ lightboxIndex + 1 }} / {{ lightboxImages.length }}
                </p>
            </div>
        </div>
    </SiteLayout>
</template>

