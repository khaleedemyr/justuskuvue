import { usePage } from '@inertiajs/vue3';
import {
    checkoutSelfOrder,
    createReservation,
    fetchReservationAvailabilityLayout,
    fetchSelfOrderMenu,
} from '@/lib/ymsoftApi';
import {
    RESERVATION_BLOCK_MINUTES,
    buildDatePickerDays,
    buildStorageImageUrl,
    buildTimeSlots,
    filterSlotsForDate,
    getTableSeatingCapacity,
    normalizeReservationNumber,
    normalizeWhatsAppNumber,
    renderAccessorySvg,
    renderTableSvg,
    sumSeatCapacityForTableIds,
    formatLocalDate,
} from '@/lib/reservationHelpers';
import { computed, nextTick, proxyRefs, ref, watch } from 'vue';

/** @param {import('vue').Ref<Array<{ id: number; name: string; address?: string | null; image?: string | null }>> | Array<...>} outletsSource */
export function useReservationArrange(outletsSource, t, langRef) {
    const page = usePage();
    const baseUrl = computed(() => String(page.props.ymsoftErpApiBaseUrl || '').replace(/\/$/, ''));
    const erpWebBaseUrl = computed(() => String(page.props.ymsoftErpWebBaseUrl || '').replace(/\/$/, ''));

    const outlets = computed(() => {
        const o = outletsSource?.value !== undefined ? outletsSource.value : outletsSource;
        return Array.isArray(o) ? o : [];
    });

    const callCenterWa = computed(() =>
        normalizeWhatsAppNumber(String(page.props.reservationCallCenterWa || '')),
    );

    const todayStr = formatLocalDate(new Date());
    const submitted = ref(false);
    const successInfo = ref(null);
    const outletId = ref('');
    const date = ref('');
    const time = ref('');
    const pax = ref(1);
    const paxDraft = ref(null);
    const smokingType = ref('non_smoking');
    const wizardStep = ref(1);
    const checkedAvailability = ref(false);
    const name = ref('');
    const phone = ref('');
    const email = ref('');
    const notes = ref('');
    const tableLayoutNotes = ref('');
    const orderChannel = ref(null);
    const channelActionLoading = ref(false);
    const savedReservationNumber = ref(null);
    const acceptedReservationTerms = ref(false);
    const message = ref(null);
    const submitError = ref(null);
    const isSubmitting = ref(false);
    const reservationCreated = ref(false);
    const selfOrderLoading = ref(false);
    const selfOrderError = ref(null);
    const selfOrderMenu = ref(null);
    const selfOrderMenuOutletId = ref(null);
    const selfOrderCart = ref({});
    const selfOrderMeta = ref({});
    const selfOrderSearch = ref('');
    const selfOrderCategoryId = ref(null);
    const viewCartOpen = ref(false);
    const detailItemId = ref(null);
    const detailQty = ref(1);
    const detailNotes = ref('');
    const detailSelectedModifiers = ref({});

    const dateStripRef = ref(null);
    const checkingAvailability = ref(false);
    const availabilityLayout = ref(null);
    const activeSectionId = ref(null);
    const selectedTableIds = ref([]);

    const timeSlots = computed(() => buildTimeSlots());
    const datePickerDays = computed(() => buildDatePickerDays(langRef.value));

    const availableTimeSlots = computed(() =>
        date.value ? filterSlotsForDate(timeSlots.value, date.value) : [],
    );

    const canCheckAvailability = computed(() => {
        if (!String(outletId.value).trim()) return false;
        if (!date.value || !time.value) return false;
        if (!pax.value || pax.value < 1 || pax.value > 40) return false;
        if (!name.value.trim() || !phone.value.trim()) return false;
        if (date.value < todayStr) return false;
        const allowed = filterSlotsForDate(timeSlots.value, date.value);
        if (!allowed.length || !allowed.includes(time.value)) return false;
        return true;
    });

    const canGoToStep2 = computed(() => String(outletId.value).trim().length > 0);

    watch(date, () => {
        if (!date.value) {
            time.value = '';
            return;
        }
        const allowed = filterSlotsForDate(timeSlots.value, date.value);
        if (!time.value) return;
        time.value = allowed.includes(time.value) ? time.value : '';
    });

    watch(date, async () => {
        await nextTick();
        if (!date.value || !dateStripRef.value) return;
        const btn = dateStripRef.value.querySelector(`[data-reservation-date="${date.value}"]`);
        btn?.scrollIntoView?.({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    });

    watch([wizardStep, orderChannel], () => {
        if (wizardStep.value === 4 && orderChannel.value === 'manual') {
            acceptedReservationTerms.value = false;
        }
        if (wizardStep.value === 5 && orderChannel.value === 'self') {
            acceptedReservationTerms.value = false;
        }
    });

    function scrollDateStrip(direction) {
        const el = dateStripRef.value;
        if (!el) return;
        const step = Math.max(180, Math.floor(el.clientWidth * 0.72));
        el.scrollBy({ left: direction * step, behavior: 'smooth' });
    }

    const selectedOutlet = computed(
        () => outlets.value.find((o) => String(o.id) === String(outletId.value)) ?? null,
    );

    const selectedOutletImageUrl = computed(() => {
        if (!selectedOutlet.value?.image?.trim()) return '';
        return buildStorageImageUrl(erpWebBaseUrl.value, selectedOutlet.value.image);
    });

    const timeWindowLabel = computed(() => {
        const start = time.value || '17:00';
        const [hRaw, mRaw] = start.split(':');
        const h = Number(hRaw);
        const m = Number(mRaw);
        if (!Number.isFinite(h) || !Number.isFinite(m)) return { start, end: '19:30' };
        const total = (h * 60 + m + RESERVATION_BLOCK_MINUTES) % (24 * 60);
        const endH = Math.floor(total / 60);
        const endM = total % 60;
        const end = `${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`;
        return { start, end };
    });

    const availabilityText = computed(() => {
        langRef.value;
        return t('reservationAvailableFor')
            .replace('{count}', String(pax.value))
            .replace('{start}', timeWindowLabel.value.start)
            .replace('{end}', timeWindowLabel.value.end);
    });

    const currentTables = computed(() => {
        if (!availabilityLayout.value || activeSectionId.value == null) return [];
        const tables =
            availabilityLayout.value.tables_by_section[String(activeSectionId.value)] ??
            availabilityLayout.value.tables_by_section[activeSectionId.value] ??
            [];
        return tables.filter((table) => (table.tipe || 'biasa') === 'biasa');
    });

    const currentAccessories = computed(() => {
        if (!availabilityLayout.value || activeSectionId.value == null) return [];
        return (
            availabilityLayout.value.accessories_by_section[String(activeSectionId.value)] ??
            availabilityLayout.value.accessories_by_section[activeSectionId.value] ??
            []
        );
    });

    const selectedTables = computed(() => {
        if (!availabilityLayout.value || selectedTableIds.value.length === 0) return [];
        const all = Object.values(availabilityLayout.value.tables_by_section).flat();
        return all.filter((table) => selectedTableIds.value.includes(Number(table.source_table_id)));
    });

    const selectedSeatTotal = computed(() => {
        if (!availabilityLayout.value || selectedTableIds.value.length === 0) return 0;
        return sumSeatCapacityForTableIds(selectedTableIds.value, availabilityLayout.value);
    });

    const maxSeatTotalForMultiTable = computed(() => Math.max(pax.value * 2, pax.value + 4));

    const isSelectionValid = computed(() => {
        if (selectedTableIds.value.length === 0 || selectedSeatTotal.value < pax.value) return false;
        if (selectedTableIds.value.length === 1) {
            return selectedSeatTotal.value >= pax.value;
        }
        return selectedSeatTotal.value <= maxSeatTotalForMultiTable.value;
    });

    const combinedSpecialRequestsForSubmit = computed(() => {
        langRef.value;
        const general = notes.value.trim();
        const tablePart = tableLayoutNotes.value.trim();
        const header = t('reservationTableNotesCombinedPrefix');
        if (!general && !tablePart) return null;
        if (!tablePart) return general || null;
        if (!general) return `${header} ${tablePart}`.trim();
        return `${general}\n\n${header} ${tablePart}`;
    });

    function resetAvailabilityCore() {
        checkedAvailability.value = false;
        availabilityLayout.value = null;
        activeSectionId.value = null;
        selectedTableIds.value = [];
        reservationCreated.value = false;
        selfOrderError.value = null;
        selfOrderCart.value = {};
        selfOrderMeta.value = {};
        selfOrderMenu.value = null;
        selfOrderMenuOutletId.value = null;
        selfOrderSearch.value = '';
        selfOrderCategoryId.value = null;
        viewCartOpen.value = false;
        detailItemId.value = null;
        tableLayoutNotes.value = '';
        orderChannel.value = null;
        channelActionLoading.value = false;
        savedReservationNumber.value = null;
        acceptedReservationTerms.value = false;
        successInfo.value = null;
    }

    function resetAvailabilityState(opts) {
        resetAvailabilityCore();
        if (opts?.outletChanged) {
            wizardStep.value = 1;
        } else {
            wizardStep.value = wizardStep.value > 2 ? 2 : wizardStep.value;
        }
    }

    async function handleCheckAvailability() {
        if (!canCheckAvailability.value) {
            if (!name.value.trim() || !phone.value.trim()) {
                message.value = t('reservationFillContactFirst');
            } else if (date.value && date.value < todayStr) {
                message.value = t('reservationDatePast');
            } else if (
                date.value &&
                time.value &&
                !filterSlotsForDate(timeSlots.value, date.value).includes(time.value)
            ) {
                message.value =
                    date.value === todayStr
                        ? t('reservationSameDayLeadTime')
                        : t('reservationFillAvailabilityFirst');
            } else {
                message.value = t('reservationFillAvailabilityFirst');
            }
            return;
        }

        checkingAvailability.value = true;
        message.value = null;
        try {
            const data = await fetchReservationAvailabilityLayout(baseUrl.value, {
                outletId: Number(outletId.value),
                reservationDate: date.value,
                reservationTime: time.value,
                numberOfGuests: pax.value,
                smokingPreference: smokingType.value,
                reservationDurationMinutes: RESERVATION_BLOCK_MINUTES,
            });
            if (!data.ok) {
                message.value = data.message || t('reservationNoTableAvailable');
                availabilityLayout.value = null;
                return;
            }
            const payload = data.data;
            const firstSectionId =
                payload.sections[0]?.source_section_id != null
                    ? Number(payload.sections[0].source_section_id)
                    : null;
            availabilityLayout.value = payload;
            activeSectionId.value = firstSectionId;
            selectedTableIds.value = [];
            checkedAvailability.value = true;
            wizardStep.value = 3;
            message.value =
                payload.available_table_count > 0 || (payload.table_combinations?.length ?? 0) > 0
                    ? t('reservationAvailabilityFound')
                    : t('reservationNoTableAvailable');
        } finally {
            checkingAvailability.value = false;
        }
    }

    async function submitReservationRequest() {
        if (!isSelectionValid.value || !outletId.value || !date.value || !time.value) {
            return { ok: false, error: t('reservationTableSelectionRequired') };
        }
        if (!name.value.trim() || !phone.value.trim()) {
            return { ok: false, error: t('reservationFillContactFirst') };
        }
        try {
            const response = await createReservation(baseUrl.value, {
                name: name.value.trim(),
                phone: phone.value.trim(),
                email: email.value.trim() || null,
                outlet_id: Number(outletId.value),
                reservation_date: date.value,
                reservation_time: time.value,
                number_of_guests: pax.value,
                selected_table_ids: selectedTableIds.value,
                smoking_preference: smokingType.value,
                special_requests: combinedSpecialRequestsForSubmit.value,
                from_sales: false,
                status: 'pending',
            });

            if (!response.ok) {
                try {
                    const err = await response.json();
                    const firstErr =
                        err.errors && Object.keys(err.errors).length
                            ? err.errors[Object.keys(err.errors)[0]]?.[0]
                            : null;
                    return {
                        ok: false,
                        error: firstErr || err.message || t('reservationSubmitFailed'),
                    };
                } catch {
                    return { ok: false, error: t('reservationSubmitFailed') };
                }
            }
            const data = await response.json();
            const id =
                typeof data.id === 'number' && Number.isFinite(data.id) ? data.id : undefined;
            const reservationNumberRaw = data.reservation_number;
            const reservationNumber =
                normalizeReservationNumber(
                    reservationNumberRaw == null
                        ? undefined
                        : String(reservationNumberRaw).trim() || undefined,
                ) ?? undefined;
            if (!reservationNumber) {
                return {
                    ok: false,
                    error:
                        langRef.value === 'id'
                            ? 'Nomor reservasi tidak tersedia dari server.'
                            : 'Reservation number was not returned by server.',
                };
            }
            return { ok: true, id, reservationNumber };
        } catch {
            return { ok: false, error: t('reservationSubmitFailed') };
        }
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(value || 0);
    }

    const selfOrderSelectedItems = computed(() => {
        if (!selfOrderMenu.value) return [];
        return selfOrderMenu.value.items
            .filter((item) => (selfOrderCart.value[item.id] ?? 0) > 0)
            .map((item) => ({
                ...item,
                qty: selfOrderCart.value[item.id] ?? 0,
                notes: selfOrderMeta.value[item.id]?.notes || '',
                selectedModifiers: selfOrderMeta.value[item.id]?.selectedModifiers || {},
            }));
    });

    const selfOrderSubtotal = computed(() =>
        selfOrderSelectedItems.value.reduce(
            (sum, item) => sum + (item.price || 0) * (item.qty || 0),
            0,
        ),
    );

    const selfOrderTotalQty = computed(() =>
        selfOrderSelectedItems.value.reduce((sum, item) => sum + (item.qty || 0), 0),
    );

    function getModifierSummary(item) {
        const selected = item.selectedModifiers || {};
        if (!item.modifiers || item.modifiers.length === 0) return [];
        const lines = [];
        for (const group of item.modifiers) {
            const picked = selected[group.modifier_id] || {};
            const optionLines = group.options
                .filter((opt) => Number(picked[opt.id] || 0) > 0)
                .map((opt) => {
                    const qty = Number(picked[opt.id] || 0);
                    return qty > 1 ? `${opt.name} x${qty}` : opt.name;
                });
            if (optionLines.length > 0) {
                lines.push(`${group.modifier_name}: ${optionLines.join(', ')}`);
            }
        }
        return lines;
    }

    async function openSelfOrder() {
        if (!name.value.trim() || !phone.value.trim() || !isSelectionValid.value || !outletId.value) {
            submitError.value = t('reservationFillAvailabilityFirst');
            return;
        }
        selfOrderError.value = null;
        const outletNumber = Number(outletId.value);
        if (selfOrderMenu.value && selfOrderMenuOutletId.value === outletNumber) return;
        selfOrderLoading.value = true;
        try {
            const result = await fetchSelfOrderMenu(baseUrl.value, outletNumber);
            if (!result.ok) {
                selfOrderError.value = result.message;
                return;
            }
            selfOrderMenu.value = result.data;
            selfOrderMenuOutletId.value = outletNumber;
            selfOrderCart.value = {};
            selfOrderMeta.value = {};
            selfOrderSearch.value = '';
            selfOrderCategoryId.value = null;
        } finally {
            selfOrderLoading.value = false;
        }
    }

    const selfOrderFilteredItems = computed(() => {
        if (!selfOrderMenu.value) return [];
        const keyword = selfOrderSearch.value.trim().toLowerCase();
        return selfOrderMenu.value.items.filter((item) => {
            const byCategory =
                selfOrderCategoryId.value == null ||
                Number(item.category_id) === selfOrderCategoryId.value;
            if (!byCategory) return false;
            if (!keyword) return true;
            const haystack = `${item.name} ${item.description || ''} ${item.category_name || ''}`.toLowerCase();
            return haystack.includes(keyword);
        });
    });

    async function handleSubmitSelfOrder() {
        if (!selfOrderMenu.value) {
            selfOrderError.value = 'Self order menu not loaded.';
            return;
        }
        if (selfOrderSelectedItems.value.length === 0) {
            selfOrderError.value = 'Pilih minimal 1 menu.';
            return;
        }
        if (!acceptedReservationTerms.value) {
            submitError.value = t('reservationTermsRequired');
            return;
        }
        isSubmitting.value = true;
        selfOrderError.value = null;
        submitError.value = null;
        try {
            let reservationNumberForSuccess = savedReservationNumber.value;
            if (!reservationCreated.value) {
                const reservationResult = await submitReservationRequest();
                if (!reservationResult.ok) {
                    submitError.value = reservationResult.error || t('reservationSubmitFailed');
                    return;
                }
                reservationCreated.value = true;
                if (reservationResult.reservationNumber) {
                    savedReservationNumber.value = reservationResult.reservationNumber;
                    reservationNumberForSuccess = reservationResult.reservationNumber;
                }
            }

            const orderResult = await checkoutSelfOrder(baseUrl.value, {
                menu_book_id: selfOrderMenu.value.menu_book.id,
                customer_name: name.value.trim(),
                customer_phone: phone.value.trim() || null,
                order_type: 'dine_in',
                notes: notes.value.trim() || null,
                items: selfOrderSelectedItems.value.map((item) => ({
                    item_id: item.id,
                    qty: item.qty,
                    notes: item.notes || null,
                    modifiers: item.selectedModifiers || {},
                })),
            });

            if (!orderResult.ok) {
                selfOrderError.value = orderResult.message;
                return;
            }

            const finalReservationNumber =
                normalizeReservationNumber(reservationNumberForSuccess || null) || '-';
            successInfo.value = {
                reservationNumber: finalReservationNumber,
                name: name.value.trim() || '-',
                phone: phone.value.trim() || '-',
                email: email.value.trim() || '-',
                outlet: selectedOutlet.value?.name || '-',
                date: date.value || '-',
                time: time.value || '-',
                pax: pax.value,
                table: tableNamesSummary.value || '-',
                area: smokingType.value === 'smoking' ? 'Smoking' : 'Non-Smoking',
                orderMethod: 'self',
            };
            submitted.value = true;
            message.value =
                langRef.value === 'id'
                    ? `Pesanan mandiri berhasil dibuat (${orderResult.data.order_no}).`
                    : `Self order created (${orderResult.data.order_no}).`;
            selfOrderCart.value = {};
            selfOrderMeta.value = {};
        } finally {
            isSubmitting.value = false;
        }
    }

    const detailItem = computed(
        () => selfOrderMenu.value?.items.find((item) => item.id === detailItemId.value) ?? null,
    );

    function openItemDetail(itemId) {
        const qty = selfOrderCart.value[itemId] ?? 0;
        const meta = selfOrderMeta.value[itemId] ?? {};
        detailItemId.value = itemId;
        detailQty.value = qty > 0 ? qty : 1;
        detailNotes.value = meta.notes ?? '';
        detailSelectedModifiers.value = { ...(meta.selectedModifiers ?? {}) };
    }

    function saveItemDetail() {
        if (!detailItemId.value) return;
        const qty = Math.max(0, Math.min(99, detailQty.value));
        selfOrderCart.value = {
            ...selfOrderCart.value,
            [detailItemId.value]: qty,
        };
        selfOrderMeta.value = {
            ...selfOrderMeta.value,
            [detailItemId.value]: {
                notes: detailNotes.value.trim(),
                selectedModifiers: detailSelectedModifiers.value,
            },
        };
        detailItemId.value = null;
    }

    function handleToggleDetailModifier(modifierId, optionId) {
        const prev = detailSelectedModifiers.value;
        const modifierOptions = { ...(prev[modifierId] || {}) };
        if (modifierOptions[optionId]) {
            const nextOptions = { ...modifierOptions };
            delete nextOptions[optionId];
            if (Object.keys(nextOptions).length === 0) {
                const next = { ...prev };
                delete next[modifierId];
                detailSelectedModifiers.value = next;
                return;
            }
            detailSelectedModifiers.value = {
                ...prev,
                [modifierId]: nextOptions,
            };
            return;
        }
        detailSelectedModifiers.value = {
            ...prev,
            [modifierId]: {
                ...modifierOptions,
                [optionId]: 1,
            },
        };
    }

    function handleDetailModifierQty(modifierId, optionId, delta) {
        const prev = detailSelectedModifiers.value;
        const modifierOptions = { ...(prev[modifierId] || {}) };
        const currentQty = Number(modifierOptions[optionId] || 0);
        if (currentQty <= 0) return;
        const nextQty = Math.max(1, Math.min(99, currentQty + delta));
        detailSelectedModifiers.value = {
            ...prev,
            [modifierId]: {
                ...modifierOptions,
                [optionId]: nextQty,
            },
        };
    }

    function handleManualOrderToWhatsApp(reservationNumber, targetWindow) {
        if (!callCenterWa.value) {
            if (targetWindow && !targetWindow.closed) {
                targetWindow.close();
            }
            submitError.value = t('reservationWaNotConfigured');
            return false;
        }
        const selectedTableLabel =
            selectedTables.value.map((table) => table.nama || `T-${table.source_table_id}`).join(' + ') ||
            '-';
        const lines = [
            'Halo Call Center, saya ingin reservasi (Order Manual).',
            '',
            `Nama: ${name.value.trim() || '-'}`,
            `No HP: ${phone.value.trim() || '-'}`,
            `Email: ${email.value.trim() || '-'}`,
            `Outlet: ${selectedOutlet.value?.name || '-'}`,
            `Tanggal: ${date.value || '-'}`,
            `Jam: ${time.value || '-'}`,
            `Pax: ${pax.value}`,
            `DP: ${formatCurrency(pax.value * 100000)}`,
            `Meja: ${selectedTableLabel}`,
            `Area: ${smokingType.value === 'smoking' ? 'Smoking' : 'Non-Smoking'}`,
            notes.value.trim() ? `Special Request: ${notes.value}` : '',
            tableLayoutNotes.value.trim()
                ? `${t('reservationTableNotesWaLine')}: ${tableLayoutNotes.value.trim()}`
                : '',
            reservationNumber && String(reservationNumber).trim()
                ? t('reservationWaReservationNumberLine').replace('{number}', String(reservationNumber).trim())
                : '',
        ].filter(Boolean);
        const text = encodeURIComponent(lines.join('\n'));
        const waUrl = `https://wa.me/${callCenterWa.value}?text=${text}`;
        if (targetWindow && !targetWindow.closed) {
            targetWindow.location.href = waUrl;
            return true;
        }
        const openedWindow = window.open(waUrl, '_blank');
        if (!openedWindow) {
            submitError.value =
                langRef.value === 'id'
                    ? 'Popup WhatsApp diblokir browser. Mohon izinkan pop-up lalu coba lagi.'
                    : 'WhatsApp popup was blocked by the browser. Please allow popups and try again.';
            return false;
        }
        return true;
    }

    async function handleAdvanceFromStep3() {
        submitError.value = null;
        message.value = null;
        if (!orderChannel.value) {
            submitError.value = t('reservationPickOrderChannel');
            return;
        }
        if (!name.value.trim() || !phone.value.trim() || !isSelectionValid.value) {
            submitError.value = t('reservationFillAvailabilityFirst');
            return;
        }

        if (orderChannel.value === 'manual') {
            wizardStep.value = 4;
            return;
        }

        channelActionLoading.value = true;
        try {
            wizardStep.value = 4;
            await openSelfOrder();
        } finally {
            channelActionLoading.value = false;
        }
    }

    async function handleManualSubmitFromSummary() {
        const preOpenedWaWindow = callCenterWa.value ? window.open('', '_blank') : null;
        submitError.value = null;
        message.value = null;
        if (!name.value.trim() || !phone.value.trim() || !isSelectionValid.value) {
            if (preOpenedWaWindow && !preOpenedWaWindow.closed) {
                preOpenedWaWindow.close();
            }
            submitError.value = t('reservationFillAvailabilityFirst');
            return;
        }
        if (!acceptedReservationTerms.value) {
            if (preOpenedWaWindow && !preOpenedWaWindow.closed) {
                preOpenedWaWindow.close();
            }
            submitError.value = t('reservationTermsRequired');
            return;
        }
        isSubmitting.value = true;
        try {
            let reservationNumberForWa = savedReservationNumber.value;
            if (!reservationCreated.value) {
                const result = await submitReservationRequest();
                if (!result.ok) {
                    if (preOpenedWaWindow && !preOpenedWaWindow.closed) {
                        preOpenedWaWindow.close();
                    }
                    submitError.value = result.error || t('reservationSubmitFailed');
                    return;
                }
                reservationCreated.value = true;
                if (result.reservationNumber) {
                    savedReservationNumber.value = result.reservationNumber;
                    reservationNumberForWa = result.reservationNumber;
                }
            }
            const opened = handleManualOrderToWhatsApp(reservationNumberForWa, preOpenedWaWindow);
            if (!opened) {
                return;
            }
            const reservationLabel =
                normalizeReservationNumber(String(reservationNumberForWa || '').trim()) || '-';
            successInfo.value = {
                reservationNumber: reservationLabel,
                name: name.value.trim() || '-',
                phone: phone.value.trim() || '-',
                email: email.value.trim() || '-',
                outlet: selectedOutlet.value?.name || '-',
                date: date.value || '-',
                time: time.value || '-',
                pax: pax.value,
                table: tableNamesSummary.value || '-',
                area: smokingType.value === 'smoking' ? 'Smoking' : 'Non-Smoking',
                orderMethod: 'manual',
            };
            submitted.value = true;
            message.value = null;
        } finally {
            isSubmitting.value = false;
        }
    }

    function handleGoToSelfSummaryFromMenu() {
        submitError.value = null;
        if (selfOrderSelectedItems.value.length === 0) {
            submitError.value = t('reservationSelfOrderNeedItems');
            return;
        }
        wizardStep.value = 5;
    }

    const wizardStepsMeta = computed(() => {
        langRef.value;
        const base = [
            { id: 1, label: t('reservationChooseOutlet') },
            { id: 2, label: t('reservationWizardVisitDetails') },
            { id: 3, label: t('reservationChooseTableTitle') },
        ];
        if (orderChannel.value === 'manual') {
            return [...base, { id: 4, label: t('reservationSummaryStep') }];
        }
        if (orderChannel.value === 'self') {
            return [
                ...base,
                { id: 4, label: t('reservationWizardSelfOrderMenu') },
                { id: 5, label: t('reservationSummaryStep') },
            ];
        }
        return [...base, { id: 4, label: t('reservationWizardStepPendingChannel') }];
    });

    const tableNamesSummary = computed(() =>
        selectedTables.value.length > 0
            ? selectedTables.value.map((tbl) => tbl.nama || `T-${tbl.source_table_id}`).join(' + ')
            : '—',
    );

    const smokingSummaryLabel = computed(() =>
        smokingType.value === 'smoking' ? t('reservationSmokingArea') : t('reservationNonSmokingArea'),
    );

    const reservationDepositAmount = computed(() => pax.value * 100000);

    const successQrPayload = computed(() => successInfo.value?.reservationNumber || '');

    const successQrUrl = computed(() => {
        if (!successQrPayload.value) return '';
        return `https://api.qrserver.com/v1/create-qr-code/?size=512x512&margin=10&data=${encodeURIComponent(successQrPayload.value)}`;
    });

    async function handleDownloadSuccessQr() {
        if (!successQrUrl.value) return;
        const filename = `reservation-${(successInfo.value?.reservationNumber || 'qr').replace(/[^a-zA-Z0-9_-]/g, '_')}.png`;
        try {
            const response = await fetch(successQrUrl.value);
            if (!response.ok) throw new Error('download_failed');
            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = objectUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(objectUrl);
        } catch {
            const a = document.createElement('a');
            a.href = successQrUrl.value;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    }

    const reservationTermKeys = [
        'reservationTermsItem1',
        'reservationTermsItem2',
        'reservationTermsItem3',
        'reservationTermsItem4',
        'reservationTermsItem5',
        'reservationTermsItem6',
        'reservationTermsItem7',
        'reservationTermsItem8',
    ];

    function onTableClick(table) {
        if (table.occupied) return;
        const canSelectTable = !table.occupied && Boolean(table.selectable);
        if (!canSelectTable) {
            message.value = t('reservationTableNotSelectableHint');
            return;
        }
        const tableId = Number(table.source_table_id);
        const prev = [...selectedTableIds.value];
        if (prev.includes(tableId)) {
            selectedTableIds.value = prev.filter((id) => id !== tableId);
            return;
        }
        if (!availabilityLayout.value) return;
        const prevTotal = sumSeatCapacityForTableIds(prev, availabilityLayout.value);
        const seatOfClicked = getTableSeatingCapacity(table);
        const nextTotal = prevTotal + seatOfClicked;
        const nextLen = prev.length + 1;
        if (nextLen > 1 && nextTotal > maxSeatTotalForMultiTable.value) {
            message.value = t('reservationSeatLimitReached');
            return;
        }
        message.value = null;
        selectedTableIds.value = [...prev, tableId];
    }

    const fieldClass =
        'mt-2 block w-full rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3.5 text-base text-white outline-none transition placeholder:text-white/30 focus:border-amber-400/45 focus:ring-2 focus:ring-amber-400/20 md:text-lg';

    return proxyRefs({
        todayStr,
        submitted,
        successInfo,
        outletId,
        date,
        time,
        pax,
        paxDraft,
        smokingType,
        wizardStep,
        checkedAvailability,
        name,
        phone,
        email,
        notes,
        tableLayoutNotes,
        orderChannel,
        channelActionLoading,
        savedReservationNumber,
        acceptedReservationTerms,
        message,
        submitError,
        isSubmitting,
        reservationCreated,
        selfOrderLoading,
        selfOrderError,
        selfOrderMenu,
        selfOrderCart,
        selfOrderMeta,
        selfOrderSearch,
        selfOrderCategoryId,
        viewCartOpen,
        detailItemId,
        detailQty,
        detailNotes,
        detailSelectedModifiers,
        dateStripRef,
        checkingAvailability,
        availabilityLayout,
        activeSectionId,
        selectedTableIds,
        timeSlots,
        datePickerDays,
        availableTimeSlots,
        canCheckAvailability,
        canGoToStep2,
        scrollDateStrip,
        selectedOutlet,
        selectedOutletImageUrl,
        availabilityText,
        currentTables,
        currentAccessories,
        selectedTables,
        selectedSeatTotal,
        maxSeatTotalForMultiTable,
        isSelectionValid,
        resetAvailabilityState,
        handleCheckAvailability,
        submitReservationRequest,
        formatCurrency,
        selfOrderSelectedItems,
        selfOrderSubtotal,
        selfOrderTotalQty,
        getModifierSummary,
        openSelfOrder,
        selfOrderFilteredItems,
        handleSubmitSelfOrder,
        detailItem,
        openItemDetail,
        saveItemDetail,
        handleToggleDetailModifier,
        handleDetailModifierQty,
        handleAdvanceFromStep3,
        handleManualSubmitFromSummary,
        handleGoToSelfSummaryFromMenu,
        wizardStepsMeta,
        tableNamesSummary,
        smokingSummaryLabel,
        reservationDepositAmount,
        successQrUrl,
        handleDownloadSuccessQr,
        reservationTermKeys,
        fieldClass,
        baseUrl,
        erpWebBaseUrl,
        handleAdvanceFromStep3Form(e) {
            e.preventDefault();
            if (
                wizardStep.value !== 3 ||
                !checkedAvailability.value ||
                isSubmitting.value ||
                channelActionLoading.value
            ) {
                return;
            }
            handleAdvanceFromStep3();
        },
        renderTableSvg,
        renderAccessorySvg,
        buildStorageImageUrl,
        onTableClick,
    });
}
