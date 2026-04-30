<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
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
</script>

<template>
    <SiteLayout
        title="Careers"
        shell-class="min-h-screen bg-[#3f3f43] text-white"
        :menus="menus"
        :brand-logos="brandLogos"
    >
        <main class="min-h-[100dvh] bg-[#3f3f43] text-white">
            <section class="relative min-h-[64vh] overflow-hidden border-b border-white/10 md:min-h-[72vh]">
                <template v-if="pageData?.hero_image_url">
                    <img
                        :src="pageData.hero_image_url"
                        alt=""
                        aria-hidden="true"
                        class="absolute inset-0 h-full w-full object-cover object-center opacity-45 blur-sm scale-110 md:hidden"
                    />
                    <img
                        :src="pageData.hero_image_url"
                        alt="Careers Hero"
                        class="absolute inset-0 h-full w-full object-contain object-center md:object-cover"
                    />
                </template>
                <div v-else class="absolute inset-0 bg-zinc-900" />
                <div class="absolute inset-0 bg-black/45" />
                <div
                    class="relative z-10 mx-auto flex min-h-[64vh] w-full max-w-7xl flex-col items-center justify-center px-6 py-12 text-center md:min-h-[72vh]"
                >
                    <h1 class="text-4xl font-semibold tracking-[0.12em] md:text-6xl">{{ pageData?.title }}</h1>
                    <p class="mt-3 text-xl italic text-white/90 md:text-4xl">{{ pageData?.subtitle }}</p>
                </div>
            </section>

            <section class="mx-auto w-full max-w-7xl px-6 py-12 text-center md:py-16">
                <p class="mx-auto max-w-5xl whitespace-pre-line text-lg leading-relaxed text-white/90 md:text-[2rem]">
                    {{ pageData?.wording }}
                </p>
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
                <h2 class="whitespace-pre-line text-2xl font-semibold uppercase tracking-[0.06em] md:text-4xl">
                    {{ pageData?.cta_title }}
                </h2>
                <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <template v-if="pageData?.primary_button_label">
                        <a
                            v-if="primaryIsExternal"
                            :href="pageData.primary_button_url || '#'"
                            target="_blank"
                            rel="noreferrer"
                            class="flex min-h-[120px] items-center justify-center rounded-[1.75rem] bg-[#efefef] px-6 py-4 text-center text-3xl font-semibold text-[#111118] transition hover:scale-[1.01]"
                        >
                            <span class="whitespace-pre-line">{{ pageData.primary_button_label }}</span>
                        </a>
                        <Link
                            v-else
                            :href="pageData.primary_button_url || '#'"
                            class="flex min-h-[120px] items-center justify-center rounded-[1.75rem] bg-[#efefef] px-6 py-4 text-center text-3xl font-semibold text-[#111118] transition hover:scale-[1.01]"
                        >
                            <span class="whitespace-pre-line">{{ pageData.primary_button_label }}</span>
                        </Link>
                    </template>
                    <template v-if="pageData?.secondary_button_label">
                        <a
                            v-if="secondaryIsExternal"
                            :href="pageData.secondary_button_url || '#'"
                            target="_blank"
                            rel="noreferrer"
                            class="flex min-h-[120px] items-center justify-center rounded-[1.75rem] bg-[#efefef] px-6 py-4 text-center text-3xl font-semibold text-[#111118] transition hover:scale-[1.01]"
                        >
                            <span class="whitespace-pre-line">{{ pageData.secondary_button_label }}</span>
                        </a>
                        <Link
                            v-else
                            :href="pageData.secondary_button_url || '#'"
                            class="flex min-h-[120px] items-center justify-center rounded-[1.75rem] bg-[#efefef] px-6 py-4 text-center text-3xl font-semibold text-[#111118] transition hover:scale-[1.01]"
                        >
                            <span class="whitespace-pre-line">{{ pageData.secondary_button_label }}</span>
                        </Link>
                    </template>
                </div>
            </section>
        </main>
    </SiteLayout>
</template>
