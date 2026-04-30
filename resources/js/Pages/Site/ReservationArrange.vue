<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link } from '@inertiajs/vue3';
import { toRef } from 'vue';
import { useSiteI18n } from '@/composables/useSiteI18n';
import { useReservationArrange } from '@/composables/useReservationArrange';
import { getTableSeatingCapacity } from '@/lib/reservationHelpers';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    outlets: { type: Array, default: () => [] },
});

const { t, lang } = useSiteI18n();
const form = useReservationArrange(toRef(props, 'outlets'), t, lang);
</script>

<template>
    <SiteLayout
        :title="t('reservationFormTitle')"
        :menus="menus"
        :brand-logos="brandLogos"
        shell-class="min-h-screen bg-[#0a0a0c] text-white"
    >
        <main
            class="relative min-h-[100dvh] overflow-hidden bg-[#0a0a0c] px-4 py-20 text-white md:px-8 md:py-24"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(251,191,36,0.12),transparent)]"
                aria-hidden
            />
            <div
                class="pointer-events-none absolute -left-32 top-1/3 h-96 w-96 rounded-full bg-amber-500/5 blur-3xl"
                aria-hidden
            />
            <div
                class="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-violet-500/5 blur-3xl"
                aria-hidden
            />

            <div class="relative mx-auto w-full max-w-6xl">
                <div
                    class="relative overflow-hidden rounded-[2rem] border border-white/[0.08] bg-gradient-to-b from-white/[0.09] to-white/[0.03] p-1 shadow-[0_32px_120px_-24px_rgba(0,0,0,0.75)] backdrop-blur-xl md:rounded-[2.25rem] md:p-1.5"
                >
                    <div class="rounded-[1.85rem] border border-white/5 bg-[#121215]/90 px-5 py-8 md:px-10 md:py-10">
                        <template v-if="!form.submitted">
                            <div class="mb-10 text-center md:mb-12">
                                <p
                                    class="font-montserrat text-[10px] font-semibold uppercase tracking-[0.45em] text-amber-400/90 md:text-xs"
                                >
                                    Justus Group
                                </p>
                                <h1
                                    class="font-montserrat mt-3 text-3xl font-light tracking-wide text-white md:text-4xl lg:text-[2.75rem]"
                                >
                                    {{ t('reservationSelectOutletTitle') }}
                                </h1>
                                <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-white/55 md:text-base">
                                    {{ t('reservationHeroLine1') }}
                                </p>
                            </div>

                            <nav class="mb-10 md:mb-12" aria-label="Reservation steps">
                                <ol class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-2">
                                    <li
                                        v-for="(st, idx) in form.wizardStepsMeta"
                                        :key="st.id"
                                        class="flex flex-1 flex-col items-center gap-2 sm:min-w-0"
                                    >
                                        <div class="flex w-full items-center sm:flex-col sm:gap-3">
                                            <div
                                                v-if="idx > 0"
                                                class="mr-3 hidden h-0.5 w-6 shrink-0 rounded-full sm:mb-0 sm:mr-0 sm:mt-5 sm:block sm:h-8 sm:w-0.5"
                                                :class="
                                                    form.wizardStep > form.wizardStepsMeta[idx - 1].id
                                                        ? 'bg-gradient-to-b from-amber-400 to-amber-600'
                                                        : 'bg-white/10'
                                                "
                                                aria-hidden
                                            />
                                            <div class="flex w-full flex-col items-center gap-2">
                                                <div
                                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border-2 text-sm font-semibold transition-all duration-300 md:h-12 md:w-12 md:text-base"
                                                    :class="
                                                        form.wizardStep === st.id
                                                            ? 'scale-110 border-amber-400 bg-gradient-to-br from-amber-300 to-amber-600 text-zinc-900 shadow-lg shadow-amber-500/35'
                                                            : form.wizardStep > st.id
                                                              ? 'border-emerald-500/70 bg-emerald-500/15 text-emerald-200'
                                                              : 'border-white/15 bg-white/[0.04] text-white/35'
                                                    "
                                                >
                                                    {{ form.wizardStep > st.id ? '✓' : st.id }}
                                                </div>
                                                <span
                                                    class="text-center text-[10px] font-medium uppercase leading-tight tracking-wider md:text-xs"
                                                    :class="form.wizardStep === st.id ? 'text-amber-200/95' : 'text-white/45'"
                                                >
                                                    {{ st.label }}
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                </ol>
                            </nav>

                            <form @submit="form.handleAdvanceFromStep3Form">
                                <div v-if="form.wizardStep === 1" class="mx-auto max-w-xl space-y-8">
                                    <div>
                                        <label class="block">
                                            <span
                                                class="font-montserrat text-sm font-medium uppercase tracking-widest text-white/50"
                                            >
                                                {{ t('reservationChooseOutlet') }}
                                            </span>
                                            <select
                                                v-model="form.outletId"
                                                required
                                                class="mt-2 block w-full rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3.5 text-base text-white outline-none transition placeholder:text-white/30 focus:border-amber-400/45 focus:ring-2 focus:ring-amber-400/20 md:text-lg"
                                                @change="form.resetAvailabilityState({ outletChanged: true })"
                                            >
                                                <option value="" disabled class="bg-zinc-900">
                                                    {{ t('reservationChooseOutlet') }}
                                                </option>
                                                <option
                                                    v-for="outlet in outlets"
                                                    :key="outlet.id"
                                                    class="bg-zinc-900"
                                                    :value="String(outlet.id)"
                                                >
                                                    {{ outlet.name }}
                                                </option>
                                            </select>
                                        </label>
                                    </div>
                                    <div
                                        v-if="form.selectedOutlet"
                                        class="overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-white/[0.07] to-transparent"
                                    >
                                        <div class="grid md:grid-cols-[minmax(0,320px)_1fr] md:items-stretch">
                                            <div
                                                class="relative flex min-h-[200px] items-center justify-center border-b border-white/10 bg-black/25 md:min-h-0 md:border-b-0 md:border-r md:border-white/10"
                                            >
                                                <div
                                                    v-if="form.selectedOutletImageUrl"
                                                    class="box-border flex w-full items-center justify-center px-5 py-8 md:min-h-[240px] md:px-8 md:py-10"
                                                >
                                                    <img
                                                        :src="form.selectedOutletImageUrl"
                                                        :alt="form.selectedOutlet.name"
                                                        class="h-auto max-h-[min(220px,45vh)] w-auto max-w-full object-contain md:max-h-[min(280px,38vh)]"
                                                    />
                                                </div>
                                                <div
                                                    v-else
                                                    class="font-montserrat px-6 text-center text-xs font-medium uppercase tracking-widest text-white/30"
                                                >
                                                    {{ t('noImage') }}
                                                </div>
                                            </div>
                                            <div class="p-6 md:p-8">
                                                <h2 class="font-montserrat text-xl font-medium text-white md:text-2xl">
                                                    {{ form.selectedOutlet.name }}
                                                </h2>
                                                <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-white/60 md:text-base">
                                                    {{ form.selectedOutlet.address || '—' }}
                                                </p>
                                                <div class="mt-6 flex flex-wrap gap-6 border-t border-white/10 pt-6 text-sm text-white/55">
                                                    <div>
                                                        <p
                                                            class="text-[10px] font-semibold uppercase tracking-widest text-amber-400/80"
                                                        >
                                                            {{ t('reservationOperationalHours') }}
                                                        </p>
                                                        <p class="mt-1 font-medium text-white/80">11.00 AM – 10.00 PM</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
                                        <Link
                                            href="/reservation"
                                            class="rounded-2xl border border-white/15 px-6 py-3.5 text-center text-sm font-semibold uppercase tracking-widest text-white/80 transition hover:border-white/25 hover:bg-white/[0.04]"
                                        >
                                            {{ t('reservationBack') }}
                                        </Link>
                                        <button
                                            type="button"
                                            :disabled="!form.canGoToStep2"
                                            class="rounded-2xl bg-gradient-to-r from-amber-400 to-amber-600 px-8 py-3.5 text-sm font-semibold uppercase tracking-widest text-zinc-900 shadow-lg shadow-amber-900/30 transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-40"
                                            @click="form.canGoToStep2 && (form.wizardStep = 2)"
                                        >
                                            {{ t('reservationWizardNext') }}
                                        </button>
                                    </div>
                                </div>

                                <div v-if="form.wizardStep === 2" class="space-y-8">
                                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-stretch lg:gap-12">
                                        <div class="space-y-6">
                                            <div>
                                                <span
                                                    class="font-montserrat text-sm font-medium uppercase tracking-widest text-white/50"
                                                >
                                                    {{ t('reservationPickDate') }}
                                                </span>
                                                <div class="mt-3 flex items-stretch gap-2">
                                                    <button
                                                        type="button"
                                                        :aria-label="t('reservationDateScrollPrev')"
                                                        class="flex h-auto min-h-[4.5rem] w-9 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.05] text-amber-300/90 transition hover:border-white/20 hover:bg-white/[0.08] md:w-10"
                                                        @click="form.scrollDateStrip(-1)"
                                                    >
                                                        <svg
                                                            class="h-5 w-5"
                                                            viewBox="0 0 24 24"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            aria-hidden
                                                        >
                                                            <path d="M15 18l-6-6 6-6" />
                                                        </svg>
                                                    </button>
                                                    <div
                                                        ref="form.dateStripRef"
                                                        class="flex min-w-0 flex-1 snap-x snap-mandatory gap-2 overflow-x-auto overscroll-contain scroll-smooth pb-1 pt-0.5 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
                                                    >
                                                        <button
                                                            v-for="row in form.datePickerDays"
                                                            :key="row.value"
                                                            type="button"
                                                            :data-reservation-date="row.value"
                                                            class="flex min-w-[5.5rem] shrink-0 snap-start flex-col items-center gap-1 rounded-2xl border px-3 py-3 text-center transition"
                                                            :class="
                                                                form.date === row.value
                                                                    ? 'border-amber-400/70 bg-gradient-to-b from-amber-500/25 to-amber-600/10 shadow-lg shadow-amber-900/20 ring-1 ring-amber-400/40'
                                                                    : 'border-white/10 bg-white/[0.04] hover:border-white/20 hover:bg-white/[0.07]'
                                                            "
                                                            @click="
                                                                () => {
                                                                    form.date = row.value;
                                                                    form.resetAvailabilityState();
                                                                }
                                                            "
                                                        >
                                                            <span
                                                                class="text-[10px] font-bold uppercase tracking-widest"
                                                                :class="
                                                                    form.date === row.value ? 'text-amber-200' : 'text-amber-400/80'
                                                                "
                                                            >
                                                                {{ row.dayLabel }}
                                                            </span>
                                                            <span
                                                                class="text-xs font-medium leading-tight"
                                                                :class="form.date === row.value ? 'text-white' : 'text-white/70'"
                                                            >
                                                                {{ row.dateLine }}
                                                            </span>
                                                        </button>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        :aria-label="t('reservationDateScrollNext')"
                                                        class="flex h-auto min-h-[4.5rem] w-9 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.05] text-amber-300/90 transition hover:border-white/20 hover:bg-white/[0.08] md:w-10"
                                                        @click="form.scrollDateStrip(1)"
                                                    >
                                                        <svg
                                                            class="h-5 w-5"
                                                            viewBox="0 0 24 24"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            aria-hidden
                                                        >
                                                            <path d="M9 18l6-6-6-6" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div>
                                                <span
                                                    class="font-montserrat text-sm font-medium uppercase tracking-widest text-white/50"
                                                >
                                                    {{ t('reservationPickTime') }}
                                                    <span
                                                        class="text-xs font-normal normal-case tracking-normal text-white/35"
                                                    >
                                                        ({{ t('reservationDurationHint') }})
                                                    </span>
                                                </span>
                                                <p
                                                    v-if="form.date && form.availableTimeSlots.length === 0"
                                                    class="mt-3 rounded-2xl border border-rose-400/25 bg-rose-500/10 px-4 py-3 text-sm text-rose-100/90"
                                                >
                                                    {{ t('reservationSameDayNoSlots') }}
                                                </p>
                                                <div v-else class="mt-3 flex flex-wrap gap-2">
                                                    <button
                                                        v-for="slot in form.availableTimeSlots"
                                                        :key="slot"
                                                        type="button"
                                                        class="min-w-[4.25rem] rounded-xl border px-3 py-2.5 text-sm font-semibold tabular-nums transition"
                                                        :class="
                                                            form.time === slot
                                                                ? 'border-amber-400/70 bg-amber-500/20 text-amber-50 ring-1 ring-amber-400/35'
                                                                : 'border-white/10 bg-white/[0.04] text-white/80 hover:border-white/18 hover:bg-white/[0.08]'
                                                        "
                                                        @click="
                                                            () => {
                                                                form.time = slot;
                                                                form.resetAvailabilityState();
                                                            }
                                                        "
                                                    >
                                                        {{ slot }}
                                                    </button>
                                                </div>
                                                <p
                                                    v-if="form.date === form.todayStr && form.availableTimeSlots.length > 0"
                                                    class="mt-2 text-xs leading-relaxed text-white/40"
                                                >
                                                    {{ t('reservationSameDayLeadHint') }}
                                                </p>
                                            </div>
                                            <div
                                                class="rounded-2xl border border-amber-400/20 bg-amber-500/10 px-4 py-4 text-center text-xs font-semibold uppercase leading-relaxed tracking-wider text-amber-100/90 md:text-sm"
                                            >
                                                {{ form.availabilityText }}
                                            </div>
                                            <div>
                                                <p
                                                    class="font-montserrat text-sm font-medium uppercase tracking-widest text-white/50"
                                                >
                                                    {{ t('reservationPax') }}
                                                </p>
                                                <div class="mt-3 grid grid-cols-3 gap-3">
                                                    <button
                                                        type="button"
                                                        class="rounded-2xl border border-white/10 bg-white/[0.06] py-3 text-3xl font-light text-white transition hover:bg-white/10"
                                                        @click="
                                                            () => {
                                                                form.paxDraft = null;
                                                                form.pax = Math.max(1, form.pax - 1);
                                                                form.resetAvailabilityState();
                                                            }
                                                        "
                                                    >
                                                        −
                                                    </button>
                                                    <input
                                                        type="text"
                                                        inputmode="numeric"
                                                        autocomplete="off"
                                                        enterkeyhint="done"
                                                        :aria-label="t('reservationPax')"
                                                        class="min-w-0 w-full rounded-2xl border border-amber-400/30 bg-amber-500/10 py-3 text-center text-3xl font-light tabular-nums text-amber-100 outline-none transition placeholder:text-amber-100/35 focus:border-amber-400/55 focus:ring-2 focus:ring-amber-400/25"
                                                        :value="
                                                            form.paxDraft !== null && form.paxDraft !== undefined
                                                                ? form.paxDraft
                                                                : String(form.pax).padStart(2, '0')
                                                        "
                                                        @focus="form.paxDraft = String(form.pax)"
                                                        @input="
                                                            (e) => {
                                                                const raw = e.target.value.replace(/\D/g, '');
                                                                form.paxDraft = raw;
                                                                if (raw === '') return;
                                                                const n = parseInt(raw, 10);
                                                                if (!Number.isFinite(n)) return;
                                                                form.pax = Math.min(40, Math.max(1, n));
                                                                form.resetAvailabilityState();
                                                            }
                                                        "
                                                        @blur="
                                                            () => {
                                                                if (form.paxDraft === '') {
                                                                    form.pax = 1;
                                                                } else {
                                                                    form.pax =
                                                                        form.pax >= 1 && form.pax <= 40 ? form.pax : 1;
                                                                }
                                                                form.paxDraft = null;
                                                            }
                                                        "
                                                    />
                                                    <button
                                                        type="button"
                                                        class="rounded-2xl border border-white/10 bg-white/[0.06] py-3 text-3xl font-light text-white transition hover:bg-white/10"
                                                        @click="
                                                            () => {
                                                                form.paxDraft = null;
                                                                form.pax = Math.min(40, form.pax + 1);
                                                                form.resetAvailabilityState();
                                                            }
                                                        "
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="flex h-full min-h-0 flex-col rounded-2xl border border-white/10 bg-white/[0.03] p-6 md:p-8"
                                        >
                                            <div class="shrink-0">
                                                <p
                                                    class="font-montserrat text-sm font-medium uppercase tracking-widest text-white/50"
                                                >
                                                    {{ t('reservationSmokingArea') }} / {{ t('reservationNonSmokingArea') }}
                                                </p>
                                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                    <button
                                                        type="button"
                                                        class="rounded-2xl border-2 px-4 py-4 text-left transition"
                                                        :class="
                                                            form.smokingType === 'non_smoking'
                                                                ? 'border-sky-400/70 bg-sky-500/15 text-sky-100'
                                                                : 'border-white/10 bg-transparent text-white/50 hover:border-white/20'
                                                        "
                                                        @click="
                                                            () => {
                                                                form.smokingType = 'non_smoking';
                                                                form.resetAvailabilityState();
                                                            }
                                                        "
                                                    >
                                                        <span class="text-xs font-bold uppercase tracking-widest">
                                                            {{ t('reservationNonSmokingArea') }}
                                                        </span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="rounded-2xl border-2 px-4 py-4 text-left transition"
                                                        :class="
                                                            form.smokingType === 'smoking'
                                                                ? 'border-amber-400/70 bg-amber-500/15 text-amber-100'
                                                                : 'border-white/10 bg-transparent text-white/50 hover:border-white/20'
                                                        "
                                                        @click="
                                                            () => {
                                                                form.smokingType = 'smoking';
                                                                form.resetAvailabilityState();
                                                            }
                                                        "
                                                    >
                                                        <span class="text-xs font-bold uppercase tracking-widest">
                                                            {{ t('reservationSmokingArea') }}
                                                        </span>
                                                    </button>
                                                </div>
                                            </div>
                                            <label class="mt-6 flex min-h-[7.5rem] flex-1 flex-col">
                                                <span
                                                    class="font-montserrat text-sm font-medium uppercase tracking-widest text-white/50"
                                                >
                                                    {{ t('reservationNotes') }}
                                                </span>
                                                <textarea
                                                    v-model="form.notes"
                                                    rows="4"
                                                    :class="form.fieldClass + ' mt-2 min-h-[7.5rem] flex-1 resize-y'"
                                                    :placeholder="t('reservationNotes')"
                                                />
                                            </label>
                                            <p class="mt-6 shrink-0 text-sm text-white/40">
                                                {{ form.selectedOutlet?.name }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6 md:p-8">
                                        <p
                                            class="font-montserrat text-sm font-medium uppercase tracking-widest text-amber-400/85"
                                        >
                                            {{ t('reservationStepContact') }}
                                        </p>
                                        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                                            <label class="md:col-span-2">
                                                <span
                                                    class="font-montserrat text-sm font-medium uppercase tracking-widest text-white/50"
                                                >
                                                    {{ t('reservationName') }}
                                                </span>
                                                <input
                                                    v-model="form.name"
                                                    type="text"
                                                    required
                                                    :class="form.fieldClass"
                                                />
                                            </label>
                                            <label>
                                                <span
                                                    class="font-montserrat text-sm font-medium uppercase tracking-widest text-white/50"
                                                >
                                                    {{ t('reservationPhone') }}
                                                </span>
                                                <input
                                                    v-model="form.phone"
                                                    type="tel"
                                                    required
                                                    :class="form.fieldClass"
                                                />
                                            </label>
                                            <label>
                                                <span
                                                    class="font-montserrat text-sm font-medium uppercase tracking-widest text-white/50"
                                                >
                                                    {{ t('reservationEmail') }}
                                                </span>
                                                <input v-model="form.email" type="email" :class="form.fieldClass" />
                                            </label>
                                        </div>
                                    </div>

                                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                                        <button
                                            type="button"
                                            class="rounded-2xl border border-white/15 px-6 py-3.5 text-sm font-semibold uppercase tracking-widest text-white/80 transition hover:bg-white/[0.04]"
                                            @click="form.wizardStep = 1"
                                        >
                                            {{ t('reservationBack') }}
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="form.checkingAvailability || !form.canCheckAvailability"
                                            class="rounded-2xl bg-gradient-to-r from-amber-400 to-amber-600 px-8 py-3.5 text-sm font-semibold uppercase tracking-widest text-zinc-900 shadow-lg shadow-amber-900/30 transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50"
                                            @click="form.handleCheckAvailability"
                                        >
                                            {{
                                                form.checkingAvailability
                                                    ? t('reservationCheckingAvailability')
                                                    : t('reservationCheckAvailability')
                                            }}
                                        </button>
                                    </div>
                                </div>

                                <div v-if="form.wizardStep === 3 && form.availabilityLayout" class="space-y-6">
                                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                        <h3 class="font-montserrat text-lg font-medium text-white md:text-xl">
                                            {{ t('reservationChooseTableTitle') }}
                                        </h3>
                                        <span
                                            v-if="form.selectedTables.length"
                                            class="rounded-xl border border-amber-400/35 bg-amber-500/10 px-4 py-2 text-xs font-bold uppercase tracking-wider text-amber-100 md:text-sm"
                                        >
                                            {{ t('reservationSelectedTable') }}:
                                            {{
                                                form.selectedTables
                                                    .map((table) => table.nama || `T-${table.source_table_id}`)
                                                    .join(' + ')
                                            }}
                                            ({{ form.selectedSeatTotal }}/{{ form.pax }}
                                            {{
                                                t('reservationSeatRangeHint')
                                                    .replace('{min}', String(form.pax))
                                                    .replace('{maxMulti}', String(form.maxSeatTotalForMultiTable))
                                            }})
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-2 text-[11px] font-semibold uppercase tracking-wider text-white/50">
                                        <span class="rounded-lg border border-amber-400/25 bg-amber-500/10 px-2 py-1 text-amber-200/90">
                                            {{ t('reservationLegendSmoking') }}
                                        </span>
                                        <span class="rounded-lg border border-sky-400/25 bg-sky-500/10 px-2 py-1 text-sky-200/90">
                                            {{ t('reservationLegendNonSmoking') }}
                                        </span>
                                    </div>
                                    <div class="rounded-2xl border border-sky-400/20 bg-sky-500/5 px-4 py-4">
                                        <p class="text-xs font-bold uppercase tracking-wider text-sky-200/90 md:text-sm">
                                            {{ t('reservationTableTutorialTitle') }}
                                        </p>
                                        <p class="mt-2 text-xs leading-relaxed text-sky-100/80 md:text-sm">
                                            {{ t('reservationTableTutorialStep1') }}
                                        </p>
                                        <p class="text-xs leading-relaxed text-sky-100/80 md:text-sm">
                                            {{ t('reservationTableTutorialStep2') }}
                                        </p>
                                        <p class="text-xs leading-relaxed text-sky-100/80 md:text-sm">
                                            {{ t('reservationTableTutorialStep3') }}
                                        </p>
                                        <p class="text-xs leading-relaxed text-sky-100/80 md:text-sm">
                                            {{ t('reservationTableTutorialStep4') }}
                                        </p>
                                        <p class="text-xs leading-relaxed text-sky-100/80 md:text-sm">
                                            {{ t('reservationTableTutorialStep5') }}
                                        </p>
                                    </div>
                                    <div class="scrollbar-dark overflow-x-auto overscroll-contain rounded-2xl border border-white/10 bg-[#1a1a1f] p-3 md:p-4">
                                        <div
                                            class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap"
                                            role="tablist"
                                            :aria-label="t('reservationTableSectionTabsAria')"
                                        >
                                            <button
                                                v-for="section in form.availabilityLayout.sections"
                                                :key="section.source_section_id"
                                                type="button"
                                                role="tab"
                                                :aria-selected="Number(form.activeSectionId) === Number(section.source_section_id)"
                                                class="min-h-[3.25rem] flex-1 rounded-2xl px-6 py-3.5 text-center text-sm font-bold uppercase tracking-widest transition sm:min-w-[10rem] sm:flex-none md:min-h-14 md:px-8 md:text-base"
                                                :class="
                                                    Number(form.activeSectionId) === Number(section.source_section_id)
                                                        ? 'bg-gradient-to-r from-amber-400 to-amber-600 text-zinc-900 shadow-lg shadow-amber-900/25'
                                                        : 'border-2 border-white/15 bg-white/[0.06] text-white/80 hover:border-white/25 hover:bg-white/[0.09]'
                                                "
                                                @click="form.activeSectionId = Number(section.source_section_id)"
                                            >
                                                {{ section.nama }}
                                            </button>
                                        </div>
                                        <div
                                            class="relative"
                                            style="
                                                width: 1200px;
                                                height: 900px;
                                                background-image: linear-gradient(
                                                        to right,
                                                        rgba(255, 255, 255, 0.04) 1px,
                                                        transparent 1px
                                                    ),
                                                    linear-gradient(to bottom, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
                                                background-size: 40px 40px;
                                            "
                                        >
                                            <div
                                                v-for="acc in form.currentAccessories"
                                                :key="acc.source_accessory_id"
                                                class="pointer-events-none absolute"
                                                :style="{ left: `${Number(acc.x ?? 0)}px`, top: `${Number(acc.y ?? 0)}px` }"
                                                v-html="form.renderAccessorySvg(acc)"
                                            />
                                            <div
                                                v-for="table in form.currentTables"
                                                :key="table.source_table_id"
                                                class="absolute flex flex-col items-center gap-[2px] transition-opacity"
                                                :class="
                                                    table.occupied || !table.selectable
                                                        ? 'cursor-not-allowed'
                                                        : 'cursor-pointer'
                                                "
                                                :style="{ left: `${Number(table.x ?? 0)}px`, top: `${Number(table.y ?? 0)}px` }"
                                                @click="form.onTableClick(table)"
                                            >
                                                <div
                                                    class="relative rounded-xl transition"
                                                    :class="
                                                        form.selectedTableIds.includes(Number(table.source_table_id))
                                                            ? 'ring-4 ring-amber-400/80 ring-offset-2 ring-offset-[#1a1a1f]'
                                                            : ''
                                                    "
                                                >
                                                    <div v-html="form.renderTableSvg(table)" />
                                                    <div
                                                        v-if="(table.tipe || 'biasa') === 'biasa'"
                                                        class="pointer-events-none absolute inset-0 flex items-center justify-center"
                                                    >
                                                        <span
                                                            class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1"
                                                            :class="
                                                                table.occupied
                                                                    ? 'bg-rose-100/95 text-rose-700 ring-rose-200'
                                                                    : 'bg-violet-100/95 text-violet-700 ring-violet-200'
                                                            "
                                                        >
                                                            Seat {{ getTableSeatingCapacity(table) || '-' }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div
                                                    class="flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold shadow-sm ring-1"
                                                    :class="
                                                        table.occupied
                                                            ? 'bg-rose-50 text-rose-800 ring-rose-200'
                                                            : 'bg-white/95 text-slate-800 ring-slate-200'
                                                    "
                                                >
                                                    <span>{{ table.nama || `T-${table.source_table_id}` }}</span>
                                                    <span
                                                        v-if="table.occupied"
                                                        class="inline-flex h-4 min-w-4 items-center justify-center rounded bg-rose-100 px-1 text-[9px] font-black leading-none text-rose-700"
                                                    >
                                                        X
                                                    </span>
                                                    <span
                                                        class="inline-flex h-4 min-w-4 items-center justify-center rounded px-1 text-[9px] font-black leading-none"
                                                        :class="
                                                            (table.smoking_type || 'non_smoking') === 'smoking'
                                                                ? 'bg-amber-100 text-amber-700'
                                                                : 'bg-sky-100 text-sky-700'
                                                        "
                                                    >
                                                        {{
                                                            (table.smoking_type || 'non_smoking') === 'smoking' ? 'S' : 'NS'
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <label class="block rounded-2xl border border-white/10 bg-white/[0.02] p-5 md:p-6">
                                        <span
                                            class="font-montserrat text-sm font-medium uppercase tracking-widest text-white/50"
                                        >
                                            {{ t('reservationTableNotesLabel') }}
                                        </span>
                                        <p class="mt-1.5 text-xs leading-relaxed text-white/40">
                                            {{ t('reservationTableNotesHint') }}
                                        </p>
                                        <textarea
                                            v-model="form.tableLayoutNotes"
                                            rows="3"
                                            :class="form.fieldClass + ' mt-3 resize-y'"
                                            :placeholder="t('reservationTableNotesPlaceholder')"
                                        />
                                    </label>
                                    <div class="space-y-6 border-t border-white/10 pt-8">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <button
                                                type="button"
                                                class="rounded-2xl border border-white/15 px-6 py-3.5 text-sm font-semibold uppercase tracking-widest text-white/80 transition hover:bg-white/[0.04]"
                                                @click="form.resetAvailabilityState()"
                                            >
                                                {{ t('reservationBack') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="text-xs font-semibold uppercase tracking-widest text-amber-400/90 underline-offset-4 hover:underline"
                                                @click="form.resetAvailabilityState()"
                                            >
                                                {{ t('reservationCheckAgain') }}
                                            </button>
                                        </div>
                                        <div class="space-y-6">
                                            <div>
                                                <p
                                                    class="font-montserrat text-xs font-medium uppercase tracking-widest text-amber-400/90"
                                                >
                                                    {{ t('reservationFinalizeSectionTitle') }}
                                                </p>
                                                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-white/55">
                                                    {{ t('reservationFinalizeSectionLead') }}
                                                </p>
                                            </div>
                                            <div>
                                                <p
                                                    class="font-montserrat text-xs font-medium uppercase tracking-widest text-white/50"
                                                >
                                                    {{ t('reservationOrderingSectionTitle') }}
                                                </p>
                                                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-white/45">
                                                    {{ t('reservationOrderingSectionLead') }}
                                                </p>
                                                <div
                                                    class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2"
                                                    role="radiogroup"
                                                    :aria-label="t('reservationOrderChannelGroupAria')"
                                                >
                                                    <label
                                                        class="flex cursor-pointer gap-3 rounded-2xl border p-5 text-left transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-amber-400/50"
                                                        :class="
                                                            form.orderChannel === 'self'
                                                                ? 'border-amber-400/50 bg-amber-500/10 ring-2 ring-amber-400/55'
                                                                : 'border-white/12 bg-white/[0.03] hover:border-white/20'
                                                        "
                                                    >
                                                        <span
                                                            class="relative mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-white/35 bg-white/[0.04]"
                                                        >
                                                            <span
                                                                v-if="form.orderChannel === 'self'"
                                                                class="h-2.5 w-2.5 rounded-full bg-amber-400 shadow-sm shadow-amber-500/50"
                                                            />
                                                        </span>
                                                        <input
                                                            v-model="form.orderChannel"
                                                            type="radio"
                                                            class="sr-only"
                                                            value="self"
                                                            name="reservation-order-channel"
                                                        />
                                                        <div class="min-w-0 flex-1">
                                                            <p
                                                                class="font-montserrat text-sm font-semibold uppercase tracking-wide text-white/90"
                                                            >
                                                                {{ t('reservationOrderSelfCardTitle') }}
                                                            </p>
                                                            <p class="mt-2 text-xs leading-relaxed text-white/45">
                                                                {{ t('reservationOrderSelfCardBody') }}
                                                            </p>
                                                        </div>
                                                    </label>
                                                    <label
                                                        class="flex cursor-pointer gap-3 rounded-2xl border p-5 text-left transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-400/50"
                                                        :class="
                                                            form.orderChannel === 'manual'
                                                                ? 'border-emerald-400/50 bg-emerald-500/10 ring-2 ring-emerald-400/50'
                                                                : 'border-emerald-500/20 bg-emerald-500/[0.06] hover:border-emerald-400/35'
                                                        "
                                                    >
                                                        <span
                                                            class="relative mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-emerald-400/40 bg-emerald-500/10"
                                                        >
                                                            <span
                                                                v-if="form.orderChannel === 'manual'"
                                                                class="h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-600/40"
                                                            />
                                                        </span>
                                                        <input
                                                            v-model="form.orderChannel"
                                                            type="radio"
                                                            class="sr-only"
                                                            value="manual"
                                                            name="reservation-order-channel"
                                                        />
                                                        <div class="min-w-0 flex-1">
                                                            <p
                                                                class="font-montserrat text-sm font-semibold uppercase tracking-wide text-emerald-100/95"
                                                            >
                                                                {{ t('reservationOrderManualCardTitle') }}
                                                            </p>
                                                            <p class="mt-2 text-xs leading-relaxed text-emerald-100/55">
                                                                {{ t('reservationOrderManualCardBody') }}
                                                            </p>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                            <button
                                                type="submit"
                                                :disabled="
                                                    !form.name.trim() ||
                                                    !form.phone.trim() ||
                                                    !form.isSelectionValid ||
                                                    !form.orderChannel ||
                                                    form.channelActionLoading
                                                "
                                                class="w-full rounded-2xl bg-gradient-to-r from-amber-400 to-amber-600 py-4 text-sm font-semibold uppercase tracking-widest text-zinc-900 shadow-lg shadow-amber-900/30 transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-45"
                                            >
                                                {{
                                                    form.orderChannel === 'self' && form.channelActionLoading
                                                        ? t('reservationOpeningMenu')
                                                        : form.orderChannel
                                                          ? t('reservationWizardNext')
                                                          : t('reservationContinuePickChannel')
                                                }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="form.wizardStep === 3 && !form.availabilityLayout"
                                    class="py-12 text-center text-white/50"
                                >
                                    <p class="text-sm">{{ t('reservationFillAvailabilityFirst') }}</p>
                                    <button
                                        type="button"
                                        class="mt-6 rounded-2xl border border-white/15 px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white/80"
                                        @click="form.wizardStep = 2"
                                    >
                                        {{ t('reservationBack') }}
                                    </button>
                                </div>

                                <!-- Manual summary step 4 -->
                                <div v-if="form.wizardStep === 4 && form.orderChannel === 'manual'" class="space-y-6">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <button
                                            type="button"
                                            class="rounded-2xl border border-white/15 px-6 py-3 text-sm font-semibold uppercase tracking-widest text-white/80 transition hover:bg-white/[0.04]"
                                            @click="form.wizardStep = 3"
                                        >
                                            {{ t('reservationBack') }}
                                        </button>
                                    </div>
                                    <div class="rounded-2xl border border-emerald-400/25 bg-emerald-500/[0.07] p-6 md:p-8">
                                        <h3 class="font-montserrat text-lg font-medium text-white md:text-xl">
                                            {{ t('reservationSummaryStep') }}
                                        </h3>
                                        <p class="mt-2 text-sm leading-relaxed text-emerald-100/70">
                                            {{ t('reservationSummaryLead') }}
                                        </p>
                                        <div class="mt-6 rounded-xl border border-white/10 bg-[#121215]/80 px-4 py-2">
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationOutlet') }}</span>
                                                <span class="max-w-[min(100%,24rem)] text-right font-medium text-white/90">
                                                    {{ form.selectedOutlet?.name ?? '—' }}
                                                </span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationDate') }}</span>
                                                <span class="font-medium text-white/90">{{ form.date || '—' }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationTime') }}</span>
                                                <span class="font-medium text-white/90">{{ form.time || '—' }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationPax') }}</span>
                                                <span class="font-medium text-white/90">{{ form.pax }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationDeposit') }}</span>
                                                <span class="font-semibold text-amber-100">{{
                                                    form.formatCurrency(form.reservationDepositAmount)
                                                }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationSummaryOrderingMethod') }}</span>
                                                <span class="font-medium text-emerald-100/95">{{
                                                    t('reservationOrderManualCardTitle')
                                                }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationSummaryAreaLabel') }}</span>
                                                <span class="font-medium text-white/90">{{ form.smokingSummaryLabel }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationSelectedTable') }}</span>
                                                <span class="max-w-[min(100%,24rem)] text-right font-medium text-white/90">{{
                                                    form.tableNamesSummary
                                                }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationName') }}</span>
                                                <span class="max-w-[min(100%,24rem)] text-right font-medium text-white/90">{{
                                                    form.name.trim() || '—'
                                                }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationPhone') }}</span>
                                                <span class="font-medium text-white/90">{{ form.phone.trim() || '—' }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationEmail') }}</span>
                                                <span
                                                    class="max-w-[min(100%,24rem)] break-all text-right font-medium text-white/90"
                                                    >{{ form.email.trim() || '—' }}</span
                                                >
                                            </div>
                                            <div
                                                v-if="form.notes.trim()"
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationNotes') }}</span>
                                                <span
                                                    class="max-w-[min(100%,24rem)] whitespace-pre-wrap text-right font-medium text-white/90"
                                                    >{{ form.notes.trim() }}</span
                                                >
                                            </div>
                                            <div
                                                v-if="form.tableLayoutNotes.trim()"
                                                class="flex flex-wrap items-baseline justify-between gap-2 py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationTableNotesLabel') }}</span>
                                                <span
                                                    class="max-w-[min(100%,24rem)] whitespace-pre-wrap text-right font-medium text-white/90"
                                                    >{{ form.tableLayoutNotes.trim() }}</span
                                                >
                                            </div>
                                        </div>
                                        <div class="mt-6 rounded-xl border border-emerald-400/20 bg-emerald-500/[0.06] px-4 py-4">
                                            <p class="font-montserrat text-sm font-semibold uppercase tracking-wide text-white/90">
                                                {{ t('reservationTermsTitle') }}
                                            </p>
                                            <div
                                                class="scrollbar-dark mt-3 max-h-[min(14rem,35vh)] overflow-y-auto overscroll-contain pr-1"
                                            >
                                                <ol
                                                    class="list-decimal space-y-2.5 pl-4 text-sm leading-relaxed text-white/70 marker:text-amber-400/80"
                                                >
                                                    <li v-for="key in form.reservationTermKeys" :key="key">
                                                        {{ t(key) }}
                                                    </li>
                                                </ol>
                                            </div>
                                            <label
                                                class="mt-4 flex cursor-pointer gap-3 rounded-lg border border-white/10 bg-black/20 px-3 py-3 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-amber-400/40"
                                            >
                                                <input
                                                    v-model="form.acceptedReservationTerms"
                                                    type="checkbox"
                                                    class="mt-0.5 h-4 w-4 shrink-0 accent-amber-500"
                                                />
                                                <span class="text-sm leading-snug text-white/85">{{
                                                    t('reservationTermsCheckbox')
                                                }}</span>
                                            </label>
                                        </div>
                                        <button
                                            type="button"
                                            :disabled="form.isSubmitting || !form.acceptedReservationTerms"
                                            class="mt-8 w-full rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 py-4 text-sm font-semibold uppercase tracking-widest text-white shadow-lg shadow-emerald-900/35 transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50"
                                            @click="form.handleManualSubmitFromSummary"
                                        >
                                            {{
                                                form.isSubmitting
                                                    ? t('reservationSubmitting')
                                                    : t('reservationManualSubmitOpenWa')
                                            }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Self order menu step 4 -->
                                <div v-if="form.wizardStep === 4 && form.orderChannel === 'self'" class="space-y-6">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <button
                                            type="button"
                                            class="rounded-2xl border border-white/15 px-6 py-3 text-sm font-semibold uppercase tracking-widest text-white/80 transition hover:bg-white/[0.04]"
                                            @click="form.wizardStep = 3"
                                        >
                                            {{ t('reservationBack') }}
                                        </button>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                            <h4 class="font-montserrat text-base font-medium text-white">
                                                {{ t('reservationOrderSelf') }}
                                            </h4>
                                            <div class="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-xs font-bold uppercase tracking-wider text-white hover:bg-white/15"
                                                    @click="form.viewCartOpen = true"
                                                >
                                                    View Cart ({{ form.selfOrderTotalQty }})
                                                </button>
                                                <span class="text-sm font-medium text-amber-200/90">{{
                                                    form.formatCurrency(form.selfOrderSubtotal)
                                                }}</span>
                                            </div>
                                        </div>
                                        <p v-if="form.selfOrderLoading" class="text-sm text-white/50">Loading menu...</p>
                                        <template v-else-if="form.selfOrderMenu">
                                            <div class="mb-4 space-y-3">
                                                <input
                                                    v-model="form.selfOrderSearch"
                                                    placeholder="Search menu..."
                                                    class="w-full rounded-xl border border-white/10 bg-white/[0.06] px-3 py-2.5 text-sm text-white outline-none placeholder:text-white/35 focus:border-amber-400/40"
                                                />
                                                <div class="flex flex-wrap gap-2">
                                                    <button
                                                        type="button"
                                                        class="rounded-lg px-3 py-1.5 text-xs font-semibold uppercase tracking-wider"
                                                        :class="
                                                            form.selfOrderCategoryId == null
                                                                ? 'bg-amber-500 text-zinc-900'
                                                                : 'border border-white/10 bg-white/5 text-white/70 hover:bg-white/10'
                                                        "
                                                        @click="form.selfOrderCategoryId = null"
                                                    >
                                                        All
                                                    </button>
                                                    <button
                                                        v-for="cat in form.selfOrderMenu.categories"
                                                        :key="cat.id"
                                                        type="button"
                                                        class="rounded-lg px-3 py-1.5 text-xs font-semibold uppercase tracking-wider"
                                                        :class="
                                                            form.selfOrderCategoryId === cat.id
                                                                ? 'bg-amber-500 text-zinc-900'
                                                                : 'border border-white/10 bg-white/5 text-white/70 hover:bg-white/10'
                                                        "
                                                        @click="form.selfOrderCategoryId = cat.id"
                                                    >
                                                        {{ cat.name }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="scrollbar-dark max-h-[420px] overflow-y-auto overscroll-contain pr-1">
                                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                    <div
                                                        v-for="item in form.selfOrderFilteredItems"
                                                        :key="item.id"
                                                        class="rounded-xl border border-white/10 bg-white/[0.04] p-3"
                                                    >
                                                        <button
                                                            type="button"
                                                            class="flex w-full min-w-0 items-start gap-3 text-left"
                                                            @click="form.openItemDetail(item.id)"
                                                        >
                                                            <span
                                                                v-if="
                                                                    form.buildStorageImageUrl(
                                                                        form.erpWebBaseUrl,
                                                                        item.image_path,
                                                                    )
                                                                "
                                                                class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white/5 ring-1 ring-white/10"
                                                            >
                                                                <img
                                                                    :src="
                                                                        form.buildStorageImageUrl(
                                                                            form.erpWebBaseUrl,
                                                                            item.image_path,
                                                                        )
                                                                    "
                                                                    :alt="item.name"
                                                                    class="max-h-full max-w-full object-contain"
                                                                />
                                                            </span>
                                                            <div
                                                                v-else
                                                                class="flex h-16 w-16 items-center justify-center rounded-lg bg-white/10 text-[10px] font-semibold text-white/50"
                                                            >
                                                                NO IMG
                                                            </div>
                                                            <div class="min-w-0 flex-1">
                                                                <p class="truncate text-sm font-semibold text-white">
                                                                    {{ item.name }}
                                                                </p>
                                                                <p class="text-xs text-white/50">
                                                                    {{ item.category_name || '-' }}
                                                                </p>
                                                                <p class="mt-1 text-sm font-bold text-amber-200/90">
                                                                    {{ form.formatCurrency(item.price) }}
                                                                </p>
                                                                <p class="mt-1 text-[11px] font-medium text-sky-300/90">
                                                                    Klik untuk atur qty, modifier, notes
                                                                </p>
                                                            </div>
                                                            <span
                                                                v-if="(form.selfOrderCart[item.id] ?? 0) > 0"
                                                                class="rounded-full bg-amber-500 px-2.5 py-1 text-xs font-bold text-zinc-900"
                                                            >
                                                                Qty {{ form.selfOrderCart[item.id] }}
                                                            </span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <p
                                                    v-if="form.selfOrderFilteredItems.length === 0"
                                                    class="mt-3 rounded-xl border border-dashed border-white/15 px-3 py-4 text-center text-sm text-white/45"
                                                >
                                                    Menu tidak ditemukan.
                                                </p>
                                            </div>
                                            <div
                                                class="sticky bottom-0 mt-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-white/10 bg-[#121215] p-3"
                                            >
                                                <p class="text-sm text-white/70">
                                                    {{ form.selfOrderTotalQty }} qty ({{
                                                        form.selfOrderSelectedItems.length
                                                    }}
                                                    item) | {{ form.formatCurrency(form.selfOrderSubtotal) }}
                                                </p>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <button
                                                        type="button"
                                                        :disabled="form.selfOrderSelectedItems.length === 0"
                                                        class="rounded-lg border border-white/15 px-3 py-2 text-xs font-bold uppercase text-white disabled:cursor-not-allowed disabled:opacity-50"
                                                        @click="form.viewCartOpen = true"
                                                    >
                                                        View Cart
                                                    </button>
                                                    <button
                                                        type="button"
                                                        :disabled="form.selfOrderSelectedItems.length === 0"
                                                        class="rounded-lg bg-gradient-to-r from-amber-400 to-amber-600 px-4 py-2 text-sm font-semibold uppercase text-zinc-900 disabled:cursor-not-allowed disabled:opacity-50"
                                                        @click="form.handleGoToSelfSummaryFromMenu"
                                                    >
                                                        {{ t('reservationSummaryNext') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                        <p v-else class="text-sm text-white/50">Menu belum tersedia.</p>
                                        <p
                                            v-if="form.selfOrderError"
                                            class="mt-4 rounded-xl border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200"
                                        >
                                            {{ form.selfOrderError }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Self summary step 5 -->
                                <div v-if="form.wizardStep === 5 && form.orderChannel === 'self'" class="space-y-6">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <button
                                            type="button"
                                            class="rounded-2xl border border-white/15 px-6 py-3 text-sm font-semibold uppercase tracking-widest text-white/80 transition hover:bg-white/[0.04]"
                                            @click="form.wizardStep = 4"
                                        >
                                            {{ t('reservationBack') }}
                                        </button>
                                    </div>
                                    <div class="rounded-2xl border border-amber-400/25 bg-amber-500/[0.08] p-6 md:p-8">
                                        <h3 class="font-montserrat text-lg font-medium text-white md:text-xl">
                                            {{ t('reservationSummaryStep') }}
                                        </h3>
                                        <p class="mt-2 text-sm leading-relaxed text-amber-100/75">
                                            {{ t('reservationSummaryLead') }}
                                        </p>
                                        <div class="mt-6 rounded-xl border border-white/10 bg-[#121215]/80 px-4 py-2">
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationOutlet') }}</span>
                                                <span class="max-w-[min(100%,24rem)] text-right font-medium text-white/90">{{
                                                    form.selectedOutlet?.name ?? '—'
                                                }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationDate') }}</span>
                                                <span class="font-medium text-white/90">{{ form.date || '—' }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationTime') }}</span>
                                                <span class="font-medium text-white/90">{{ form.time || '—' }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationPax') }}</span>
                                                <span class="font-medium text-white/90">{{ form.pax }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationDeposit') }}</span>
                                                <span class="font-semibold text-amber-100">{{
                                                    form.formatCurrency(form.reservationDepositAmount)
                                                }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationSummaryOrderingMethod') }}</span>
                                                <span class="font-medium text-amber-100/95">{{
                                                    t('reservationOrderSelfCardTitle')
                                                }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationSummaryAreaLabel') }}</span>
                                                <span class="font-medium text-white/90">{{ form.smokingSummaryLabel }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationSelectedTable') }}</span>
                                                <span class="max-w-[min(100%,24rem)] text-right font-medium text-white/90">{{
                                                    form.tableNamesSummary
                                                }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationName') }}</span>
                                                <span class="max-w-[min(100%,24rem)] text-right font-medium text-white/90">{{
                                                    form.name.trim() || '—'
                                                }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationPhone') }}</span>
                                                <span class="font-medium text-white/90">{{ form.phone.trim() || '—' }}</span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationEmail') }}</span>
                                                <span
                                                    class="max-w-[min(100%,24rem)] break-all text-right font-medium text-white/90"
                                                    >{{ form.email.trim() || '—' }}</span
                                                >
                                            </div>
                                            <div
                                                v-if="form.notes.trim()"
                                                class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationNotes') }}</span>
                                                <span
                                                    class="max-w-[min(100%,24rem)] whitespace-pre-wrap text-right font-medium text-white/90"
                                                    >{{ form.notes.trim() }}</span
                                                >
                                            </div>
                                            <div
                                                v-if="form.tableLayoutNotes.trim()"
                                                class="flex flex-wrap items-baseline justify-between gap-2 py-2.5 text-sm"
                                            >
                                                <span class="text-white/45">{{ t('reservationTableNotesLabel') }}</span>
                                                <span
                                                    class="max-w-[min(100%,24rem)] whitespace-pre-wrap text-right font-medium text-white/90"
                                                    >{{ form.tableLayoutNotes.trim() }}</span
                                                >
                                            </div>
                                        </div>
                                        <div class="mt-8">
                                            <p
                                                class="font-montserrat text-xs font-medium uppercase tracking-widest text-amber-400/90"
                                            >
                                                {{ t('reservationOrderSelf') }}
                                            </p>
                                            <div class="mt-3 space-y-2 rounded-xl border border-white/10 bg-[#121215]/80 p-4">
                                                <div
                                                    v-for="item in form.selfOrderSelectedItems"
                                                    :key="item.id"
                                                    class="border-b border-white/[0.06] py-2 text-sm last:border-0 last:pb-0"
                                                >
                                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                                        <div class="min-w-0">
                                                            <p class="font-semibold text-white">{{ item.name }}</p>
                                                            <p class="text-xs text-white/50">
                                                                {{ form.formatCurrency(item.price) }} × {{ item.qty }}
                                                            </p>
                                                            <p
                                                                v-if="form.getModifierSummary(item).length > 0"
                                                                class="mt-1 text-xs text-white/55"
                                                            >
                                                                {{ form.getModifierSummary(item).join(' · ') }}
                                                            </p>
                                                            <p v-if="item.notes" class="mt-1 text-xs italic text-white/45">
                                                                {{ item.notes }}
                                                            </p>
                                                        </div>
                                                        <p class="shrink-0 font-bold text-amber-200/90">
                                                            {{
                                                                form.formatCurrency((item.price || 0) * (item.qty || 0))
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                class="mt-4 flex items-center justify-between rounded-xl border border-amber-400/25 bg-amber-500/10 px-4 py-3"
                                            >
                                                <span class="text-sm font-semibold text-white/80">
                                                    {{
                                                        lang === 'id'
                                                            ? `${form.selfOrderTotalQty} qty · ${form.selfOrderSelectedItems.length} menu`
                                                            : `${form.selfOrderTotalQty} qty · ${form.selfOrderSelectedItems.length} items`
                                                    }}
                                                </span>
                                                <span class="text-base font-bold text-amber-100">{{
                                                    form.formatCurrency(form.selfOrderSubtotal)
                                                }}</span>
                                            </div>
                                        </div>
                                        <div class="mt-6 rounded-xl border border-amber-400/20 bg-amber-500/[0.08] px-4 py-4">
                                            <p class="font-montserrat text-sm font-semibold uppercase tracking-wide text-white/90">
                                                {{ t('reservationTermsTitle') }}
                                            </p>
                                            <div
                                                class="scrollbar-dark mt-3 max-h-[min(14rem,35vh)] overflow-y-auto overscroll-contain pr-1"
                                            >
                                                <ol
                                                    class="list-decimal space-y-2.5 pl-4 text-sm leading-relaxed text-white/70 marker:text-amber-400/80"
                                                >
                                                    <li v-for="key in form.reservationTermKeys" :key="key + '-s'">
                                                        {{ t(key) }}
                                                    </li>
                                                </ol>
                                            </div>
                                            <label
                                                class="mt-4 flex cursor-pointer gap-3 rounded-lg border border-white/10 bg-black/20 px-3 py-3"
                                            >
                                                <input
                                                    v-model="form.acceptedReservationTerms"
                                                    type="checkbox"
                                                    class="mt-0.5 h-4 w-4 shrink-0 accent-amber-500"
                                                />
                                                <span class="text-sm leading-snug text-white/85">{{
                                                    t('reservationTermsCheckbox')
                                                }}</span>
                                            </label>
                                        </div>
                                        <button
                                            type="button"
                                            :disabled="
                                                form.isSubmitting ||
                                                form.selfOrderSelectedItems.length === 0 ||
                                                !form.acceptedReservationTerms
                                            "
                                            class="mt-8 w-full rounded-2xl bg-gradient-to-r from-amber-400 to-amber-600 py-4 text-sm font-semibold uppercase tracking-widest text-zinc-900 shadow-lg shadow-amber-900/30 transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50"
                                            @click="form.handleSubmitSelfOrder"
                                        >
                                            {{
                                                form.isSubmitting
                                                    ? t('reservationSubmitting')
                                                    : t('reservationConfirmSelfOrderSubmit')
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </template>

                        <div v-else class="mx-auto max-w-3xl py-6 md:py-10">
                            <div class="rounded-3xl border border-emerald-400/25 bg-emerald-500/[0.06] p-5 md:p-7">
                                <div
                                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-3xl text-white shadow-lg shadow-emerald-900/40"
                                >
                                    ✓
                                </div>
                                <h2 class="font-montserrat text-center text-2xl font-light text-white md:text-3xl">
                                    Thank You
                                </h2>
                                <p class="mt-2 text-center text-sm text-emerald-100/80 md:text-base">
                                    {{
                                        lang === 'id'
                                            ? 'Reservasi Anda berhasil dibuat. Silakan simpan QR code dan nomor reservasi berikut.'
                                            : 'Your reservation is confirmed. Please save the QR code and reservation number below.'
                                    }}
                                </p>
                                <div class="mt-6 grid gap-5 md:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)]">
                                    <div class="rounded-2xl border border-white/10 bg-[#121215]/80 px-4 py-3">
                                        <div
                                            class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2 text-sm"
                                        >
                                            <span class="text-white/45">No. Reservasi</span>
                                            <span class="font-bold text-amber-200">{{
                                                form.successInfo?.reservationNumber || '-'
                                            }}</span>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2 text-sm"
                                        >
                                            <span class="text-white/45">{{ t('reservationName') }}</span>
                                            <span class="font-medium text-white/90">{{ form.successInfo?.name || '-' }}</span>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2 text-sm"
                                        >
                                            <span class="text-white/45">{{ t('reservationPhone') }}</span>
                                            <span class="font-medium text-white/90">{{ form.successInfo?.phone || '-' }}</span>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2 text-sm"
                                        >
                                            <span class="text-white/45">{{ t('reservationOutlet') }}</span>
                                            <span class="text-right font-medium text-white/90">{{
                                                form.successInfo?.outlet || '-'
                                            }}</span>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2 text-sm"
                                        >
                                            <span class="text-white/45">{{ t('reservationDate') }}</span>
                                            <span class="font-medium text-white/90">{{ form.successInfo?.date || '-' }}</span>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2 text-sm"
                                        >
                                            <span class="text-white/45">{{ t('reservationTime') }}</span>
                                            <span class="font-medium text-white/90">{{ form.successInfo?.time || '-' }}</span>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2 text-sm"
                                        >
                                            <span class="text-white/45">{{ t('reservationPax') }}</span>
                                            <span class="font-medium text-white/90">{{ form.successInfo?.pax ?? '-' }}</span>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2 text-sm"
                                        >
                                            <span class="text-white/45">{{ t('reservationDeposit') }}</span>
                                            <span class="font-semibold text-amber-100">{{
                                                form.formatCurrency((form.successInfo?.pax || 0) * 100000)
                                            }}</span>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/[0.06] py-2 text-sm"
                                        >
                                            <span class="text-white/45">{{ t('reservationSelectedTable') }}</span>
                                            <span class="text-right font-medium text-white/90">{{
                                                form.successInfo?.table || '-'
                                            }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-baseline justify-between gap-2 py-2 text-sm">
                                            <span class="text-white/45">{{ t('reservationSummaryOrderingMethod') }}</span>
                                            <span class="font-medium text-white/90">{{
                                                form.successInfo?.orderMethod === 'manual'
                                                    ? t('reservationOrderManualCardTitle')
                                                    : t('reservationOrderSelfCardTitle')
                                            }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-center rounded-2xl border border-white/10 bg-[#121215]/80 p-4">
                                        <img
                                            v-if="form.successQrUrl"
                                            :src="form.successQrUrl"
                                            alt="Reservation QR"
                                            class="h-52 w-52 rounded-xl border border-white/10 bg-white p-2"
                                        />
                                        <div
                                            v-else
                                            class="flex h-52 w-52 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-sm text-white/45"
                                        >
                                            QR unavailable
                                        </div>
                                        <button
                                            type="button"
                                            class="mt-4 w-full rounded-xl border border-white/15 bg-white/[0.06] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/[0.1]"
                                            @click="form.handleDownloadSuccessQr"
                                        >
                                            Download QR Code
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-6 grid gap-3 md:grid-cols-2">
                                    <Link
                                        href="/reservation"
                                        class="inline-flex justify-center rounded-2xl border border-white/15 px-6 py-3 text-sm font-semibold uppercase tracking-widest text-white/85 transition hover:bg-white/[0.06]"
                                    >
                                        {{
                                            lang === 'id' ? 'Kembali ke Reservasi' : 'Back to Reservation'
                                        }}
                                    </Link>
                                    <Link
                                        href="/"
                                        class="inline-flex justify-center rounded-2xl bg-gradient-to-r from-amber-400 to-amber-600 px-6 py-3 text-sm font-semibold uppercase tracking-widest text-zinc-900 transition hover:brightness-105"
                                    >
                                        {{ lang === 'id' ? 'Kembali ke Home' : 'Go to Home' }}
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <p
                            v-if="!form.submitted && form.message"
                            class="mt-6 rounded-2xl border border-sky-400/25 bg-sky-500/10 px-4 py-3 text-sm text-sky-100 md:text-base"
                        >
                            {{ form.message }}
                        </p>
                        <p
                            v-if="!form.submitted && form.submitError"
                            class="mt-6 rounded-2xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100 md:text-base"
                        >
                            {{ form.submitError }}
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Item detail modal -->
        <div
            v-if="form.detailItem"
            class="scrollbar-dark fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 px-4 py-6 backdrop-blur-sm"
        >
            <div
                class="my-auto flex max-h-[min(85dvh,calc(100dvh-2rem))] w-full max-w-md flex-col rounded-2xl border border-white/10 bg-[#1a1a1f] text-white shadow-2xl"
                role="dialog"
                aria-modal="true"
            >
                <div class="shrink-0 border-b border-white/10 px-5 pb-4 pt-5">
                    <h5 id="reservation-item-detail-title" class="text-base font-bold uppercase leading-snug">
                        {{ form.detailItem.name }}
                    </h5>
                    <p class="mt-1 text-sm text-white/60">{{ form.formatCurrency(form.detailItem.price) }}</p>
                </div>
                <div class="scrollbar-dark min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold">Qty</span>
                        <button
                            type="button"
                            class="h-8 w-8 rounded-lg bg-white/10 text-base font-bold"
                            @click="form.detailQty = Math.max(0, form.detailQty - 1)"
                        >
                            -
                        </button>
                        <span class="w-8 text-center text-sm font-bold">{{ form.detailQty }}</span>
                        <button
                            type="button"
                            class="h-8 w-8 rounded-lg bg-white/10 text-base font-bold"
                            @click="form.detailQty = Math.min(99, form.detailQty + 1)"
                        >
                            +
                        </button>
                    </div>
                    <label class="mt-4 block">
                        <span class="text-xs font-semibold uppercase text-white/50">Notes</span>
                        <textarea
                            v-model="form.detailNotes"
                            rows="2"
                            class="mt-1 w-full resize-y rounded-xl border border-white/10 bg-white/[0.06] px-3 py-2 text-sm text-white outline-none focus:border-amber-400/40"
                        />
                    </label>
                    <div v-if="form.detailItem.modifiers?.length" class="mt-4 space-y-2">
                        <span class="text-xs font-semibold uppercase text-white/50">Modifiers</span>
                        <div
                            v-for="group in form.detailItem.modifiers"
                            :key="group.modifier_id"
                            class="rounded-xl border border-white/10 bg-white/[0.04] p-2"
                        >
                            <p class="text-xs font-bold uppercase text-white/70">{{ group.modifier_name }}</p>
                            <div class="mt-2 space-y-1">
                                <div
                                    v-for="option in group.options"
                                    :key="option.id"
                                    class="flex items-center justify-between gap-2 rounded-lg border border-white/10 bg-[#121215] px-2 py-2 text-sm"
                                >
                                    <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-2">
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 shrink-0 accent-amber-500"
                                            :checked="Boolean(form.detailSelectedModifiers[group.modifier_id]?.[option.id])"
                                            @change="
                                                form.handleToggleDetailModifier(group.modifier_id, option.id)
                                            "
                                        />
                                        <span class="min-w-0 break-words leading-snug">{{ option.name }}</span>
                                    </label>
                                    <div
                                        v-if="form.detailSelectedModifiers[group.modifier_id]?.[option.id]"
                                        class="flex shrink-0 items-center gap-1"
                                    >
                                        <button
                                            type="button"
                                            class="h-7 w-7 rounded-lg bg-white/10 text-sm font-bold"
                                            @click="
                                                form.handleDetailModifierQty(group.modifier_id, option.id, -1)
                                            "
                                        >
                                            -
                                        </button>
                                        <span class="w-6 text-center text-xs font-bold">{{
                                            form.detailSelectedModifiers[group.modifier_id]?.[option.id] ?? 0
                                        }}</span>
                                        <button
                                            type="button"
                                            class="h-7 w-7 rounded-lg bg-white/10 text-sm font-bold"
                                            @click="
                                                form.handleDetailModifierQty(group.modifier_id, option.id, 1)
                                            "
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="shrink-0 border-t border-white/10 px-5 pb-5 pt-4">
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="rounded-xl bg-white/10 px-3 py-2.5 text-sm font-semibold"
                            @click="form.detailItemId = null"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-gradient-to-r from-amber-400 to-amber-600 px-3 py-2.5 text-sm font-semibold text-zinc-900"
                            @click="form.saveItemDetail"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart modal -->
        <div
            v-if="form.viewCartOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-lg rounded-2xl border border-white/10 bg-[#1a1a1f] p-5 text-white shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <h5 class="text-base font-bold uppercase">Your Cart</h5>
                    <button
                        type="button"
                        class="rounded-lg bg-white/10 px-2 py-1 text-xs font-bold uppercase"
                        @click="form.viewCartOpen = false"
                    >
                        Close
                    </button>
                </div>
                <p
                    v-if="form.selfOrderSelectedItems.length === 0"
                    class="rounded-xl border border-dashed border-white/15 px-3 py-4 text-center text-sm text-white/50"
                >
                    Cart masih kosong.
                </p>
                <template v-else>
                    <div class="scrollbar-dark max-h-[320px] space-y-2 overflow-y-auto overscroll-contain pr-1">
                        <button
                            v-for="item in form.selfOrderSelectedItems"
                            :key="'c' + item.id"
                            type="button"
                            class="w-full rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2 text-left hover:bg-white/[0.07]"
                            @click="
                                () => {
                                    form.viewCartOpen = false;
                                    form.openItemDetail(item.id);
                                }
                            "
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ item.name }}</p>
                                    <p class="text-xs text-white/50">
                                        {{ form.formatCurrency(item.price) }} x {{ item.qty }}
                                    </p>
                                    <p v-if="form.getModifierSummary(item).length" class="mt-1 text-xs text-white/60">
                                        Modifier: {{ form.getModifierSummary(item).join(' | ') }}
                                    </p>
                                    <p v-if="item.notes" class="mt-1 text-xs italic text-white/50">
                                        Notes: {{ item.notes }}
                                    </p>
                                </div>
                                <p class="text-sm font-bold text-amber-200/90">
                                    {{ form.formatCurrency((item.price || 0) * (item.qty || 0)) }}
                                </p>
                            </div>
                        </button>
                    </div>
                    <div class="mt-4 flex items-center justify-between rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2">
                        <p class="text-sm font-semibold">
                            {{ form.selfOrderTotalQty }} qty ({{ form.selfOrderSelectedItems.length }} item)
                        </p>
                        <p class="text-sm font-bold text-amber-200/90">
                            {{ form.formatCurrency(form.selfOrderSubtotal) }}
                        </p>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="rounded-xl bg-white/10 px-3 py-2.5 text-sm font-semibold"
                            @click="form.viewCartOpen = false"
                        >
                            Continue Shopping
                        </button>
                        <button
                            type="button"
                            :disabled="form.selfOrderSelectedItems.length === 0"
                            class="rounded-xl bg-gradient-to-r from-amber-400 to-amber-600 px-3 py-2.5 text-sm font-semibold text-zinc-900 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="
                                () => {
                                    form.viewCartOpen = false;
                                    form.handleGoToSelfSummaryFromMenu();
                                }
                            "
                        >
                            {{ t('reservationCartGoToSummary') }}
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </SiteLayout>
</template>
