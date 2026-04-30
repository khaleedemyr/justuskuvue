<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    heroImageUrl: { type: String, default: null },
    packages: { type: Array, default: () => [] },
});

const { t } = useSiteI18n();
const activeBrandId = ref(null);

function brandLogoFromPackage(brand) {
    return brand?.logo_cp_url || brand?.thumbnail_url || brand?.image_url || '';
}

const brandTabs = computed(() => {
    const map = new Map();
    props.packages.forEach((pkg) => {
        const brand = pkg?.brand;
        const id = Number(brand?.id || 0);
        if (!id || map.has(id)) return;
        const logo = brandLogoFromPackage(brand);
        if (!logo) return;
        map.set(id, {
            id,
            title: String(brand?.title || '').trim() || `Brand #${id}`,
            logo,
        });
    });
    return Array.from(map.values());
});

watch(
    brandTabs,
    (tabs) => {
        if (!tabs.length) {
            activeBrandId.value = null;
            return;
        }
        if (activeBrandId.value !== null && tabs.some((tab) => tab.id === activeBrandId.value)) return;
        activeBrandId.value = tabs[0].id;
    },
    { immediate: true },
);

const filteredPackages = computed(() => {
    if (activeBrandId.value === null) return [];
    return props.packages.filter((pkg) => Number(pkg?.brand?.id || 0) === activeBrandId.value);
});

const activeBrandTitle = computed(() => brandTabs.value.find((tab) => tab.id === activeBrandId.value)?.title || '');

function renderBodyParts(text) {
    const lines = String(text || '').split(/\r?\n/);
    return lines.map((line) => {
        const parts = [];
        const pattern = /(\*\*[^*]+\*\*)/g;
        let lastIndex = 0;
        let match;
        while ((match = pattern.exec(line)) !== null) {
            if (match.index > lastIndex) {
                parts.push({ type: 'text', text: line.slice(lastIndex, match.index) });
            }
            parts.push({ type: 'bold', text: match[0].slice(2, -2) });
            lastIndex = match.index + match[0].length;
        }
        if (lastIndex < line.length) {
            parts.push({ type: 'text', text: line.slice(lastIndex) });
        }
        if (parts.length === 0) parts.push({ type: 'text', text: '' });
        return parts;
    });
}
</script>

<template>
    <SiteLayout :title="t('homeServiceMenu')" :menus="menus" :brand-logos="brandLogos">
        <main class="min-h-[100dvh] bg-[#1a1a1c] text-white">
            <section class="relative flex min-h-[42vh] flex-col items-center justify-center overflow-hidden px-6 pb-16 pt-28 md:min-h-[48vh] md:pb-20 md:pt-32">
                <img v-if="heroImageUrl" :src="heroImageUrl" alt="" class="absolute inset-0 h-full w-full object-cover" />
                <div v-else class="absolute inset-0 bg-gradient-to-b from-zinc-800 to-zinc-900" />
                <div class="absolute inset-0 bg-black/55" />

                <div class="relative z-10 flex w-full max-w-5xl flex-col items-center text-center">
                    <div class="mb-6 w-full text-left">
                        <Link
                            href="/"
                            class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-black/35 px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white/90 transition hover:border-white/60 hover:bg-black/55 hover:text-white md:text-sm"
                        >
                            <span aria-hidden>←</span>
                            {{ t('backToHome') }}
                        </Link>
                    </div>

                    <h1 class="font-sans text-2xl font-bold uppercase tracking-[0.2em] text-white drop-shadow md:text-4xl md:tracking-[0.28em]">
                        {{ t('homeServiceMenu') }}
                    </h1>

                    <div v-if="brandTabs.length > 0" class="mt-10 flex flex-wrap items-center justify-center gap-6 md:mt-12 md:gap-10">
                        <button
                            v-for="tab in brandTabs"
                            :key="tab.id"
                            type="button"
                            class="group relative flex h-28 w-28 shrink-0 items-center justify-center rounded-full border-4 p-3 shadow-xl transition md:h-36 md:w-36"
                            :class="tab.id === activeBrandId ? 'border-amber-400 bg-black/55 ring-2 ring-amber-400/50' : 'border-white/40 bg-black/45 opacity-90 hover:border-white/70 hover:bg-black/60 hover:opacity-100'"
                            :aria-pressed="tab.id === activeBrandId ? 'true' : 'false'"
                            :aria-label="`${t('showPackageFor')} ${tab.title}`"
                            @click="activeBrandId = tab.id"
                        >
                            <img :src="tab.logo" alt="" class="h-full w-full object-contain" />
                        </button>
                    </div>
                    <p v-else class="mt-8 max-w-md text-sm text-white/70">
                        {{ t('noHomeServicePackages') }}
                    </p>
                </div>
            </section>

            <section
                v-if="activeBrandId !== null && filteredPackages.length > 0"
                class="relative border-t border-white/10 bg-[#141416] px-4 py-12 sm:px-6 md:py-16"
                style="background-image: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(255,255,255,0.02) 2px, rgba(255,255,255,0.02) 4px)"
            >
                <div class="mx-auto max-w-6xl">
                    <p class="mb-8 text-right text-xs font-semibold uppercase tracking-[0.25em] text-white/40">
                        {{ activeBrandTitle }}
                    </p>
                    <div class="grid grid-cols-1 justify-items-center gap-7 md:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="pkg in filteredPackages"
                            :key="pkg.id"
                            class="flex w-full max-w-[20rem] flex-col rounded-[2.25rem] border border-white/10 bg-[#3e3e43]/95 px-6 py-6 text-center shadow-2xl ring-1 ring-white/5 md:max-w-[21rem] md:px-7"
                        >
                            <h2 class="text-sm font-semibold uppercase leading-snug tracking-[0.08em] text-white md:text-base">
                                {{ pkg.title }}
                            </h2>
                            <p v-if="pkg.price_label" class="mt-1 text-xs font-medium text-white/90 md:text-sm">
                                {{ pkg.price_label }}
                            </p>
                            <div class="mx-auto mt-4 h-px w-24 bg-gradient-to-r from-transparent via-white/30 to-transparent" aria-hidden />
                            <div v-if="pkg.body_html" class="home-service-package-body mt-4 flex-1 text-xs leading-relaxed text-white/85 md:text-sm">
                                <div v-for="(lineParts, lineIdx) in renderBodyParts(pkg.body_html)" :key="`line-${pkg.id}-${lineIdx}`" class="min-h-[1.1rem]">
                                    <template v-for="(part, partIdx) in lineParts" :key="`part-${pkg.id}-${lineIdx}-${partIdx}`">
                                        <strong v-if="part.type === 'bold'" class="home-service-section-title">{{ part.text }}</strong>
                                        <span v-else>{{ part.text }}</span>
                                    </template>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>
        </main>
    </SiteLayout>
</template>
