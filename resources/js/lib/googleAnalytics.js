/**
 * Google Tag Manager helpers for Inertia SPA navigations.
 * Initial load is handled by the GTM snippet in app.blade.php.
 * Push virtual page_view events so GTM can fire GA4 on client navigations.
 */

export function getGtmContainerId() {
    if (typeof window === 'undefined') {
        return '';
    }

    return String(window.__GTM_CONTAINER_ID__ || '').trim();
}

export function trackGtmPageView(url) {
    if (!getGtmContainerId() || typeof window === 'undefined') {
        return;
    }

    window.dataLayer = window.dataLayer || [];

    const pagePath =
        typeof url === 'string' && url.length > 0
            ? url
            : `${window.location.pathname}${window.location.search}`;

    window.dataLayer.push({
        event: 'page_view',
        page_path: pagePath,
        page_location: window.location.href,
        page_title: document.title,
    });
}

export function initInertiaGaTracking() {
    if (typeof document === 'undefined' || !getGtmContainerId()) {
        return;
    }

    // First page_view is already sent by GTM on initial load.
    let isFirstNavigation = true;

    document.addEventListener('inertia:navigate', (event) => {
        if (isFirstNavigation) {
            isFirstNavigation = false;
            return;
        }

        const pageUrl = event?.detail?.page?.url;
        // Wait a tick so Inertia can update document.title via <Head>.
        window.setTimeout(() => trackGtmPageView(pageUrl), 0);
    });
}
