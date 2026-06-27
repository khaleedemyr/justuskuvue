import { onBeforeUnmount, onMounted, ref } from 'vue';

/** Match Tailwind `md` breakpoint — mobile-first perf toggles. */
export function useIsMobile(breakpointPx = 768) {
    const isMobile = ref(
    typeof window !== 'undefined' && window.matchMedia('(max-width: 767px)').matches,
);
    let mq = null;
    let handler = null;

    onMounted(() => {
        if (typeof window === 'undefined') {
            return;
        }
        mq = window.matchMedia(`(max-width: ${breakpointPx - 1}px)`);
        handler = () => {
            isMobile.value = mq?.matches ?? false;
        };
        handler();
        mq.addEventListener('change', handler);
    });

    onBeforeUnmount(() => {
        if (mq && handler) {
            mq.removeEventListener('change', handler);
        }
    });

    return isMobile;
}
