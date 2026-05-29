<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import SiteHeroNavBar from '@/Components/SiteHeroNavBar.vue';
import SiteHeroBannerText from '@/Components/SiteHeroBannerText.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    pageData: { type: Object, default: () => ({}) },
});

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
function isVideoHero() {
    const byType = String(props.pageData?.hero_image_type || '').toLowerCase() === 'video';
    if (byType) return true;
    return /\.(mp4|webm)(\?.*)?$/i.test(String(props.pageData?.hero_image_url || ''));
}

function isCompanyCultureLine(line) {
    return String(line || '').trim().toUpperCase() === 'COMPANY CULTURE';
}

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

                <div class="relative z-10 flex min-h-0 flex-1 flex-col pb-20 md:pb-24">
                    <SiteHeroBannerText :title="pageData?.title || ''" :subtitle="pageData?.subtitle || ''" />
                </div>
                <SiteHeroNavBar :menus="menus" :brand-logos="brandLogos" />
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
