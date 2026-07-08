import { computed, ref } from 'vue';
import { reservationEn, reservationId } from '@/i18n/reservationMessages';

const STORAGE_KEY = 'justus-lang';
const LEGACY_STORAGE_KEY = 'justus_lang';
const lang = ref('en');
let initialized = false;

const messages = {
    id: {
        home: 'BERANDA',
        brand: 'BRAND',
        homeService: 'HOME SERVICE',
        justusApps: 'JUSTUS APPS',
        whatsOn: 'INFO TERBARU',
        careers: 'KARIER',
        reservation: 'RESERVASI',
        about: 'TENTANG',
        connect: 'Terhubung',
        craftedGuestJourney: 'Crafted Guest Journey',
        allRightsReserved: 'Semua hak dilindungi.',
        copyright: 'Copyright 2005 JUSTUS Group',
        noBrandLogos: 'Logo brand belum tersedia.',
        noHomeBlocks: 'Belum ada konten home block.',
        noVideoUploaded: 'Video belum diunggah.',
        noImage: 'Tidak ada gambar',
        readMore: 'Baca Selengkapnya',
        backToHome: 'Kembali ke Beranda',
        backToHomeService: 'Kembali ke Home Service',
        backToWhatsOn: "Kembali ke What's On",
        share: 'Bagikan',
        shareAction: 'Bagikan',
        copyLink: 'Salin Link',
        copied: 'Tersalin',
        close: 'Tutup',
        gallery: 'Galeri',
        menu: 'Menu',
        landingPage: 'Landing Page',
        googleMap: 'Google Map',
        noOutletForBrand: 'Belum ada outlet untuk brand ini.',
        careersOpenings: 'Lowongan',
        sourceFromErp: 'Sumber data: job vacancies dari ERP.',
        qualification: 'Kualifikasi',
        joinOurTeam: 'Bergabung dengan Tim Kami',
        newsUpdates: 'Berita & pembaruan',
        homeServiceMenu: 'Menu Home Service',
        noHomeServicePackages: 'Belum ada paket home service.',
        showPackageFor: 'Tampilkan paket',
        experienceMore: 'JELAJAHI LEBIH BANYAK',
        downloadAppCta: 'UNDUH APLIKASI DAN NIKMATI KEUNTUNGAN EKSKLUSIF',
        noAppBlocks: 'Belum ada konten block.',
        memberPhoto: 'MEMBER PHOTO',
        reservationMaintenanceBadge: 'Sedang Dalam Pemeliharaan',
        reservationMaintenanceTitle: 'Layanan Reservasi Online',
        reservationMaintenanceLead:
            'Kami sedang meningkatkan pengalaman reservasi digital Justus Group untuk melayani Anda dengan lebih baik.',
        reservationMaintenanceBody:
            'Selama proses ini, pemesanan meja melalui website untuk sementara tidak tersedia. Tim kami tetap siap membantu reservasi Anda melalui saluran resmi kami.',
        reservationMaintenanceApology:
            'Terima kasih atas pengertian dan kesabaran Anda. Kami menantikan kedatangan Anda.',
        reservationMaintenanceContact: 'Hubungi Kami',
        reservationMaintenanceWhatsAppPrefill:
            'Halo, saya ingin melakukan reservasi. Mohon bantuannya.',
        outletLandingWhatsAppPrefill:
            'Halo, saya ingin reservasi di {outlet}. Mohon bantuannya.',
        ...reservationId,
    },
    en: {
        home: 'HOME',
        brand: 'BRAND',
        homeService: 'HOME SERVICE',
        justusApps: 'JUSTUS APPS',
        whatsOn: "WHAT'S ON",
        careers: 'CAREERS',
        reservation: 'RESERVATION',
        about: 'ABOUT',
        connect: 'Connect',
        craftedGuestJourney: 'Crafted Guest Journey',
        allRightsReserved: 'All rights reserved.',
        copyright: 'Copyright 2005 JUSTUS Group',
        noBrandLogos: 'No brand logos available.',
        noHomeBlocks: 'No home blocks available.',
        noVideoUploaded: 'No video uploaded.',
        noImage: 'No image',
        readMore: 'Read More',
        backToHome: 'Back to Home',
        backToHomeService: 'Back to Home Service',
        backToWhatsOn: "Back to What's On",
        share: 'Share',
        shareAction: 'Share',
        copyLink: 'Copy Link',
        copied: 'Copied',
        close: 'Close',
        gallery: 'Gallery',
        menu: 'Menu',
        landingPage: 'Landing Page',
        googleMap: 'Google Map',
        noOutletForBrand: 'No outlet available for this brand.',
        careersOpenings: 'Openings',
        sourceFromErp: 'Data source: ERP job vacancies.',
        qualification: 'Qualification',
        joinOurTeam: 'Join Our Team',
        newsUpdates: 'News & updates',
        homeServiceMenu: 'Home Service Menu',
        noHomeServicePackages: 'No home service packages available.',
        showPackageFor: 'Show package',
        experienceMore: 'EXPERIENCE MORE',
        downloadAppCta: 'DOWNLOAD THE APP AND ENJOY EXCLUSIVE PRIVILEGES',
        noAppBlocks: 'No block content available.',
        memberPhoto: 'MEMBER PHOTO',
        reservationMaintenanceBadge: 'Under Maintenance',
        reservationMaintenanceTitle: 'Online Reservation Service',
        reservationMaintenanceLead:
            'We are currently enhancing the Justus Group digital reservation experience to serve you better.',
        reservationMaintenanceBody:
            'During this period, table reservations via our website are temporarily unavailable. Our team remains ready to assist you through our official channels.',
        reservationMaintenanceApology:
            'Thank you for your understanding and patience. We look forward to welcoming you.',
        reservationMaintenanceContact: 'Contact Us',
        reservationMaintenanceWhatsAppPrefill:
            'Hello, I would like to make a reservation. Could you please assist me?',
        outletLandingWhatsAppPrefill:
            'Hello, I would like to make a reservation at {outlet}. Could you please assist me?',
        ...reservationEn,
    },
};

function initializeLang() {
    if (initialized || typeof window === 'undefined') return;
    initialized = true;
    try {
        const saved = window.localStorage.getItem(STORAGE_KEY) || window.localStorage.getItem(LEGACY_STORAGE_KEY);
        if (saved === 'id' || saved === 'en') {
            lang.value = saved;
        }
    } catch {
        // Ignore storage errors.
    }
}

export function useSiteI18n() {
    initializeLang();

    function setLang(nextLang) {
        lang.value = nextLang === 'en' ? 'en' : 'id';
        if (typeof window !== 'undefined') {
            try {
                window.localStorage.setItem(STORAGE_KEY, lang.value);
            } catch {
                // Ignore storage errors.
            }
        }
    }

    function t(key) {
        const active = messages[lang.value] || messages.id;
        return active[key] || messages.en[key] || key;
    }

    function translateMenuLabel(menu) {
        const n = String(menu || '').trim().toUpperCase().replace(/`/g, "'");
        if (n === 'HOME') return t('home');
        if (n.includes('BRAND')) return t('brand');
        if (n.includes('HOME SERVICE')) return t('homeService');
        if (n.includes('JUSTUS APPS') || n.includes('JUSTUST APPS')) return t('justusApps');
        if (n.includes("WHAT'S ON")) return t('whatsOn');
        if (n.includes('CAREERS')) return t('careers');
        if (n.includes('RESERVATION')) return t('reservation');
        if (n.includes('ABOUT')) return t('about');
        return menu;
    }

    const isEnglish = computed(() => lang.value === 'en');

    return {
        lang,
        isEnglish,
        setLang,
        t,
        translateMenuLabel,
    };
}
