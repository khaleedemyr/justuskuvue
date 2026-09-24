<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';
import { normalizeWhatsAppNumber } from '@/lib/reservationHelpers';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    landing: { type: Object, required: true },
});

const { t } = useSiteI18n();
const page = usePage();

const pageTitle = computed(() => {
    const name = String(props.landing?.outlet_name || '').trim();
    return name || 'Outlet';
});

const introParagraphs = computed(() =>
    Array.isArray(props.landing?.intro_paragraphs) ? props.landing.intro_paragraphs.filter(Boolean) : [],
);

const galleryImages = computed(() =>
    Array.isArray(props.landing?.gallery_images) ? props.landing.gallery_images.filter(Boolean) : [],
);

const topGallery = computed(() => galleryImages.value.slice(0, 2));
const bottomGallery = computed(() => galleryImages.value.slice(2, 5));

const bookNowHref = computed(() => {
    const digits = normalizeWhatsAppNumber(String(page.props.reservationCallCenterWa || ''));
    if (!digits) return null;

    const outletName =
        String(props.landing?.outlet_name || props.landing?.brand_name || 'Justus').trim() || 'Justus';
    const prefill = t('outletLandingWhatsAppPrefill').replace('{outlet}', outletName);
    return `https://wa.me/${digits}?text=${encodeURIComponent(prefill)}`;
});

/** Embed Google Maps — pakai API jika ada, fallback dari map_url / alamat outlet. */
const mapEmbedUrl = computed(() => {
    const fromApi = String(props.landing?.map_embed_url || '').trim();
    if (fromApi) return fromApi;

    const mapUrl = String(props.landing?.map_url || '').trim();
    if (mapUrl) {
        try {
            const u = new URL(mapUrl);
            const q = u.searchParams.get('q');
            if (q) {
                return `https://maps.google.com/maps?q=${encodeURIComponent(q)}&z=15&output=embed`;
            }
        } catch {
            // ignore invalid URL
        }
    }

    const lat = String(props.landing?.lat ?? '').trim();
    const lng = String(props.landing?.long ?? props.landing?.lng ?? '').trim();
    if (lat && lng) {
        return `https://maps.google.com/maps?q=${encodeURIComponent(`${lat},${lng}`)}&z=15&output=embed`;
    }

    const address = String(props.landing?.address || '').trim();
    if (address) {
        return `https://maps.google.com/maps?q=${encodeURIComponent(address)}&z=15&output=embed`;
    }

    return null;
});

/** Link langsung ke Google Maps (tab/app baru). */
const mapExternalUrl = computed(() => {
    const fromApi = String(props.landing?.map_url || '').trim();
    if (fromApi) return fromApi;

    const lat = String(props.landing?.lat ?? '').trim();
    const lng = String(props.landing?.long ?? props.landing?.lng ?? '').trim();
    if (lat && lng) {
        return `https://www.google.com/maps?q=${encodeURIComponent(`${lat},${lng}`)}`;
    }

    const address = String(props.landing?.address || '').trim();
    if (address) {
        return `https://www.google.com/maps?q=${encodeURIComponent(address)}`;
    }

    return null;
});

const googleReviews = computed(() => {
    const data = props.landing?.google_reviews;
    if (!data || typeof data !== 'object') return null;
    const rating = Number(data.rating);
    if (!Number.isFinite(rating) || rating <= 0) return null;
    return {
        placeId: String(data.place_id || ''),
        rating,
        userRatingCount: Number(data.user_rating_count) || 0,
        reviews: Array.isArray(data.reviews) ? data.reviews.slice(0, 5) : [],
        writeReviewUrl: String(data.write_review_url || '').trim() || null,
    };
});

function formatRating(value) {
    const n = Number(value);
    if (!Number.isFinite(n)) return '0.0';
    return n.toFixed(1);
}

function starFillPercent(rating, starIndex) {
    const filled = Math.min(Math.max(Number(rating) - (starIndex - 1), 0), 1);
    return `${Math.round(filled * 100)}%`;
}

const lightboxImages = ref([]);
const lightboxIndex = ref(0);
const lightboxOpen = computed(() => lightboxImages.value.length > 0);
const currentLightboxImage = computed(() => lightboxImages.value[lightboxIndex.value] || '');

function openLightbox(startIndex = 0) {
    if (!galleryImages.value.length) return;
    lightboxImages.value = [...galleryImages.value];
    lightboxIndex.value = Math.min(Math.max(startIndex, 0), galleryImages.value.length - 1);
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
onBeforeUnmount(() => window.removeEventListener('keydown', onKeyDown));
</script>

<template>
    <SiteLayout
        :title="pageTitle"
        :menus="menus"
        :brand-logos="brandLogos"
        :show-header="false"
        :show-footer="false"
        shell-class="min-h-screen bg-black text-white"
    >
        <main class="font-montserrat bg-black text-center text-white">
            <div
                v-if="landing.is_preview"
                class="bg-amber-500 px-4 py-2 text-center text-xs font-semibold uppercase tracking-widest text-black"
            >
                Preview Mode — konten belum publik
            </div>

            <!-- Header / intro -->
            <section class="mx-auto max-w-3xl px-6 pb-8 pt-14 text-center md:pb-10 md:pt-20">
                <div v-if="landing.logo_url" class="mb-5 flex justify-center md:mb-6">
                    <img
                        :src="landing.logo_url"
                        :alt="landing.outlet_name"
                        class="h-20 w-20 rounded-full border-2 border-amber-400/80 object-cover md:h-28 md:w-28"
                    />
                </div>
                <p v-if="landing.outlet_name" class="text-xs font-semibold uppercase tracking-[0.2em] text-white/90 md:text-base">
                    {{ landing.outlet_name }}
                </p>

                <h1
                    v-if="landing.headline"
                    class="mt-6 text-base font-bold uppercase leading-snug tracking-[0.06em] text-white md:mt-10 md:text-2xl"
                >
                    {{ landing.headline }}
                </h1>

                <div
                    v-if="introParagraphs.length"
                    class="mt-5 space-y-4 text-sm font-light leading-relaxed text-white/85 md:mt-6 md:text-base"
                >
                    <p v-for="(para, idx) in introParagraphs" :key="`intro-${idx}`">{{ para }}</p>
                </div>
            </section>

            <!-- Hero -->
            <section v-if="landing.hero_image_url" class="w-full">
                <img
                    :src="landing.hero_image_url"
                    :alt="landing.outlet_name"
                    class="mx-auto w-full max-w-5xl object-cover md:max-h-[520px]"
                />
            </section>

            <!-- Secondary + Book Now -->
            <section class="mx-auto max-w-3xl px-6 py-10 text-center md:py-16">
                <p
                    v-if="landing.secondary_paragraph"
                    class="text-sm font-light leading-relaxed text-white/85 md:text-base"
                >
                    {{ landing.secondary_paragraph }}
                </p>
                <a
                    v-if="bookNowHref"
                    :href="bookNowHref"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-8 inline-block text-sm font-bold uppercase tracking-[0.25em] text-white transition hover:text-amber-300 md:mt-10 md:text-base"
                >
                    {{ landing.book_now_label || 'BOOK NOW' }}
                </a>
            </section>

            <!-- Gallery — mobile: full-bleed stack | desktop: 2 + 3 grid -->
            <section v-if="galleryImages.length" class="w-full">
                <!-- Mobile -->
                <div class="flex flex-col md:hidden">
                    <button
                        v-for="(src, idx) in galleryImages"
                        :key="`mobile-g-${idx}`"
                        type="button"
                        class="block w-full cursor-zoom-in border-0 bg-black p-0"
                        @click="openLightbox(idx)"
                    >
                        <img
                            :src="src"
                            :alt="`${landing.outlet_name || 'Outlet'} gallery ${idx + 1}`"
                            class="h-52 w-full object-cover sm:h-60"
                        />
                    </button>
                </div>

                <!-- Desktop -->
                <div class="mx-auto hidden max-w-5xl px-2 md:block md:px-4">
                    <div
                        v-if="topGallery.length"
                        class="grid grid-cols-2 gap-1 md:gap-1.5"
                        :class="topGallery.length === 1 ? 'grid-cols-1' : 'md:grid-cols-[3fr_2fr]'"
                    >
                        <button
                            v-for="(src, idx) in topGallery"
                            :key="`top-${idx}`"
                            type="button"
                            class="block w-full cursor-zoom-in border-0 bg-black p-0"
                            @click="openLightbox(idx)"
                        >
                            <img
                                :src="src"
                                :alt="`${landing.outlet_name || 'Outlet'} gallery ${idx + 1}`"
                                class="h-56 w-full object-cover lg:h-72"
                            />
                        </button>
                    </div>
                    <div
                        v-if="bottomGallery.length"
                        class="mt-1.5 grid gap-1 md:gap-1.5"
                        :class="{
                            'grid-cols-1': bottomGallery.length === 1,
                            'grid-cols-2': bottomGallery.length === 2,
                            'grid-cols-3': bottomGallery.length >= 3,
                        }"
                    >
                        <button
                            v-for="(src, idx) in bottomGallery"
                            :key="`bottom-${idx}`"
                            type="button"
                            class="block w-full cursor-zoom-in border-0 bg-black p-0"
                            :class="bottomGallery.length >= 3 && idx === 1 ? 'row-span-1' : ''"
                            @click="openLightbox(topGallery.length + idx)"
                        >
                            <img
                                :src="src"
                                :alt="`${landing.outlet_name || 'Outlet'} gallery ${topGallery.length + idx + 1}`"
                                class="w-full object-cover"
                                :class="bottomGallery.length >= 3 && idx === 1 ? 'h-64 lg:h-80' : 'h-48 lg:h-56'"
                            />
                        </button>
                    </div>
                </div>
            </section>

            <!-- Address + Map + Google Reviews -->
            <section
                v-if="landing.address || mapEmbedUrl || googleReviews"
                class="border-t border-white/10 px-4 py-10 md:px-6 md:py-16"
            >
                <p
                    v-if="landing.address"
                    class="mx-auto max-w-2xl text-center whitespace-pre-line text-sm font-light leading-relaxed text-white/80 md:text-base"
                >
                    {{ landing.address }}
                </p>
                <div
                    v-if="mapEmbedUrl"
                    class="mx-auto mt-6 w-full max-w-4xl overflow-hidden rounded-lg border border-white/10 bg-zinc-900 md:mt-8"
                >
                    <iframe
                        :src="mapEmbedUrl"
                        class="h-64 w-full border-0 md:h-96"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                        :title="`Peta ${landing.outlet_name || 'outlet'}`"
                    />
                </div>
                <div v-if="mapExternalUrl" class="mx-auto mt-5 max-w-4xl text-center md:mt-6">
                    <a
                        :href="mapExternalUrl"
                        target="_blank"
                        rel="noreferrer noopener"
                        class="inline-flex items-center gap-2 rounded-full border border-white/40 px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:border-amber-400/80 hover:text-amber-300 md:text-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        {{ landing.see_map_label || 'Open in Google Maps' }}
                    </a>
                </div>

                <!-- Google Reviews -->
                <div
                    v-if="googleReviews"
                    class="mx-auto mt-10 max-w-4xl border-t border-white/10 pt-10 text-left md:mt-12 md:pt-12"
                >
                    <div class="flex flex-col items-center gap-3 text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-white/50">
                            Google Reviews
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            <span class="text-4xl font-light tracking-tight text-white md:text-5xl">
                                {{ formatRating(googleReviews.rating) }}
                            </span>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-0.5" aria-hidden="true">
                                    <span
                                        v-for="star in 5"
                                        :key="`hdr-star-${star}`"
                                        class="relative inline-block h-5 w-5 text-white/20"
                                    >
                                        <svg class="absolute inset-0 h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2l2.9 6.9L22 10.3l-5 4.7 1.4 7L12 18.3 5.6 22l1.4-7-5-4.7 7.1-1.4L12 2z" />
                                        </svg>
                                        <span
                                            class="absolute inset-0 overflow-hidden text-amber-400"
                                            :style="{ width: starFillPercent(googleReviews.rating, star) }"
                                        >
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 2l2.9 6.9L22 10.3l-5 4.7 1.4 7L12 18.3 5.6 22l1.4-7-5-4.7 7.1-1.4L12 2z" />
                                            </svg>
                                        </span>
                                    </span>
                                </div>
                                <p v-if="googleReviews.userRatingCount > 0" class="text-xs text-white/55">
                                    {{ googleReviews.userRatingCount.toLocaleString() }} reviews
                                </p>
                            </div>
                        </div>
                    </div>

                    <ul
                        v-if="googleReviews.reviews.length"
                        class="mt-8 space-y-4"
                    >
                        <li
                            v-for="(review, idx) in googleReviews.reviews"
                            :key="`review-${idx}`"
                            class="rounded-lg border border-white/10 bg-white/[0.03] p-4 md:p-5"
                        >
                            <div class="flex items-start gap-3">
                                <img
                                    v-if="review.profile_photo"
                                    :src="review.profile_photo"
                                    :alt="review.author || 'Reviewer'"
                                    class="h-10 w-10 shrink-0 rounded-full object-cover"
                                    loading="lazy"
                                    referrerpolicy="no-referrer"
                                />
                                <div
                                    v-else
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-semibold text-white/70"
                                    aria-hidden="true"
                                >
                                    {{ (review.author || '?').charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <p class="truncate text-sm font-semibold text-white">
                                            {{ review.author || 'Google user' }}
                                        </p>
                                        <p v-if="review.date" class="text-xs text-white/40">
                                            {{ review.date }}
                                        </p>
                                    </div>
                                    <div class="mt-1 flex items-center gap-0.5" aria-label="Rating">
                                        <svg
                                            v-for="star in 5"
                                            :key="`rev-${idx}-star-${star}`"
                                            class="h-3.5 w-3.5"
                                            :class="Number(review.rating) >= star ? 'text-amber-400' : 'text-white/20'"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                            aria-hidden="true"
                                        >
                                            <path d="M12 2l2.9 6.9L22 10.3l-5 4.7 1.4 7L12 18.3 5.6 22l1.4-7-5-4.7 7.1-1.4L12 2z" />
                                        </svg>
                                    </div>
                                    <p
                                        v-if="review.text"
                                        class="mt-2 text-sm font-light leading-relaxed text-white/75"
                                    >
                                        {{ review.text }}
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div v-if="googleReviews.writeReviewUrl" class="mt-8 text-center">
                        <a
                            :href="googleReviews.writeReviewUrl"
                            target="_blank"
                            rel="noreferrer noopener"
                            class="inline-flex items-center gap-2 rounded-full border border-amber-400/70 bg-amber-400/10 px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-amber-300 transition hover:border-amber-300 hover:bg-amber-400/20 hover:text-amber-200 md:text-sm"
                        >
                            Write a Review
                        </a>
                    </div>
                </div>
            </section>

            <!-- Back -->
            <div class="pb-10 text-center md:pb-12">
                <Link
                    href="/brands"
                    class="inline-flex items-center gap-2 rounded-full border border-white/30 px-5 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white/90 transition hover:border-white/60 hover:text-white"
                >
                    <span aria-hidden>←</span>
                    Brands
                </Link>
            </div>
        </main>

        <!-- Lightbox -->
        <div
            v-if="lightboxOpen"
            class="fixed inset-0 z-[120] flex items-center justify-center bg-black/90 p-4"
            role="dialog"
            aria-modal="true"
            @click="closeLightbox"
        >
            <button
                type="button"
                class="absolute right-4 top-4 z-10 rounded-full border border-white/40 px-3 py-1 text-sm text-white hover:bg-white/10"
                :aria-label="t('close')"
                @click.stop="closeLightbox"
            >
                {{ t('close') }}
            </button>
            <template v-if="lightboxImages.length > 1">
                <button
                    type="button"
                    class="absolute left-2 top-1/2 z-10 -translate-y-1/2 rounded-full border border-white/40 bg-black/40 px-3 py-2 text-white hover:bg-white/10 md:left-4"
                    aria-label="Previous image"
                    @click.stop="prevImage"
                >
                    ←
                </button>
                <button
                    type="button"
                    class="absolute right-2 top-1/2 z-10 -translate-y-1/2 rounded-full border border-white/40 bg-black/40 px-3 py-2 text-white hover:bg-white/10 md:right-4"
                    aria-label="Next image"
                    @click.stop="nextImage"
                >
                    →
                </button>
            </template>
            <div class="max-h-[88vh] w-full max-w-5xl" @click.stop>
                <img
                    :src="currentLightboxImage"
                    :alt="`Gallery ${lightboxIndex + 1}`"
                    class="max-h-[82vh] w-full rounded-md object-contain"
                />
                <p v-if="lightboxImages.length > 1" class="mt-3 text-center text-xs text-white/80">
                    {{ lightboxIndex + 1 }} / {{ lightboxImages.length }}
                </p>
            </div>
        </div>
    </SiteLayout>
</template>
