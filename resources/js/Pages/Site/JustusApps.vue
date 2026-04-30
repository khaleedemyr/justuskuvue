<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { computed } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    pageData: { type: Object, default: () => ({}) },
});

const { t } = useSiteI18n();

const visibleBlocks = computed(() => {
    const blocks = Array.isArray(props.pageData?.blocks) ? [...props.pageData.blocks] : [];
    return blocks
        .sort((a, b) => Number(a?.sort_order || 0) - Number(b?.sort_order || 0))
        .filter((block) => block?.title || block?.body || block?.image_url);
});

function isVideoHero() {
    const image = String(props.pageData?.hero_image_url || '');
    if (!image) return false;
    if (props.pageData?.hero_media_type === 'video') return true;
    return /\.(mp4|webm)(\?.*)?$/i.test(image);
}
</script>

<template>
    <SiteLayout title="Justus Apps" :menus="menus" :brand-logos="brandLogos">
        <main class="block w-full bg-black text-white">
            <section class="relative flex min-h-[100dvh] flex-col items-center justify-center overflow-hidden px-6 pb-16 pt-28 md:pb-20 md:pt-32">
                <video
                    v-if="pageData?.hero_image_url && isVideoHero()"
                    :src="pageData.hero_image_url"
                    class="absolute inset-0 h-full w-full bg-black object-contain object-center md:object-cover"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="auto"
                />
                <img
                    v-else-if="pageData?.hero_image_url"
                    :src="pageData.hero_image_url"
                    class="absolute inset-0 h-full w-full bg-black object-contain object-center md:object-cover"
                    alt="Justus Apps Hero"
                />
                <div v-else class="absolute inset-0 bg-zinc-900" />
                <div class="pointer-events-none absolute inset-0 bg-black/50" />
                <div class="relative z-10 mx-auto flex w-full max-w-7xl flex-1 flex-col items-center justify-center px-6 py-8 text-center sm:py-10" />
            </section>

            <section class="w-full max-w-none bg-[#2f2f35]">
                <div class="flex w-full max-w-none flex-col">
                    <div v-if="visibleBlocks.length === 0" class="w-full py-16 text-center text-white/70">
                        {{ t('noAppBlocks') }}
                    </div>
                    <article
                        v-for="(block, index) in visibleBlocks"
                        v-else
                        :key="block.id"
                        class="grid w-full grid-cols-1 gap-0 md:grid-cols-2 md:items-stretch"
                    >
                        <div class="min-w-0" :class="index % 2 === 0 ? 'md:order-1' : 'md:order-2'">
                            <article class="relative h-full min-h-[260px] w-full min-w-0 overflow-hidden bg-black md:min-h-[340px]">
                                <img
                                    v-if="block.image_url"
                                    :src="block.image_url"
                                    :alt="block.title || 'Justus Apps'"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="flex aspect-video w-full items-center justify-center bg-zinc-900 text-sm text-white/60">
                                    {{ t('memberPhoto') }}
                                </div>
                            </article>
                        </div>
                        <div class="min-w-0" :class="index % 2 === 0 ? 'md:order-2' : 'md:order-1'">
                            <article class="h-full min-h-[260px] w-full min-w-0 bg-[#47474d] text-white md:min-h-[340px]">
                                <div class="flex h-full min-h-0 w-full min-w-0 flex-col justify-center px-6 py-10 md:px-10 md:py-14">
                                    <h3 v-if="block.title" class="text-3xl font-semibold">{{ block.title }}</h3>
                                    <p v-if="block.body" class="mt-6 whitespace-pre-wrap text-xl leading-relaxed text-white/90">
                                        {{ block.body }}
                                    </p>
                                </div>
                            </article>
                        </div>
                    </article>
                </div>
            </section>

            <section class="bg-[#3f3f43] px-6 py-14 text-center">
                <h2 class="text-4xl font-semibold tracking-[0.08em] md:text-5xl">{{ t('experienceMore') }}</h2>
                <p class="mt-2 text-xl tracking-[0.06em] text-white/85 md:text-2xl">{{ t('downloadAppCta') }}</p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    <a
                        v-if="pageData?.playstore_url"
                        :href="pageData.playstore_url"
                        target="_blank"
                        rel="noreferrer"
                        class="transition hover:scale-[1.02]"
                    >
                        <img src="/btn_download_mobile_playstore.png" alt="Get it on Google Play" class="h-[56px] w-auto" />
                    </a>
                    <a
                        v-if="pageData?.appstore_url"
                        :href="pageData.appstore_url"
                        target="_blank"
                        rel="noreferrer"
                        class="transition hover:scale-[1.02]"
                    >
                        <img src="/btn_download_mobile_appstore.png" alt="Download on the App Store" class="h-[56px] w-auto" />
                    </a>
                </div>
            </section>
        </main>
    </SiteLayout>
</template>

