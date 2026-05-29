<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import SiteHeroNavBar from '@/Components/SiteHeroNavBar.vue';
import SiteHeroBannerText from '@/Components/SiteHeroBannerText.vue';
import { computed } from 'vue';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    pageData: { type: Object, default: () => ({}) },
});

const sections = computed(() => (Array.isArray(props.pageData?.sections) ? props.pageData.sections : []));
const storySection = computed(() => sections.value.find((s) => s?.id === 'our-story') || sections.value[0] || null);
const philosophySection = computed(() => sections.value.find((s) => s?.id === 'brand-philosophy') || sections.value[1] || null);
const bottomSection = computed(() => sections.value.find((s) => s?.id === 'vision-mission') || sections.value[2] || null);
</script>

<template>
    <SiteLayout title="About" :menus="menus" :brand-logos="brandLogos" :show-header="false">
        <main class="min-h-[100dvh] bg-[#3f3f43] text-white">
            <section class="relative flex h-[100dvh] min-h-[44vh] flex-col overflow-visible border-b border-white/10 md:h-auto md:min-h-[44vh]">
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

                <div class="relative z-10 flex min-h-0 flex-1 flex-col pb-20 md:pb-24">
                    <SiteHeroBannerText
                        :title="pageData?.title || 'OUR STORY'"
                        :subtitle="pageData?.subtitle || ''"
                    />
                </div>
                <SiteHeroNavBar :menus="menus" :brand-logos="brandLogos" />
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
