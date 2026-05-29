<script setup>
import SiteNavbar from '@/Components/SiteNavbar.vue';
import { ref } from 'vue';

defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    showHeroMenu: { type: Boolean, default: true },
});

const siteNavRef = ref(null);
</script>

<template>
    <button
        v-if="showHeroMenu"
        type="button"
        class="absolute left-4 top-4 z-[260] inline-flex items-center justify-center rounded-md border border-white/20 bg-black/50 p-2 text-white/90 backdrop-blur-sm transition hover:bg-black/70 hover:text-white md:hidden"
        aria-label="Menu"
        @click="siteNavRef?.toggleMobileMenu()"
    >
        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <div class="absolute inset-x-0 bottom-0 z-[260] w-full border-y border-white/10 bg-black/75 backdrop-blur-md">
        <SiteNavbar
            ref="siteNavRef"
            :menus="menus"
            :brand-logos="brandLogos"
            variant="bar"
            delegate-mobile-menu-button
        />
    </div>
</template>
