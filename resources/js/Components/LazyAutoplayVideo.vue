<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useIsMobile } from '@/composables/useIsMobile';

const props = defineProps({
    src: { type: String, required: true },
    class: { type: String, default: '' },
    poster: { type: String, default: '' },
    /** Hero: load + autoplay immediately (including mobile). */
    eager: { type: Boolean, default: false },
});

const isMobile = useIsMobile();
const videoRef = ref(null);
const shouldLoad = ref(false);
let observer = null;

function tryPlay() {
    const el = videoRef.value;
    if (!el || !shouldLoad.value) {
        return;
    }
    el.play().catch(() => {});
}

onMounted(() => {
    if (props.eager) {
        shouldLoad.value = true;
        requestAnimationFrame(tryPlay);

        return;
    }

    if (isMobile.value) {
        observer = new IntersectionObserver(
            (entries) => {
                if (!entries.some((e) => e.isIntersecting)) {
                    return;
                }
                shouldLoad.value = true;
                observer?.disconnect();
                observer = null;
                requestAnimationFrame(tryPlay);
            },
            { rootMargin: '120px 0px', threshold: 0.12 },
        );
        if (videoRef.value) {
            observer.observe(videoRef.value);
        }
        return;
    }

    shouldLoad.value = true;
    requestAnimationFrame(tryPlay);
});

onBeforeUnmount(() => {
    observer?.disconnect();
    observer = null;
});
</script>

<template>
    <video
        ref="videoRef"
        :class="props.class"
        :src="shouldLoad ? src : undefined"
        :poster="poster || undefined"
        muted
        loop
        playsinline
        :autoplay="shouldLoad"
        :preload="eager ? 'auto' : 'none'"
    />
</template>
