<script setup>
import SiteLayout from '@/Layouts/SiteLayout.vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    brandLogos: { type: Array, default: () => [] },
    scope: { type: String, required: true },
    vacancies: { type: Array, default: () => [] },
});

const selectedJob = ref(null);
const submitting = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

const fullName = ref('');
const email = ref('');
const phone = ref('');
const coverLetter = ref('');
const cvFile = ref(null);

const title = computed(() =>
    props.scope === 'head_office' ? 'HEAD OFFICE VACANCIES' : 'OUTLET VACANCIES',
);

function formatDate(iso) {
    if (!iso) return '-';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function openApply(job) {
    selectedJob.value = job;
    errorMsg.value = '';
    successMsg.value = '';
    fullName.value = '';
    email.value = '';
    phone.value = '';
    coverLetter.value = '';
    cvFile.value = null;
}

function closeModal() {
    selectedJob.value = null;
}

function onCvChange(e) {
    const f = e.target.files?.[0];
    cvFile.value = f || null;
}

async function onSubmit(e) {
    e.preventDefault();
    if (!selectedJob.value || !cvFile.value) return;

    submitting.value = true;
    errorMsg.value = '';
    successMsg.value = '';

    const fd = new FormData();
    fd.append('job_id', String(selectedJob.value.id));
    fd.append('full_name', fullName.value);
    fd.append('email', email.value);
    fd.append('phone', phone.value);
    fd.append('cover_letter', coverLetter.value);
    fd.append('cv_file', cvFile.value);

    try {
        const { data } = await axios.post('/careers/apply', fd, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        successMsg.value = data.message ?? 'Lamaran berhasil dikirim.';
        fullName.value = '';
        email.value = '';
        phone.value = '';
        coverLetter.value = '';
        cvFile.value = null;
    } catch (err) {
        const msg = err.response?.data?.message;
        errorMsg.value = typeof msg === 'string' ? msg : 'Gagal kirim lamaran.';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <SiteLayout
        :title="title"
        shell-class="min-h-screen bg-[#2f2f35] text-white"
        :menus="menus"
        :brand-logos="brandLogos"
    >
        <main class="min-h-[100dvh] bg-[#2f2f35] text-white">
            <section class="border-b border-white/10 bg-[#18181c] px-6 py-12 text-center md:py-16">
                <h1 class="text-3xl font-semibold tracking-[0.08em] md:text-5xl">{{ title }}</h1>
            </section>

            <section class="mx-auto w-full max-w-6xl px-4 py-8 md:px-6 md:py-10">
                <div
                    v-if="vacancies.length === 0"
                    class="rounded-xl border border-white/10 bg-[#3a3a3f] px-6 py-10 text-center text-white/80"
                >
                    Belum ada lowongan untuk kategori ini.
                </div>

                <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <article
                        v-for="job in vacancies"
                        :key="job.id"
                        class="overflow-hidden rounded-xl border border-white/10 bg-[#3a3a3f]"
                    >
                        <img
                            v-if="job.banner_url"
                            :src="job.banner_url"
                            :alt="job.position"
                            class="h-[220px] w-full object-cover"
                        />
                        <div class="space-y-3 p-5">
                            <h2 class="text-xl font-semibold">{{ job.position }}</h2>
                            <p class="text-sm text-white/80">
                                <span class="font-semibold text-white">Lokasi:</span>
                                {{ job.location || '-' }}
                            </p>
                            <p class="text-sm text-white/80">
                                <span class="font-semibold text-white">Tutup:</span>
                                {{ formatDate(job.closing_date) }}
                            </p>
                            <p v-if="job.description" class="whitespace-pre-line text-sm leading-relaxed text-white/85">
                                {{ job.description }}
                            </p>
                            <p v-if="job.requirements" class="whitespace-pre-line text-sm leading-relaxed text-white/85">
                                <span class="font-semibold text-white">Kualifikasi:</span>
                                <br />
                                {{ job.requirements }}
                            </p>
                            <button
                                type="button"
                                class="mt-3 inline-flex rounded-full border border-white/30 px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white transition hover:border-white/60"
                                @click="openApply(job)"
                            >
                                Lamar Sekarang
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <div class="pb-10 text-center">
                <Link
                    href="/careers"
                    class="inline-flex items-center gap-2 rounded-full border border-white/30 px-5 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white/90 transition hover:border-white/60 hover:text-white"
                >
                    <span aria-hidden>←</span>
                    Back to Careers
                </Link>
            </div>

            <div
                v-if="selectedJob"
                class="fixed inset-0 z-[120] flex items-center justify-center bg-black/70 p-4"
                @click="closeModal"
            >
                <div class="w-full max-w-xl rounded-xl border border-white/15 bg-[#2f2f35] p-5" @click.stop>
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <h3 class="text-lg font-semibold">{{ selectedJob.position }}</h3>
                        <button type="button" class="text-white/70 hover:text-white" @click="closeModal">✕</button>
                    </div>
                    <form class="space-y-3" @submit="onSubmit">
                        <input
                            v-model="fullName"
                            name="full_name"
                            required
                            placeholder="Nama lengkap"
                            class="w-full rounded border border-white/20 bg-[#24242a] px-3 py-2 text-sm text-white placeholder:text-white/50"
                        />
                        <input
                            v-model="email"
                            name="email"
                            type="email"
                            required
                            placeholder="Email"
                            class="w-full rounded border border-white/20 bg-[#24242a] px-3 py-2 text-sm text-white placeholder:text-white/50"
                        />
                        <input
                            v-model="phone"
                            name="phone"
                            required
                            placeholder="Nomor HP"
                            class="w-full rounded border border-white/20 bg-[#24242a] px-3 py-2 text-sm text-white placeholder:text-white/50"
                        />
                        <textarea
                            v-model="coverLetter"
                            name="cover_letter"
                            rows="4"
                            placeholder="Pesan singkat / cover letter"
                            class="w-full rounded border border-white/20 bg-[#24242a] px-3 py-2 text-sm text-white placeholder:text-white/50"
                        />
                        <input
                            type="file"
                            required
                            accept=".pdf,.doc,.docx"
                            class="w-full text-sm text-white/90 file:mr-3 file:rounded file:border-0 file:bg-white/15 file:px-3 file:py-2 file:text-white"
                            @change="onCvChange"
                        />
                        <p v-if="errorMsg" class="text-sm text-red-300">{{ errorMsg }}</p>
                        <p v-if="successMsg" class="text-sm text-emerald-300">{{ successMsg }}</p>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="w-full rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#111118] transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {{ submitting ? 'Mengirim...' : 'Kirim Lamaran' }}
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </SiteLayout>
</template>
