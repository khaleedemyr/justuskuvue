<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, toRef, watch } from 'vue';
import {
    brandHref,
    isBrandMenuItem,
    isMobilePinnedNavItem,
    menuToHref,
    useSiteNav,
} from '@/composables/useSiteNav';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    /** header = sticky SiteLayout nav; bar = bottom overlay on hero pages */
    variant: {
        type: String,
        default: 'bar',
        validator: (v) => ['header', 'bar'].includes(v),
    },
    /** When true (e.g. Home nav pinned to top), use full mobile bar instead of bottom-minimal layout. */
    mobileBarAtTop: { type: Boolean, default: false },
    /** Parent renders the hamburger (e.g. Home hero); drawer stays in this component. */
    delegateMobileMenuButton: { type: Boolean, default: false },
});

const mobileOpen = ref(false);
const brandMenuOpen = ref(false);
let brandMenuCloseTimer = null;

const { lang, setLang, navItems, translatedNavItems } = useSiteNav(toRef(props, 'menus'));

const isHeader = computed(() => props.variant === 'header');

/** Mobile bar shows only Brand + Reservation; other links go in hamburger drawer. */
const isMinimalMobileBar = computed(() => !props.mobileBarAtTop);

const desktopNavClass = computed(() =>
    isHeader.value
        ? 'hidden md:flex w-full items-center justify-center gap-x-5 text-base tracking-wide md:gap-x-6 md:text-lg'
        : 'hidden md:flex shrink-0 flex-nowrap items-center gap-x-6 whitespace-nowrap text-[16px] tracking-wide text-white/90',
);

const desktopLinkClass = computed(() =>
    isHeader.value ? 'text-white/90 transition hover:text-white' : 'transition hover:text-white',
);

const mobilePinnedLinkClass =
    'text-[11px] font-medium uppercase tracking-[0.14em] text-white/90 transition hover:text-white sm:text-[12px]';

const navEntries = computed(() =>
    navItems.value.map((item, idx) => ({
        item,
        idx,
        label: translatedNavItems.value[idx],
    })),
);

const mobilePinnedEntries = computed(() => navEntries.value.filter(({ item }) => isMobilePinnedNavItem(item)));

const mobileDrawerEntries = computed(() => navEntries.value.filter(({ item }) => !isMobilePinnedNavItem(item)));

const showInBarMobileHamburger = computed(
    () => !props.delegateMobileMenuButton && (!isMinimalMobileBar.value || isHeader.value),
);

const showFloatingMobileHamburger = computed(
    () => !props.delegateMobileMenuButton && isMinimalMobileBar.value && !isHeader.value,
);

function openBrandMenu() {
    if (brandMenuCloseTimer) {
        clearTimeout(brandMenuCloseTimer);
        brandMenuCloseTimer = null;
    }
    brandMenuOpen.value = true;
}

function scheduleCloseBrandMenu() {
    if (brandMenuCloseTimer) clearTimeout(brandMenuCloseTimer);
    brandMenuCloseTimer = setTimeout(() => {
        brandMenuOpen.value = false;
        brandMenuCloseTimer = null;
    }, 120);
}

function toggleMobileMenu() {
    mobileOpen.value = !mobileOpen.value;
}

function closeMobileMenu() {
    mobileOpen.value = false;
}

defineExpose({
    toggleMobileMenu,
    closeMobileMenu,
    mobileOpen,
});

watch(mobileOpen, (open) => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = open ? 'hidden' : '';
});

onBeforeUnmount(() => {
    if (brandMenuCloseTimer) {
        clearTimeout(brandMenuCloseTimer);
        brandMenuCloseTimer = null;
    }
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <div class="relative w-full">
        <!-- Mobile hamburger fallback (fixed) when not delegated to parent -->
        <button
            v-if="showFloatingMobileHamburger"
            type="button"
            class="fixed left-4 top-4 z-[260] inline-flex items-center justify-center rounded-md border border-white/20 bg-black/50 p-2 text-white/90 backdrop-blur-sm transition hover:bg-black/70 hover:text-white md:hidden"
            :aria-expanded="mobileOpen"
            aria-controls="site-mobile-nav"
            @click="toggleMobileMenu"
        >
            <span class="sr-only">Menu</span>
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <path
                    :class="{ hidden: mobileOpen, 'inline-flex': !mobileOpen }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"
                />
                <path
                    :class="{ hidden: !mobileOpen, 'inline-flex': mobileOpen }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                />
            </svg>
        </button>

        <div
            :class="
                isHeader
                    ? 'relative mx-auto flex w-full max-w-7xl items-center justify-between gap-3 px-4 py-4 md:justify-center'
                    : isMinimalMobileBar
                      ? 'mx-auto flex w-full max-w-7xl items-center justify-center px-4 py-3 sm:px-6 sm:py-4 md:justify-center'
                      : 'mx-auto flex w-full max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 sm:py-4 md:justify-center'
            "
        >
            <!-- Mobile: hamburger (in-bar, e.g. when nav pinned to top) -->
            <button
                v-if="showInBarMobileHamburger"
                type="button"
                class="inline-flex items-center justify-center rounded-md p-2 text-white/90 transition hover:bg-white/10 hover:text-white md:hidden"
                :aria-expanded="mobileOpen"
                aria-controls="site-mobile-nav"
                @click="toggleMobileMenu"
            >
                <span class="sr-only">Menu</span>
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        :class="{ hidden: mobileOpen, 'inline-flex': !mobileOpen }"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                    <path
                        :class="{ hidden: !mobileOpen, 'inline-flex': mobileOpen }"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>

            <!-- Mobile: pinned links (Brand + Reservation) -->
            <nav
                v-if="mobilePinnedEntries.length > 0"
                :class="[
                    'flex min-w-0 items-center justify-between gap-3 md:hidden',
                    isMinimalMobileBar ? 'w-full max-w-lg px-2' : 'flex-1 px-1',
                ]"
                aria-label="Quick links"
            >
                <template v-for="{ item, idx, label } in mobilePinnedEntries" :key="`pinned-${item}`">
                    <Link
                        v-if="isBrandMenuItem(item)"
                        href="/brands"
                        :class="mobilePinnedLinkClass"
                    >
                        {{ label }}
                    </Link>
                    <Link
                        v-else
                        :href="menuToHref(item)"
                        :class="mobilePinnedLinkClass"
                    >
                        {{ label }}
                    </Link>
                </template>
            </nav>

            <!-- Desktop navigation -->
            <nav :class="desktopNavClass" aria-label="Main">
                <template v-for="(item, idx) in navItems" :key="item">
                    <div
                        v-if="isBrandMenuItem(item)"
                        @mouseenter="openBrandMenu"
                        @mouseleave="scheduleCloseBrandMenu"
                    >
                        <Link href="/brands" :class="desktopLinkClass">{{ translatedNavItems[idx] }}</Link>
                    </div>
                    <Link v-else :href="menuToHref(item)" :class="desktopLinkClass">
                        {{ translatedNavItems[idx] }}
                    </Link>
                </template>
            </nav>

            <!-- Language switcher -->
            <div
                class="inline-flex shrink-0 items-center gap-1 rounded-full border border-white/25 bg-black/30 p-1 text-[10px] sm:text-[11px]"
                :class="[
                    isHeader ? '' : 'md:ml-2',
                    isMinimalMobileBar ? 'hidden md:inline-flex' : '',
                ]"
            >
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 transition"
                    :class="lang === 'id' ? 'bg-white/20 text-white' : 'text-white/75 hover:text-white'"
                    @click="setLang('id')"
                >
                    <span aria-hidden>🇮🇩</span> ID
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 transition"
                    :class="lang === 'en' ? 'bg-white/20 text-white' : 'text-white/75 hover:text-white'"
                    @click="setLang('en')"
                >
                    <span aria-hidden>🇬🇧</span> EN
                </button>
            </div>
        </div>

        <!-- Mobile menu: teleport to body so it is not clipped behind hero (overflow-hidden) -->
        <Teleport to="body">
            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-[250] md:hidden"
                role="dialog"
                aria-modal="true"
                aria-labelledby="site-mobile-nav-title"
            >
                <button
                    type="button"
                    class="absolute inset-0 bg-black/75"
                    aria-label="Close menu"
                    @click="closeMobileMenu"
                />
                <div
                    id="site-mobile-nav"
                    class="relative z-[251] max-h-[min(88dvh,100%)] w-full overflow-y-auto border-b border-white/10 bg-[#0f0f12] shadow-2xl"
                >
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <p id="site-mobile-nav-title" class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">
                            Menu
                        </p>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md p-2 text-white/90 transition hover:bg-white/10"
                            aria-label="Close menu"
                            @click="closeMobileMenu"
                        >
                            <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <nav class="flex flex-col px-4 py-3" aria-label="Mobile">
                        <template v-for="{ item, label } in mobileDrawerEntries" :key="`mobile-${item}`">
                            <Link
                                :href="menuToHref(item)"
                                class="border-b border-white/10 py-3 text-sm tracking-wide text-white/90 transition hover:text-white"
                                @click="closeMobileMenu"
                            >
                                {{ label }}
                            </Link>
                        </template>
                        <div v-if="brandLogos.length > 0" class="border-t border-white/10 pt-3">
                            <p class="pb-2 text-xs font-semibold uppercase tracking-wider text-white/50">Brands</p>
                            <div class="grid grid-cols-2 gap-3 pb-2">
                                <Link
                                    v-for="brand in brandLogos"
                                    :key="brand.id"
                                    :href="brandHref(brand)"
                                    class="flex h-16 items-center justify-center rounded-lg bg-white/5 p-2 transition hover:bg-white/10"
                                    @click="closeMobileMenu"
                                >
                                    <img :src="brand.logo" :alt="brand.title || 'Brand Logo'" class="h-full w-full object-contain" />
                                </Link>
                            </div>
                            <Link
                                href="/brands"
                                class="block pb-1 text-xs uppercase tracking-wider text-amber-400/90"
                                @click="closeMobileMenu"
                            >
                                View all brands →
                            </Link>
                        </div>
                        <div
                            v-if="isMinimalMobileBar || delegateMobileMenuButton"
                            class="mt-4 flex items-center justify-center gap-1 rounded-full border border-white/25 bg-black/30 p-1 text-[11px]"
                        >
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 transition"
                                :class="lang === 'id' ? 'bg-white/20 text-white' : 'text-white/75 hover:text-white'"
                                @click="setLang('id')"
                            >
                                <span aria-hidden>🇮🇩</span> ID
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 transition"
                                :class="lang === 'en' ? 'bg-white/20 text-white' : 'text-white/75 hover:text-white'"
                                @click="setLang('en')"
                            >
                                <span aria-hidden>🇬🇧</span> EN
                            </button>
                        </div>
                    </nav>
                </div>
            </div>
        </Teleport>

        <!-- Desktop brand dropdown -->
        <div
            v-if="brandMenuOpen && brandLogos.length > 0"
            class="absolute left-0 right-0 top-full z-[300] hidden bg-[#3f3f43] shadow-xl md:block"
            @mouseenter="openBrandMenu"
            @mouseleave="scheduleCloseBrandMenu"
        >
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-center gap-6 px-6 py-10">
                <Link
                    v-for="brand in brandLogos"
                    :key="brand.id"
                    :href="brandHref(brand)"
                    class="flex h-[80px] w-[160px] items-center justify-center px-1 transition hover:scale-105 md:h-[96px] md:w-[210px]"
                >
                    <img :src="brand.logo" :alt="brand.title || 'Brand Logo'" class="h-full w-full object-contain" />
                </Link>
            </div>
        </div>
    </div>
</template>
