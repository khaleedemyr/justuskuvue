<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    item: { type: Object, required: true },
});
const { t } = useSiteI18n();

const shareUrl = ref('');
const copied = ref(false);
const canNativeShare = ref(false);

const shareLinks = computed(() => {
    if (!shareUrl.value) return null;
    const encodedUrl = encodeURIComponent(shareUrl.value);
    const encodedTitle = encodeURIComponent(props.item?.title || "What's On");
    return {
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
        x: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`,
        whatsapp: `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`,
        linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
        telegram: `https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`,
    };
});

async function nativeShare() {
    if (!canNativeShare.value || !shareUrl.value || !navigator.share) return;
    try {
        await navigator.share({
            title: props.item?.title || "What's On",
            text: props.item?.title || "What's On",
            url: shareUrl.value,
        });
    } catch {
        // User canceled share.
    }
}

async function copyLink() {
    if (!shareUrl.value) return;
    try {
        await navigator.clipboard.writeText(shareUrl.value);
        copied.value = true;
        window.setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        // Ignore clipboard failures.
    }
}

onMounted(() => {
    shareUrl.value = window.location.href;
    canNativeShare.value = typeof navigator !== 'undefined' && !!navigator.share;
});
</script>

<template>
    <SiteLayout :title="item.title || `What's On`" :menus="menus" :brand-logos="brandLogos">
        <article class="mx-auto w-full max-w-4xl px-5 py-10">
            <Link href="/whats-on" class="text-sm text-cyan-300 hover:text-cyan-200">← {{ t('backToWhatsOn') }}</Link>
            <h1 class="mt-4 text-3xl font-semibold">{{ item.title }}</h1>
            <p class="mt-2 text-sm text-white/70">{{ item.category_name || '-' }}</p>

            <section class="mt-8 border-t border-white/10 pt-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/45">{{ t('share') }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-if="canNativeShare"
                            type="button"
                            class="rounded-full border border-amber-400/40 bg-amber-400/10 px-4 py-2 text-sm font-medium text-amber-200 transition hover:bg-amber-400/20"
                            @click="nativeShare"
                        >
                            {{ t('shareAction') }}
                        </button>
                        <template v-if="shareLinks">
                            <a :href="shareLinks.facebook" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/20 bg-white/5 px-3 py-1 text-xs hover:border-amber-400/50">Facebook</a>
                            <a :href="shareLinks.x" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/20 bg-white/5 px-3 py-1 text-xs hover:border-amber-400/50">X</a>
                            <a :href="shareLinks.whatsapp" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/20 bg-white/5 px-3 py-1 text-xs hover:border-amber-400/50">WhatsApp</a>
                            <a :href="shareLinks.linkedin" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/20 bg-white/5 px-3 py-1 text-xs hover:border-amber-400/50">LinkedIn</a>
                            <a :href="shareLinks.telegram" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/20 bg-white/5 px-3 py-1 text-xs hover:border-amber-400/50">Telegram</a>
                        </template>
                        <button type="button" class="rounded-full border border-white/20 bg-white/5 px-3 py-1 text-xs hover:border-amber-400/50" @click="copyLink">
                            {{ t('copyLink') }}
                        </button>
                        <span v-if="copied" class="text-xs text-amber-300/90">{{ t('copied') }}</span>
                    </div>
                </div>
            </section>

            <img v-if="item.image" :src="item.image" class="mt-6 h-[360px] w-full rounded-xl object-cover" :alt="item.title" />
            <div class="prose prose-invert mt-8 max-w-none whitespace-pre-wrap text-white/85" v-html="item.content || '-'"></div>
        </article>
    </SiteLayout>
</template>

