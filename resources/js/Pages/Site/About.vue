<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import SiteNavbar from '@/Components/SiteNavbar.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    pageData: { type: Object, default: () => ({}) },
});

const { t } = useSiteI18n();

const sections = computed(() => (Array.isArray(props.pageData?.sections) ? props.pageData.sections : []));
const storySection = computed(() => sections.value.find((s) => s?.id === 'our-story') || sections.value[0] || null);
const philosophySection = computed(() => sections.value.find((s) => s?.id === 'brand-philosophy') || sections.value[1] || null);
const bottomSection = computed(() => sections.value.find((s) => s?.id === 'vision-mission') || sections.value[2] || null);
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
                    <SiteNavbar :menus="menus" :brand-logos="brandLogos" variant="bar" />
                </div>
            </section>

            <section v-if="storySection" class="border-b border-white/10 bg-[#3f3f43] px-6 py-14 md:px-10 md:py-20">
                <div class="mx-auto grid max-w-7xl gap-10 md:grid-cols-2 md:items-center md:gap-16">
                    <div>
                        <h2 class="text-2xl font-semibold uppercase tracking-[0.12em] md:text-4xl">{{ storySection.title }}</h2>
                        <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-white/85 md:text-base">{{ storySection.body }}</p>
                    </div>
                    <div v-if="storySection.image_url" class="overflow-hidden rounded-2xl">
                        <img :src="storySection.image_url" :alt="storySection.title || 'Our Story'" class="h-full w-full object-cover" />
                    </div>
                </div>
            </section>

            <section v-if="philosophySection" class="border-b border-white/10 bg-[#2f2f35] px-6 py-14 md:px-10 md:py-20">
                <div class="mx-auto grid max-w-7xl gap-10 md:grid-cols-2 md:items-center md:gap-16">
                    <div v-if="philosophySection.image_url" class="order-2 overflow-hidden rounded-2xl md:order-1">
                        <img :src="philosophySection.image_url" :alt="philosophySection.title || 'Philosophy'" class="h-full w-full object-cover" />
                    </div>
                    <div class="order-1 md:order-2">
                        <h2 class="text-2xl font-semibold uppercase tracking-[0.12em] md:text-4xl">{{ philosophySection.title }}</h2>
                        <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-white/85 md:text-base">{{ philosophySection.body }}</p>
                    </div>
                </div>
            </section>

            <section v-if="bottomSection" class="bg-[#3f3f43] px-6 py-14 md:px-10 md:py-20">
                <div class="mx-auto max-w-7xl text-center">
                    <h2 class="text-2xl font-semibold uppercase tracking-[0.12em] md:text-4xl">{{ bottomSection.title }}</h2>
                    <p class="mx-auto mt-4 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-white/85 md:text-base">{{ bottomSection.body }}</p>
                    <div v-if="bottomSection.image_url" class="mx-auto mt-10 max-w-4xl overflow-hidden rounded-2xl">
                        <img :src="bottomSection.image_url" :alt="bottomSection.title || 'Vision Mission'" class="h-full w-full object-cover" />
                    </div>
                </div>
            </section>
        </main>
    </SiteLayout>
</template>
