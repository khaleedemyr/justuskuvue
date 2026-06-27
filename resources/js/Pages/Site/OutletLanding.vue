<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    landing: { type: Object, required: true },
});

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
    const id = props.landing?.book_now_outlet_id;
    if (!id) return '/reservation/arrange';
    return `/reservation/arrange?outlet_id=${encodeURIComponent(String(id))}`;
});
</script>

<template>
    <SiteLayout :title="pageTitle" :menus="menus" :brand-logos="brandLogos" :show-header="false">
        <main class="font-montserrat bg-black text-white">
            <div
                v-if="landing.is_preview"
                class="bg-amber-500 px-4 py-2 text-center text-xs font-semibold uppercase tracking-widest text-black"
            >
                Preview Mode — konten belum publik
            </div>
            <!-- Header / intro -->
            <section class="mx-auto max-w-3xl px-6 pb-10 pt-16 text-center md:pt-20">
                <div v-if="landing.logo_url" class="mb-6 flex justify-center">
                    <img
                        :src="landing.logo_url"
                        :alt="landing.outlet_name"
                        class="h-24 w-24 rounded-full border-2 border-amber-400/80 object-cover md:h-28 md:w-28"
                    />
                </div>
                <p v-if="landing.outlet_name" class="text-sm font-semibold uppercase tracking-[0.2em] text-white/90 md:text-base">
                    {{ landing.outlet_name }}
                </p>

                <h1
                    v-if="landing.headline"
                    class="mt-8 text-lg font-bold uppercase leading-snug tracking-[0.06em] text-white md:mt-10 md:text-2xl"
                >
                    {{ landing.headline }}
                </h1>

                <div v-if="introParagraphs.length" class="mt-6 space-y-4 text-left text-sm font-light leading-relaxed text-white/85 md:text-center md:text-base">
                    <p v-for="(para, idx) in introParagraphs" :key="`intro-${idx}`">{{ para }}</p>
                </div>
            </section>

            <!-- Hero -->
            <section v-if="landing.hero_image_url" class="w-full">
                <img
                    :src="landing.hero_image_url"
                    :alt="landing.outlet_name"
                    class="mx-auto max-h-[420px] w-full max-w-5xl object-cover px-0 md:max-h-[520px]"
                />
            </section>

            <!-- Secondary + Book Now -->
            <section class="mx-auto max-w-3xl px-6 py-12 text-center md:py-16">
                <p
                    v-if="landing.secondary_paragraph"
                    class="text-sm font-light leading-relaxed text-white/85 md:text-base"
                >
                    {{ landing.secondary_paragraph }}
                </p>
                <Link
                    :href="bookNowHref"
                    class="mt-10 inline-block text-sm font-bold uppercase tracking-[0.25em] text-white transition hover:text-amber-300 md:text-base"
                >
                    {{ landing.book_now_label || 'BOOK NOW' }}
                </Link>
            </section>

            <!-- Gallery -->
            <section v-if="galleryImages.length" class="mx-auto max-w-5xl px-4 pb-12 md:px-6">
                <div v-if="topGallery.length" class="grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-4">
                    <img
                        v-for="(src, idx) in topGallery"
                        :key="`top-${idx}`"
                        :src="src"
                        alt=""
                        class="h-48 w-full object-cover md:h-64"
                    />
                </div>
                <div v-if="bottomGallery.length" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3 md:mt-4 md:gap-4">
                    <img
                        v-for="(src, idx) in bottomGallery"
                        :key="`bottom-${idx}`"
                        :src="src"
                        alt=""
                        class="h-40 w-full object-cover md:h-48"
                    />
                </div>
            </section>

            <!-- Address + Map -->
            <section class="border-t border-white/10 px-6 py-12 text-center md:py-16">
                <p v-if="landing.address" class="mx-auto max-w-2xl whitespace-pre-line text-sm font-light leading-relaxed text-white/80 md:text-base">
                    {{ landing.address }}
                </p>
                <a
                    v-if="landing.map_url"
                    :href="landing.map_url"
                    target="_blank"
                    rel="noreferrer"
                    class="mt-8 inline-block text-sm font-bold uppercase tracking-[0.25em] text-white transition hover:text-amber-300 md:text-base"
                >
                    {{ landing.see_map_label || 'SEE MAP' }}
                </a>
            </section>

            <!-- Back -->
            <div class="pb-12 text-center">
                <Link
                    href="/brands"
                    class="inline-flex items-center gap-2 rounded-full border border-white/30 px-5 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white/90 transition hover:border-white/60 hover:text-white"
                >
                    <span aria-hidden>←</span>
                    Brands
                </Link>
            </div>
        </main>
    </SiteLayout>
</template>
