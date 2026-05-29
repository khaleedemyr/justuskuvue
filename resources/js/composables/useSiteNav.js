import { computed } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';

const DEFAULT_MENUS = ['HOME', 'BRAND', 'HOME SERVICE', 'JUSTUS APPS', "WHAT'S ON", 'CAREERS', 'RESERVATION', 'ABOUT'];

export function menuToHref(label) {
    const key = String(label || '').trim().toUpperCase();
    if (key === 'HOME') return '/';
    if (key.includes('HOME SERVICE')) return '/home-service';
    if (key === 'BRAND') return '/brands';
    if (key === 'JUSTUS APPS') return '/justus-apps';
    if (key === "WHAT'S ON") return '/whats-on';
    if (key === 'CAREERS') return '/careers';
    if (key === 'RESERVATION') return '/reservation';
    if (key === 'ABOUT') return '/about';
    return '#';
}

export function brandHref(brand) {
    const key = String(brand?.slug || brand?.title || '').trim();
    return key ? `/brands?brand=${encodeURIComponent(key)}` : '/brands';
}

export function isBrandMenuItem(item) {
    return String(item || '').trim().toUpperCase().includes('BRAND');
}

export function isReservationMenuItem(item) {
    return String(item || '').trim().toUpperCase() === 'RESERVATION';
}

/** Always visible on mobile navbar (not tucked in hamburger). */
export function isMobilePinnedNavItem(item) {
    return isBrandMenuItem(item) || isReservationMenuItem(item);
}

export function useSiteNav(menusSource) {
    const { lang, setLang, translateMenuLabel } = useSiteI18n();

    const navItems = computed(() => {
        const menus = menusSource?.value ?? menusSource ?? [];
        if (Array.isArray(menus) && menus.length > 0) {
            return menus;
        }
        return DEFAULT_MENUS;
    });

    const translatedNavItems = computed(() => navItems.value.map((item) => translateMenuLabel(item)));

    return {
        lang,
        setLang,
        navItems,
        translatedNavItems,
        menuToHref,
        brandHref,
        isBrandMenuItem,
    };
}
