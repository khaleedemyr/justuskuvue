<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
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
    <SiteLayout title="About" :menus="menus" :brand-logos="brandLogos">
        <main class="min-h-[100dvh] bg-[#3f3f43] text-white">
            <section class="relative min-h-[44vh] overflow-hidden border-b border-white/10">
                <img
                    v-if="pageData?.hero_image_url"
                    :src="pageData.hero_image_url"
                    alt="About Hero"
                    class="absolute inset-0 h-full w-full object-cover"
                />
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
            </section>

            <section class="mx-auto w-full max-w-7xl px-6 py-10 md:py-12">
                <article v-if="storySection" class="mb-10">
                    <p class="whitespace-pre-line text-2xl leading-relaxed text-white/88 [text-align:justify] [text-justify:inter-word]">
                        {{ storySection.content }}
                    </p>
                </article>

                <article v-if="philosophySection">
                    <img
                        v-if="philosophySection.image_url"
                        :src="philosophySection.image_url"
                        :alt="philosophySection.title"
                        class="h-auto w-full rounded-sm border border-white/10 object-cover"
                    />
                    <h2 class="mt-8 text-4xl font-medium text-white/95">{{ philosophySection.title }}</h2>
                    <p class="mt-4 whitespace-pre-line text-2xl leading-relaxed text-white/88 [text-align:justify] [text-justify:inter-word]">
                        {{ philosophySection.content }}
                    </p>
                </article>
            </section>

            <section
                v-if="bottomSection"
                class="mx-auto grid w-full max-w-7xl grid-cols-1 gap-8 px-6 pb-14 md:grid-cols-[42%_1fr]"
            >
                <div>
                    <img
                        v-if="bottomSection.image_url"
                        :src="bottomSection.image_url"
                        :alt="bottomSection.title"
                        class="h-full min-h-[260px] w-full object-cover"
                    />
                    <div v-else class="flex min-h-[260px] items-center justify-center bg-zinc-900 text-sm text-white/60">
                        {{ t('noImage') }}
                    </div>
                </div>
                <article>
                    <h2 class="text-5xl font-semibold uppercase">{{ bottomSection.title }}</h2>
                    <p v-if="bottomSection.subtitle" class="mt-2 text-2xl text-white/90">{{ bottomSection.subtitle }}</p>
                    <p class="mt-4 whitespace-pre-line text-2xl leading-relaxed text-white/90 [text-align:justify] [text-justify:inter-word]">
                        {{ bottomSection.content }}
                    </p>
                </article>
            </section>
        </main>
    </SiteLayout>
</template>
