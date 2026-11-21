<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import {
    CheckCircle2,
    AlertCircle,
    User,
    Lock,
    GraduationCap,
    Clock,
    ShieldCheck,
    Check,
    KeyRound,
    Loader2,
    MessageSquare,
    HelpCircle,
    Copy,
    ExternalLink,
    X
} from 'lucide-vue-next';

const props = defineProps({
    setting: Object,
    faculties: Array,
    departmentsGrouped: Object,
    activeSession: Object,
});

const form = useForm({
    student_id: '',
    name: '',
    faculty: '',
    study_program: '',
    password: '',
    password_confirmation: '',
});

const availablePrograms = computed(() => {
    if (!form.faculty || !props.departmentsGrouped) return [];
    return props.departmentsGrouped[form.faculty] || [];
});

const nimStatus = ref(null); // 'checking' | 'registered' | 'dpt_found' | 'available'
const nimFeedback = ref('');
const isDptVerified = ref(false);
let debounceTimeout = null;

// State Modal Helpdesk Pengaduan / Perbaikan Data DPT
const isHelpdeskModalOpen = ref(false);
const isCopied = ref(false);

const openHelpdeskModal = () => {
    isHelpdeskModalOpen.value = true;
};

const closeHelpdeskModal = () => {
    isHelpdeskModalOpen.value = false;
};

const reportMessageText = computed(() => {
    const org = props.setting?.organization_name || 'Panitia Pemira';
    return `Halo ${org}, saya ingin mengonfirmasi ketidaksesuaian data DPT Pemira.\n\n` +
        `• NIM: ${form.student_id || '-'}\n` +
        `• Nama Tertera di DPT: ${form.name || '-'}\n` +
        `• Jurusan Tertera di DPT: ${form.faculty || '-'}\n` +
        `• Program Studi Tertera di DPT: ${form.study_program || '-'}\n\n` +
        `Mohon bantuan verifikasi dan pembaruan data yang sebenarnya:\n` +
        `• Nama Sebenarnya: \n` +
        `• Jurusan / Prodi Sebenarnya: \n` +
        `• Lampiran Bukti: (Foto KTM / Screenshot SIAKAD Aktif)`;
});

const helpdeskWhatsAppUrl = computed(() => {
    const raw = props.setting?.helpdesk_contact || '081234567890';
    if (raw.startsWith('http://') || raw.startsWith('https://')) {
        return raw;
    }
    let digits = raw.replace(/\D/g, '');
    if (digits.startsWith('0')) {
        digits = '62' + digits.substring(1);
    }
    if (!digits) {
        return `https://wa.me/?text=${encodeURIComponent(reportMessageText.value)}`;
    }
    return `https://wa.me/${digits}?text=${encodeURIComponent(reportMessageText.value)}`;
});

const copyReportMessage = async () => {
    try {
        await navigator.clipboard.writeText(reportMessageText.value);
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 2000);
    } catch (e) {
        // Fallback
    }
};

const checkNimRealtime = (val) => {
    if (!val || val.trim().length < 3) {
        nimStatus.value = null;
        nimFeedback.value = '';
        isDptVerified.value = false;
        return;
    }

    nimStatus.value = 'checking';
    nimFeedback.value = 'Memeriksa ketersediaan NIM di database...';

    if (debounceTimeout) clearTimeout(debounceTimeout);

    debounceTimeout = setTimeout(async () => {
        try {
            const res = await axios.post(route('voter.check_nim'), {
                student_id: val,
            });

            const data = res.data;
            nimStatus.value = data.status;
            nimFeedback.value = data.message;

            if (data.status === 'dpt_found') {
                isDptVerified.value = true;
                if (data.name) form.name = data.name;
                if (data.faculty) form.faculty = data.faculty;
                if (data.study_program) form.study_program = data.study_program;
            } else {
                isDptVerified.value = false;
            }
        } catch (err) {
            nimStatus.value = null;
            nimFeedback.value = '';
        }
    }, 400);
};

watch(() => form.student_id, (newVal) => {
    checkNimRealtime(newVal);
});

const submit = () => {
    form.post(route('voter.register.submit'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Registrasi & Aktivasi Akun Pemilih - DEMA Polkesmar" />

    <div class="min-h-screen bg-slate-50 text-slate-900 flex flex-col justify-center py-10 px-4 sm:px-6 selection:bg-slate-900 selection:text-white">
        <div class="max-w-md mx-auto w-full space-y-4">
            <!-- Kartu Formulir Registrasi -->
            <main class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-7 shadow-xs space-y-5">
                <!-- Branding Header -->
                <div class="text-center space-y-2 border-b border-slate-100 pb-4">
                    <img
                        :src="setting?.logo_url || $page.props.app_setting?.logo_url || '/images/logo-default.png'"
                        :alt="setting?.organization_name || 'Logo Lembaga'"
                        class="w-12 h-12 object-contain mx-auto"
                    />
                    <div>
                        <h1 class="text-lg font-bold text-slate-950">
                            Registrasi Akun Pemilih
                        </h1>
                        <p class="text-xs text-slate-700 font-medium mt-0.5">
                            Aktivasi akun DPT Anda dengan membuat password sendiri untuk masuk ke bilik suara.
                        </p>
                    </div>

                    <!-- Lencana Sesi Aktif -->
                    <div v-if="activeSession" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-800 text-xs font-semibold border border-slate-300">
                        <Clock class="w-3.5 h-3.5 text-slate-600" />
                        <span>{{ activeSession.name }}</span>
                    </div>
                </div>

                <!-- Formulir Registrasi -->
                <form @submit.prevent="submit" class="space-y-3.5 text-xs">
                    <!-- Field 1: NIM dengan Real-time Check -->
                    <div>
                        <label for="student_id" class="block font-bold text-slate-900 mb-1">
                            Nomor Induk Mahasiswa (NIM) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                id="student_id"
                                v-model="form.student_id"
                                type="text"
                                required
                                placeholder="Contoh: P1337434124001"
                                class="w-full rounded-lg border-slate-300 text-sm font-mono uppercase font-semibold text-slate-950 placeholder:text-slate-400 tracking-wider py-2 px-3 focus:border-slate-900 focus:ring-slate-900"
                                :class="{
                                    'border-emerald-600 ring-1 ring-emerald-600': nimStatus === 'dpt_found' || nimStatus === 'available',
                                    'border-rose-500': nimStatus === 'registered' || form.errors.student_id
                                }"
                            />
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <Check v-if="nimStatus === 'dpt_found' || nimStatus === 'available'" class="w-4 h-4 text-emerald-700 stroke-[2.5]" />
                                <AlertCircle v-else-if="nimStatus === 'registered'" class="w-4 h-4 text-rose-600" />
                                <Loader2 v-else-if="nimStatus === 'checking'" class="w-4 h-4 text-slate-600 animate-spin" />
                            </div>
                        </div>

                        <!-- Feedback Real-time NIM -->
                        <div v-if="nimFeedback" class="mt-1 text-xs leading-normal">
                            <div v-if="nimStatus === 'checking'" class="text-slate-600 font-medium flex items-center gap-1.5">
                                <span>Mengecek data pemilih di DPT...</span>
                            </div>
                            <div v-else-if="nimStatus === 'dpt_found'" class="text-emerald-900 font-semibold flex items-center gap-1.5">
                                <CheckCircle2 class="w-3.5 h-3.5 shrink-0 text-emerald-700" />
                                <span>{{ nimFeedback }}</span>
                            </div>
                            <div v-else-if="nimStatus === 'registered'" class="text-rose-800 font-semibold flex items-center gap-1.5">
                                <AlertCircle class="w-3.5 h-3.5 shrink-0 text-rose-600" />
                                <span>
                                    {{ nimFeedback }}
                                    <Link :href="route('home')" class="underline hover:text-rose-950 ml-1 font-bold">Login di sini</Link>
                                </span>
                            </div>
                            <div v-else-if="nimStatus === 'available'" class="text-slate-800 font-medium">
                                {{ nimFeedback }}
                            </div>
                            <div v-else class="text-slate-600 italic">
                                {{ nimFeedback }}
                            </div>
                        </div>
                        <div v-if="form.errors.student_id" class="text-rose-700 font-semibold text-xs mt-1">{{ form.errors.student_id }}</div>
                    </div>

                    <!-- Field 2: Nama Lengkap -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="name" class="block font-bold text-slate-900">
                                Nama Lengkap <span class="text-rose-500">*</span>
                            </label>
                            <span v-if="isDptVerified" class="text-[11px] uppercase font-bold text-emerald-950 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300">
                                DATA DPT KAMPUS
                            </span>
                        </div>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Nama sesuai KTP / KTM Mahasiswa"
                            class="w-full rounded-lg border-slate-300 text-sm py-2 px-3 focus:border-slate-900 focus:ring-slate-900"
                            :readonly="isDptVerified"
                            :class="{ 'bg-slate-100 text-slate-950 font-bold border-slate-300': isDptVerified, 'text-slate-900': !isDptVerified }"
                        />
                        <div v-if="form.errors.name" class="text-rose-700 font-semibold text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Field 3: Jurusan & Program Studi -->
                    <div v-if="isDptVerified" class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="faculty" class="block font-bold text-slate-900">
                                    Jurusan <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[11px] uppercase font-bold text-emerald-950 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300">
                                    DATA DPT KAMPUS
                                </span>
                            </div>
                            <input
                                id="faculty"
                                v-model="form.faculty"
                                type="text"
                                readonly
                                class="w-full rounded-lg border-slate-300 text-sm py-2 px-3 bg-slate-100 text-slate-950 font-bold"
                            />
                            <div v-if="form.errors.faculty" class="text-rose-700 font-semibold text-xs mt-1">{{ form.errors.faculty }}</div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="study_program" class="block font-bold text-slate-900">
                                    Program Studi <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[11px] uppercase font-bold text-emerald-950 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300">
                                    DATA DPT KAMPUS
                                </span>
                            </div>
                            <input
                                id="study_program"
                                v-model="form.study_program"
                                type="text"
                                readonly
                                class="w-full rounded-lg border-slate-300 text-sm py-2 px-3 bg-slate-100 text-slate-950 font-bold"
                            />
                            <div v-if="form.errors.study_program" class="text-rose-700 font-semibold text-xs mt-1">{{ form.errors.study_program }}</div>
                        </div>

                        <!-- Banner Bantuan Jika Data DPT Tidak Sesuai -->
                        <div class="p-3.5 bg-amber-50 border border-amber-300 rounded-xl text-xs text-amber-950 flex items-start gap-2.5">
                            <AlertCircle class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" />
                            <div class="flex-1 space-y-1">
                                <div class="font-bold text-amber-950">Data nama atau jurusan tidak sesuai?</div>
                                <p class="text-amber-950 text-xs font-medium leading-relaxed">
                                    Data di atas terkunci dari Master DPT. Jika terdapat kesalahan penulisan, hubungi Helpdesk Panitia untuk diverifikasi dan diperbaiki oleh Admin.
                                </p>
                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                    <a
                                        :href="helpdeskWhatsAppUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-emerald-700 hover:bg-emerald-800 px-3 py-1.5 rounded-lg transition-colors shadow-2xs cursor-pointer"
                                    >
                                        <MessageSquare class="w-3.5 h-3.5" />
                                        <span>Chat Langsung ke WA</span>
                                        <ExternalLink class="w-3 h-3" />
                                    </a>
                                    <button
                                        type="button"
                                        @click="openHelpdeskModal"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-950 bg-white hover:bg-amber-100 border border-amber-400 px-3 py-1.5 rounded-lg transition-colors shadow-xs cursor-pointer"
                                    >
                                        <span>Lihat Prosedur Lengkap</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="space-y-3">
                        <div>
                            <label for="faculty" class="block font-semibold text-slate-700 mb-1">
                                Jurusan <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="faculty"
                                v-model="form.faculty"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm py-2 px-3 focus:border-slate-900 focus:ring-slate-900"
                            >
                                <option value="" disabled>Pilih Jurusan Resmi</option>
                                <option v-for="fac in faculties" :key="fac" :value="fac">
                                    {{ fac }}
                                </option>
                                <option v-if="form.faculty && !faculties.includes(form.faculty)" :value="form.faculty">
                                    {{ form.faculty }}
                                </option>
                            </select>
                            <div v-if="form.errors.faculty" class="text-rose-600 text-xs mt-1">{{ form.errors.faculty }}</div>
                        </div>

                        <div>
                            <label for="study_program" class="block font-semibold text-slate-700 mb-1">
                                Program Studi <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="study_program"
                                v-model="form.study_program"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm py-2 px-3 focus:border-slate-900 focus:ring-slate-900"
                                :disabled="!form.faculty"
                                :class="{ 'bg-slate-50 text-slate-700': !form.faculty }"
                            >
                                <option value="" disabled>
                                    {{ form.faculty ? 'Pilih Program Studi' : 'Pilih Jurusan Terlebih Dahulu' }}
                                </option>
                                <option v-for="prog in availablePrograms" :key="prog" :value="prog">
                                    {{ prog }}
                                </option>
                                <option v-if="form.study_program && !availablePrograms.includes(form.study_program)" :value="form.study_program">
                                    {{ form.study_program }}
                                </option>
                            </select>
                            <div v-if="form.errors.study_program" class="text-rose-600 text-xs mt-1">{{ form.errors.study_program }}</div>
                        </div>
                    </div>

                    <!-- Field 4: Password & Konfirmasi Password -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-0.5">
                        <div>
                            <label for="password" class="block font-bold text-slate-900 mb-1">
                                Password Akun <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                minlength="6"
                                placeholder="Minimal 6 karakter"
                                class="w-full rounded-lg border-slate-300 text-sm py-2 px-3 text-slate-950 placeholder:text-slate-400 font-medium focus:border-slate-900 focus:ring-slate-900"
                            />
                            <div v-if="form.errors.password" class="text-rose-700 font-semibold text-xs mt-1">{{ form.errors.password }}</div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block font-bold text-slate-900 mb-1">
                                Konfirmasi Password <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                placeholder="Ulangi password"
                                class="w-full rounded-lg border-slate-300 text-sm py-2 px-3 text-slate-950 placeholder:text-slate-400 font-medium focus:border-slate-900 focus:ring-slate-900"
                            />
                        </div>
                    </div>

                    <!-- Tombol Submit -->
                    <div class="pt-1.5">
                        <button
                            type="submit"
                            :disabled="form.processing || nimStatus === 'registered'"
                            class="w-full bg-slate-950 hover:bg-slate-800 disabled:bg-slate-400 text-white font-bold text-xs sm:text-sm py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-xs focus:ring-2 focus:ring-slate-950 focus:outline-none cursor-pointer"
                        >
                            <User class="w-4 h-4 stroke-[2.5]" />
                            <span>{{ form.processing ? 'Mendaftarkan Akun...' : 'Daftar & Masuk ke Bilik Suara' }}</span>
                        </button>
                    </div>
                </form>
            </main>

            <!-- Bantuan / Hubungi Helpdesk & Sudah Punya Akun -->
            <div class="space-y-2">
                <div class="p-3.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 font-medium flex items-center justify-between gap-2 shadow-xs">
                    <div class="flex items-center gap-2">
                        <HelpCircle class="w-4 h-4 text-slate-600 shrink-0" />
                        <span>NIM belum terdaftar atau data salah?</span>
                    </div>
                    <button
                        type="button"
                        @click="openHelpdeskModal"
                        class="font-bold text-emerald-800 hover:text-emerald-950 hover:underline cursor-pointer shrink-0"
                    >
                        Lapor Panitia &rarr;
                    </button>
                </div>

                <div class="p-3.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 font-medium flex items-center justify-between shadow-xs">
                    <span>Sudah memiliki akun terdaftar?</span>
                    <Link
                        :href="route('home')"
                        class="font-bold text-slate-950 hover:underline"
                    >
                        Masuk di Beranda &rarr;
                    </Link>
                </div>
            </div>
        </div>

        <!-- Modal Bantuan & Layanan Perbaikan DPT -->
        <div
            v-if="isHelpdeskModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in duration-150"
            @click.self="closeHelpdeskModal"
        >
            <div class="bg-white rounded-2xl max-w-md w-full p-5 sm:p-6 border border-slate-300 shadow-2xl space-y-4 animate-in zoom-in-95 duration-150">
                <div class="flex items-start justify-between border-b border-slate-200 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-900 border border-emerald-300 flex items-center justify-center">
                            <MessageSquare class="w-5 h-5 stroke-[2.5]" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-950">Pusat Bantuan DPT Pemira</h3>
                            <p class="text-xs text-slate-700 font-medium">Layanan perbaikan data & verifikasi hak suara</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeHelpdeskModal"
                        class="text-slate-600 hover:text-slate-950 p-1.5 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                        title="Tutup dialog"
                    >
                        <X class="w-4 h-4 stroke-[2.5]" />
                    </button>
                </div>

                <!-- Ringkasan Data Saat Ini -->
                <div class="bg-slate-100 rounded-xl p-3.5 border border-slate-300 text-xs space-y-1.5 font-mono">
                    <div class="text-xs font-sans font-bold text-slate-800 mb-1">Data Anda Saat Ini di Formulir:</div>
                    <div class="flex justify-between"><span class="text-slate-700 font-sans font-medium">NIM:</span> <span class="font-bold text-slate-950">{{ form.student_id || '(Belum diisi)' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-700 font-sans font-medium">Nama:</span> <span class="font-bold text-slate-950">{{ form.name || '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-700 font-sans font-medium">Jurusan:</span> <span class="font-bold text-slate-950">{{ form.faculty || '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-700 font-sans font-medium">Prodi:</span> <span class="font-bold text-slate-950">{{ form.study_program || '-' }}</span></div>
                </div>

                <!-- Panduan Prosedur -->
                <div class="text-xs text-slate-800 space-y-2">
                    <div class="font-bold text-slate-950">Prosedur Perbaikan Data:</div>
                    <ol class="list-decimal list-inside space-y-1 text-xs text-slate-800 font-medium pl-1 leading-relaxed">
                        <li>Hubungi Panitia KPUM via WhatsApp atau kanal pengaduan resmi.</li>
                        <li>Lampirkan <strong class="font-bold text-slate-950">foto KTM</strong> atau tangkapan layar <strong class="font-bold text-slate-950">SIAKAD</strong> aktif sebagai bukti sah.</li>
                        <li>Admin akan memverifikasi dan memperbarui data Anda langsung di master DPT.</li>
                        <li>Setelah diperbaiki, silakan kembali ke halaman ini dan muat ulang.</li>
                    </ol>
                </div>

                <!-- Tombol Aksi -->
                <div class="space-y-2 pt-2 border-t border-slate-200">
                    <!-- Tombol Utama Langsung Chat WA -->
                    <a
                        :href="helpdeskWhatsAppUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm py-2.5 sm:py-3 px-4 rounded-xl transition-all flex items-center justify-center gap-2 shadow-xs cursor-pointer"
                    >
                        <MessageSquare class="w-4 h-4 stroke-[2.5]" />
                        <span>Chat Langsung ke WhatsApp Panitia</span>
                        <ExternalLink class="w-3.5 h-3.5 opacity-90" />
                    </a>

                    <div v-if="props.setting?.helpdesk_contact" class="text-[11px] text-slate-700 text-center font-medium">
                        Nomor WhatsApp Helpdesk: <strong class="text-slate-950 font-bold">{{ props.setting.helpdesk_contact }}</strong>
                    </div>

                    <button
                        type="button"
                        @click="copyReportMessage"
                        class="w-full border border-slate-300 hover:bg-slate-100 text-slate-900 font-bold text-xs py-2 px-3 rounded-xl transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                        <Check v-if="isCopied" class="w-3.5 h-3.5 text-emerald-700 stroke-[2.5]" />
                        <Copy v-else class="w-3.5 h-3.5 text-slate-600" />
                        <span>{{ isCopied ? 'Format Pesan Berhasil Disalin!' : 'Salin Format Pesan Pengaduan' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
