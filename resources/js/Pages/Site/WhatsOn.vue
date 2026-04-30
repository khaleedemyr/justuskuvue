<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    items: { type: Array, default: () => [] },
});
const { t } = useSiteI18n();

function tileClass(index) {
    const pattern = index % 10;
    if (pattern === 0) return 'md:col-span-8 md:row-span-3';
    if (pattern === 1) return 'md:col-span-4 md:row-span-3';
    if (pattern === 2) return 'md:col-span-4 md:row-span-2';
    if (pattern === 3) return 'md:col-span-4 md:row-span-2';
    if (pattern === 4) return 'md:col-span-4 md:row-span-2';
    if (pattern === 5) return 'md:col-span-8 md:row-span-2';
    if (pattern === 6) return 'md:col-span-4 md:row-span-3';
    if (pattern === 7) return 'md:col-span-8 md:row-span-3';
    if (pattern === 8) return 'md:col-span-6 md:row-span-2';
    return 'md:col-span-6 md:row-span-2';
}

function publishedLabel(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

const normalizedItems = computed(() =>
    props.items.map((item) => ({
        ...item,
        publishedLabel: publishedLabel(item?.published_at || null),
        excerpt: String(item?.content || '')
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim(),
    })),
);
</script>

<template>
    <SiteLayout title="What's On" :menus="menus" :brand-logos="brandLogos">
        <section class="mx-auto w-full max-w-7xl px-6 pb-14 pt-12 sm:px-8 md:pt-16">
            <div class="mb-10">
                <h1 class="text-3xl font-semibold uppercase tracking-[0.07em] md:text-5xl">What's On</h1>
              
            </div>

            <div v-if="normalizedItems.length === 0" class="rounded-xl border border-white/10 bg-white/5 p-8 text-center text-white/70">
                Belum ada news yang tersedia.
            </div>

            <div v-else class="grid grid-cols-1 gap-4 md:auto-rows-[110px] md:grid-cols-12 md:gap-5">
                <Link
                    v-for="(item, index) in normalizedItems"
                    :key="item.id"
                    :href="`/news/${item.id}`"
                    class="group relative block overflow-hidden rounded-xl border border-white/15 bg-[#3a3a40] shadow-lg shadow-black/20 transition hover:-translate-y-1 hover:border-white/30 hover:shadow-2xl hover:shadow-black/35"
                    :class="tileClass(index)"
                >
                    <div class="relative h-[220px] w-full overflow-hidden bg-zinc-800 md:h-full">
                        <img
                            v-if="item.image"
                            :src="item.image"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.05]"
                            :alt="item.title"
                            loading="lazy"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center text-sm text-white/45">
                            {{ t('noImage') }}
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent" />
                        <div class="absolute inset-x-0 bottom-0 p-4 md:p-5">
                            <p v-if="item.publishedLabel" class="text-[11px] uppercase tracking-[0.16em] text-amber-400/90">
                                {{ item.publishedLabel }}
                            </p>
                            <h2 class="mt-1 line-clamp-2 text-base font-semibold leading-tight text-white md:text-lg">
                                {{ item.title }}
                            </h2>
                            <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-white/80 md:text-sm">
                                {{ item.excerpt || '\u00a0' }}
                            </p>
                        </div>
                    </div>
                </Link>
            </div>
        </section>
    </SiteLayout>
</template>

