/** Table hold / dining block length (must match ERP DEFAULT_RESERVATION_DURATION_MINUTES). */
export const RESERVATION_BLOCK_MINUTES = 150;

/** Same-day booking: first allowed slot is this many hours from now (must match ERP). */
export const SAME_DAY_LEAD_HOURS = 4;

export const DATE_PICKER_RANGE_DAYS = 60;

export function buildTimeSlots() {
    const slots = [];
    for (let h = 10; h <= 22; h += 1) {
        for (const m of [0, 30]) {
            if (h === 22 && m > 0) continue;
            slots.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`);
        }
    }
    return slots;
}

export function formatLocalDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

export function parseSlotToMinutes(slot) {
    const [h, m] = slot.split(':').map(Number);
    if (!Number.isFinite(h) || !Number.isFinite(m)) return -1;
    return h * 60 + m;
}

/** Earliest 30-minute slot boundary at or after (now + SAME_DAY_LEAD_HOURS). */
export function getSameDayMinimumSlotMinutes() {
    const min = new Date(Date.now() + SAME_DAY_LEAD_HOURS * 60 * 60 * 1000);
    const total = min.getHours() * 60 + min.getMinutes();
    const rounded = Math.ceil(total / 30) * 30;
    return Math.min(rounded, 24 * 60);
}

export function filterSlotsForDate(allSlots, dateStr) {
    const today = formatLocalDate(new Date());
    if (dateStr !== today) return allSlots;
    const minM = getSameDayMinimumSlotMinutes();
    return allSlots.filter((s) => parseSlotToMinutes(s) >= minM);
}

export function buildDatePickerDays(lang) {
    const locale = lang === 'id' ? 'id-ID' : 'en-US';
    const start = new Date();
    const todayStr = formatLocalDate(start);
    const rows = [];
    for (let i = 0; i <= DATE_PICKER_RANGE_DAYS; i += 1) {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        const value = formatLocalDate(d);
        const isToday = value === todayStr;
        rows.push({
            value,
            isToday,
            dayLabel: isToday ? (lang === 'id' ? 'Hari ini' : 'Today') : d.toLocaleDateString(locale, { weekday: 'short' }),
            dateLine: d.toLocaleDateString(locale, {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            }),
        });
    }
    return rows;
}

export function normalizeWhatsAppNumber(raw) {
    const digits = String(raw || '').replace(/\D/g, '');
    if (!digits) return '';
    if (digits.startsWith('62')) return digits;
    if (digits.startsWith('0')) return `62${digits.slice(1)}`;
    return digits;
}

export function buildStorageImageUrl(baseApiUrl, imagePath) {
    if (!imagePath) return '';
    const path = String(imagePath).trim();
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    const webBase = String(baseApiUrl).replace(/\/api\/?$/i, '');
    return `${webBase}/storage/${path.replace(/^\/+/, '')}`;
}

export function getTableSeatingCapacity(table) {
    if ((table.tipe || 'biasa') !== 'biasa') return 0;
    const value =
        typeof table.seating_capacity === 'number'
            ? table.seating_capacity
            : Number(table.jumlah_kursi || 0);
    return Number.isFinite(value) && value > 0 ? value : 0;
}

export function sumSeatCapacityForTableIds(ids, layout) {
    if (ids.length === 0) return 0;
    const all = Object.values(layout.tables_by_section).flat();
    return ids.reduce((sum, id) => {
        const row = all.find((t) => Number(t.source_table_id) === Number(id));
        return sum + (row ? getTableSeatingCapacity(row) : 0);
    }, 0);
}

export function renderTableSvg(table) {
    const warna = table.warna || '#2563eb';
    const tipe = table.tipe || 'biasa';
    const bentuk = table.bentuk || 'round';
    const orientasi = table.orientasi || 'horizontal';
    const jumlahKursi = Math.max(1, Number(table.jumlah_kursi || 4));

    if (tipe === 'takeaway') {
        return `
      <svg width="80" height="80" viewBox="0 0 80 80">
        <rect x="20" y="30" width="40" height="32" rx="8" fill="#fbbf24" stroke="#b45309" stroke-width="3" />
        <rect x="28" y="38" width="24" height="16" rx="4" fill="#fde68a" />
        <rect x="32" y="24" width="16" height="12" rx="4" fill="#fbbf24" stroke="#b45309" stroke-width="2" />
        <rect x="36" y="18" width="8" height="8" rx="2" fill="#fde68a" />
      </svg>
    `;
    }

    if (tipe === 'ojol') {
        return `
      <svg width="80" height="80" viewBox="0 0 80 80">
        <ellipse cx="40" cy="54" rx="22" ry="10" fill="#2563eb" />
        <rect x="28" y="44" width="24" height="12" rx="4" fill="#fbbf24" stroke="#b45309" stroke-width="2" />
        <circle cx="32" cy="62" r="6" fill="#222" />
        <circle cx="48" cy="62" r="6" fill="#222" />
        <ellipse cx="40" cy="40" rx="14" ry="10" fill="#a3e635" stroke="#2563eb" stroke-width="2" />
      </svg>
    `;
    }

    if (bentuk === 'round') {
        let chairs = '';
        for (let i = 0; i < jumlahKursi; i += 1) {
            const angle = (2 * Math.PI * i) / jumlahKursi;
            const x = 40 + Math.cos(angle) * 32;
            const y = 40 + Math.sin(angle) * 32;
            chairs += `<circle cx="${x}" cy="${y}" r="10" fill="#fbbf24" stroke="#b45309" stroke-width="1.5" />`;
        }

        return `
      <svg width="80" height="80" viewBox="0 0 80 80">
        ${chairs}
        <circle cx="40" cy="40" r="28" fill="${warna}" stroke="#0f172a" stroke-width="2" />
      </svg>
    `;
    }

    const kursiA = Math.ceil(jumlahKursi / 2);
    const kursiB = Math.floor(jumlahKursi / 2);
    const kursiMax = Math.max(kursiA, kursiB);

    let mejaW = 0;
    let mejaH = 0;
    let svgW = 0;
    let svgH = 0;
    let mx = 0;
    let my = 0;
    let chairs = '';

    if (orientasi === 'vertical') {
        mejaW = 32;
        mejaH = 32 + kursiMax * 16;
        svgW = mejaW + 60;
        svgH = mejaH + 40;
        mx = svgW / 2 - mejaW / 2;
        my = svgH / 2 - mejaH / 2;

        for (let i = 0; i < kursiA; i += 1) {
            const y = my + (mejaH / (kursiA + 1)) * (i + 1);
            chairs += `<rect x="${mx - 18}" y="${y - 8}" width="16" height="16" rx="4" fill="#fbbf24" stroke="#b45309" stroke-width="1.5" />`;
        }

        for (let i = 0; i < kursiB; i += 1) {
            const y = my + (mejaH / (kursiB + 1)) * (i + 1);
            chairs += `<rect x="${mx + mejaW + 2}" y="${y - 8}" width="16" height="16" rx="4" fill="#fbbf24" stroke="#b45309" stroke-width="1.5" />`;
        }
    } else {
        mejaW = 32 + kursiMax * 16;
        mejaH = 32;
        svgW = mejaW + 40;
        svgH = mejaH + 60;
        mx = svgW / 2 - mejaW / 2;
        my = svgH / 2 - mejaH / 2;

        for (let i = 0; i < kursiA; i += 1) {
            const x = mx + (mejaW / (kursiA + 1)) * (i + 1);
            chairs += `<rect x="${x - 8}" y="${my - 18}" width="16" height="16" rx="4" fill="#fbbf24" stroke="#b45309" stroke-width="1.5" />`;
        }

        for (let i = 0; i < kursiB; i += 1) {
            const x = mx + (mejaW / (kursiB + 1)) * (i + 1);
            chairs += `<rect x="${x - 8}" y="${my + mejaH + 2}" width="16" height="16" rx="4" fill="#fbbf24" stroke="#b45309" stroke-width="1.5" />`;
        }
    }

    return `
    <svg width="${svgW}" height="${svgH}" viewBox="0 0 ${svgW} ${svgH}">
      ${chairs}
      <rect x="${mx}" y="${my}" width="${mejaW}" height="${mejaH}" rx="8" fill="${warna}" stroke="#0f172a" stroke-width="2" />
    </svg>
  `;
}

export function renderAccessorySvg(accessory) {
    const type = accessory.type;
    const panjang = Number(accessory.panjang || 80);
    const orientasi = accessory.orientasi || 'horizontal';

    if (type === 'divider') {
        if (orientasi === 'horizontal') {
            return `<svg width="${panjang}" height="16" viewBox="0 0 ${panjang} 16"><rect x="4" y="6" width="${panjang - 8}" height="4" rx="2" fill="#64748b" /><rect x="2" y="4" width="6" height="8" rx="2" fill="#94a3b8" /><rect x="${panjang - 8}" y="4" width="6" height="8" rx="2" fill="#94a3b8" /></svg>`;
        }
        return `<svg width="16" height="${panjang}" viewBox="0 0 16 ${panjang}"><rect x="6" y="4" width="4" height="${panjang - 8}" rx="2" fill="#64748b" /><rect x="4" y="2" width="8" height="6" rx="2" fill="#94a3b8" /><rect x="4" y="${panjang - 8}" width="8" height="6" rx="2" fill="#94a3b8" /></svg>`;
    }

    if (type === 'lemari') {
        if (orientasi === 'horizontal') {
            return `<svg width="${panjang}" height="40" viewBox="0 0 ${panjang} 40"><rect x="2" y="4" width="${panjang - 4}" height="32" rx="4" fill="#f59e42" stroke="#b45309" stroke-width="2" /><rect x="6" y="8" width="24" height="24" fill="#fde68a" /></svg>`;
        }
        return `<svg width="40" height="${panjang}" viewBox="0 0 40 ${panjang}"><rect x="4" y="2" width="32" height="${panjang - 4}" rx="4" fill="#f59e42" stroke="#b45309" stroke-width="2" /><rect x="8" y="6" width="24" height="24" fill="#fde68a" /></svg>`;
    }

    if (type === 'pot') {
        return '<svg width="28" height="36" viewBox="0 0 28 36"><ellipse cx="14" cy="10" rx="12" ry="6" fill="#a3e635" /><rect x="4" y="10" width="20" height="18" rx="6" fill="#fbbf24" /><ellipse cx="14" cy="28" rx="10" ry="4" fill="#fbbf24" /></svg>';
    }

    if (type === 'pos') {
        return '<svg width="90" height="90" viewBox="0 0 90 90"><rect x="12" y="42" width="66" height="36" rx="10" fill="#2563eb" stroke="#222" stroke-width="4" /><rect x="28" y="18" width="34" height="22" rx="5" fill="#fff" stroke="#2563eb" stroke-width="3" /><rect x="22" y="68" width="16" height="10" rx="3" fill="#fbbf24" /><rect x="52" y="68" width="16" height="10" rx="3" fill="#fbbf24" /></svg>';
    }

    if (type === 'kasir') {
        return '<svg width="90" height="90" viewBox="0 0 90 90"><rect x="12" y="62" width="66" height="16" rx="6" fill="#b6d0ff" stroke="#2563eb" stroke-width="4" /><circle cx="45" cy="42" r="18" fill="#fbbf24" stroke="#b45309" stroke-width="3" /><rect x="32" y="72" width="26" height="12" rx="4" fill="#2563eb" /></svg>';
    }

    return '';
}

export function normalizeReservationNumber(value) {
    const raw = String(value || '').trim();
    if (!raw) return null;
    if (/^\d+$/.test(raw)) {
        return `RSV-${raw.padStart(6, '0')}`;
    }
    return raw;
}
