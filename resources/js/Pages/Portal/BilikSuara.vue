<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    AlertTriangle,
    ShieldCheck,
    LogOut,
    GraduationCap,
    Vote,
    Lock,
    Clock,
    RefreshCw
} from 'lucide-vue-next';

const props = defineProps({
    voter: Object,
    candidates: Array,
    setting: Object,
    currentSession: Object,
});

const selectedCandidate = ref(null);
const showConfirmModal = ref(false);
const expandedCandidateId = ref(null);

const reloadPage = () => {
    router.reload();
};

const form = useForm({
    candidate_id: null,
});

const selectCandidate = (candidate) => {
    selectedCandidate.value = candidate;
    form.candidate_id = candidate.id;
    showConfirmModal.value = true;
};

const toggleExpand = (id) => {
    expandedCandidateId.value = expandedCandidateId.value === id ? null : id;
};

const submitVote = () => {
    form.post(route('booth.submit'), {
        preserveScroll: true,
        onError: () => {
            showConfirmModal.value = false;
        },
    });
};
</script>

<template>
    <Head title="Bilik Suara Digital - Surat Suara Resmi" />

    <div class="min-h-screen bg-slate-100/80 text-slate-900 flex flex-col justify-between selection:bg-slate-900 selection:text-white">
        <!-- Top Navigation Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs pt-[env(safe-area-inset-top,0px)]">
            <div class="max-w-6xl mx-auto px-3 sm:px-6 flex items-center justify-between h-14 sm:h-16 gap-2">
                <!-- Logo + Title -->
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <img
                        :src="$page.props.app_setting?.logo_url || '/images/logo-default.png'"
                        alt="Logo Lembaga"
                        class="w-8 h-8 sm:w-9 sm:h-9 object-contain shrink-0"
                    />
                    <div class="leading-tight min-w-0">
                        <div class="font-bold text-[13px] sm:text-sm text-slate-950 truncate">
                            Bilik Suara
                        </div>
                        <div class="text-xs text-slate-800 font-semibold hidden sm:block truncate">
                            {{ setting.title || 'Pemira DEMA Polkesmar 2026/2027' }}
                        </div>
                    </div>
                </div>

                <!-- Right side: voter info + logout -->
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <!-- Voter pill -->
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 sm:px-3">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0">
                            {{ voter.name ? voter.name.charAt(0) : 'P' }}
                        </div>
                        <div class="text-left leading-tight">
                            <div class="font-bold text-xs sm:text-sm text-slate-950 max-w-[110px] sm:max-w-[190px] truncate">
                                {{ voter.name }}
                            </div>
                            <div class="text-xs text-slate-700 font-bold font-mono">
                                {{ voter.student_id }}
                            </div>
                        </div>
                    </div>

                    <!-- Logout -->
                    <a
                        :href="route('home')"
                        class="border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-slate-800 hover:text-slate-950 rounded-lg p-2 sm:px-3.5 sm:py-2 text-xs sm:text-sm font-semibold transition-colors flex items-center gap-1.5 focus:ring-2 focus:ring-slate-900 focus:outline-none"
                        title="Batal dan Keluar"
                    >
                        <LogOut class="w-4 h-4" />
                        <span class="hidden sm:inline">Keluar</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Ballot Container -->
        <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8 flex-1 w-full space-y-6">
            <!-- Mode 1: Bilik Suara Aktif (Ada Pasangan Calon) -->
            <template v-if="candidates && candidates.length > 0">
                <!-- Header Surat Suara Formal -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-xs">
                    <div class="border-b border-slate-100 pb-4">
                        <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                            <Vote class="w-4 h-4 text-slate-900" />
                            <span>Surat Suara Elektronik {{ setting.organization_name || 'Pemira' }}</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-950 tracking-tight">
                            Pemilihan Pasangan Calon Presiden & Wakil Presiden Mahasiswa
                        </h1>
                        <p class="text-sm text-slate-700 font-medium mt-1.5">
                            Gunakan hak pilih Anda dengan bijak. Pelajari profil, visi, dan misi kandidat sebelum menentukan pilihan.
                        </p>
                    </div>

                    <!-- Petunjuk Cara Memilih Singkat & Info Sesi -->
                    <div class="pt-3 flex flex-col sm:flex-row sm:items-center justify-between text-sm text-slate-800 gap-2 font-medium">
                        <span class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                            Klik tombol <strong>"Coblos Paslon"</strong> di bawah kartu kandidat yang Anda kehendaki.
                        </span>
                        <span v-if="currentSession" class="inline-flex items-center gap-1.5 text-xs sm:text-sm text-slate-800 font-semibold self-start sm:self-auto">
                            <Clock class="w-4 h-4 text-slate-700 shrink-0" />
                            <span>{{ currentSession.name }} &bull; {{ currentSession.time_range }}</span>
                        </span>
                    </div>
                </div>

                <!-- Daftar Kartu Surat Suara Pasangan Calon -->
                <div class="grid md:grid-cols-2 gap-6 sm:gap-8 items-start">
                <article
                    v-for="candidate in candidates"
                    :key="candidate.id"
                    class="bg-white border-2 rounded-2xl overflow-hidden transition-all duration-200 flex flex-col justify-between shadow-xs hover:shadow-md"
                    :class="selectedCandidate?.id === candidate.id ? 'border-slate-950 ring-2 ring-slate-950' : 'border-slate-200 hover:border-slate-300'"
                >
                    <!-- Bagian Atas Surat Suara: Nomor Urut Formal -->
                    <div class="bg-slate-900 text-white px-5 py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-md bg-white text-slate-950 font-black font-mono text-xl flex items-center justify-center shadow-xs">
                                {{ String(candidate.candidate_number).padStart(2, '0') }}
                            </div>
                            <div class="leading-tight">
                                <div class="text-xs uppercase font-bold tracking-widest text-slate-300">
                                    Pasangan Calon
                                </div>
                                <div class="text-sm sm:text-base font-bold text-white">
                                    Nomor Urut {{ candidate.candidate_number }}
                                </div>
                            </div>
                        </div>

                        <span class="text-xs font-medium text-slate-300 px-2.5 py-0.5 rounded bg-slate-800 border border-slate-700">
                            Surat Suara Sah
                        </span>
                    </div>

                    <!-- Frame Foto Calon (Portrait 3:4) -->
                    <div class="p-5 pb-3">
                        <div class="relative w-full aspect-[3/4] rounded-xl overflow-hidden border border-slate-200 bg-slate-100">
                            <!-- Foto Asli Portrait (Isi Penuh Frame, Tanpa Sisa Ruang) -->
                            <img
                                v-if="candidate.photo_path"
                                :src="candidate.photo_path"
                                :alt="'Foto Calon ' + candidate.candidate_number"
                                class="w-full h-full object-cover object-top"
                            />

                            <!-- Fallback Foto Resmi (Silhouette Clean Illustration) -->
                            <div v-else class="w-full h-full flex flex-col justify-between p-4 relative select-none">
                                <!-- Watermark Nomor Urut di Background -->
                                <div class="absolute inset-0 flex items-center justify-center opacity-10 pointer-events-none">
                                    <span class="text-9xl font-black text-slate-900 font-mono">
                                        {{ String(candidate.candidate_number).padStart(2, '0') }}
                                    </span>
                                </div>

                                <!-- Silhouette Calon (Solo vs Duo) -->
                                <div v-if="candidate.candidate_type === 'solo' || !candidate.deputy_name" class="flex items-end justify-center h-full pb-2 relative z-10">
                                    <!-- Calon Tunggal Silhouette -->
                                    <div class="flex flex-col items-center">
                                        <div class="w-24 h-24 rounded-full bg-slate-300 border-2 border-white shadow-xs flex items-center justify-center text-slate-600 mb-1">
                                            <svg class="w-14 h-14 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                            </svg>
                                        </div>
                                        <span class="text-xs font-bold px-2.5 py-0.5 rounded bg-slate-900 text-white uppercase tracking-wider">
                                            Calon Tunggal
                                        </span>
                                    </div>
                                </div>

                                <!-- Pasangan Silhouette Duo -->
                                <div v-else class="flex items-end justify-center gap-6 sm:gap-10 h-full pb-2 relative z-10">
                                    <!-- Calon Presma Silhouette -->
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-slate-300 border-2 border-white shadow-xs flex items-center justify-center text-slate-600 mb-1">
                                            <svg class="w-12 h-12 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                            </svg>
                                        </div>
                                        <span class="text-xs font-bold px-2.5 py-0.5 rounded bg-slate-900 text-white uppercase tracking-wider">
                                            Capresma
                                        </span>
                                    </div>

                                    <!-- Calon Wapresma Silhouette -->
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-slate-300 border-2 border-white shadow-xs flex items-center justify-center text-slate-600 mb-1">
                                            <svg class="w-12 h-12 text-slate-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                            </svg>
                                        </div>
                                        <span class="text-xs font-bold px-2.5 py-0.5 rounded bg-slate-800 text-white uppercase tracking-wider">
                                            Cawapresma
                                        </span>
                                    </div>
                                </div>

                                <!-- Badge Pasangan Calon Resmi Bawah -->
                                <div class="relative z-10 text-center">
                                    <span class="inline-block text-xs font-bold text-slate-800 bg-white/95 backdrop-blur-xs px-3.5 py-1 rounded-full border border-slate-300 shadow-xs">
                                        Foto Resmi Calon {{ String(candidate.candidate_number).padStart(2, '0') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nama dan Detail Paslon -->
                    <div class="px-5 py-3 space-y-3.5 flex-1">
                        <!-- Calon Ketua & Wakil (Solo vs Duo) -->
                        <div class="pb-2.5 border-b border-slate-100">
                            <!-- Solo Calon Tunggal -->
                            <div v-if="candidate.candidate_type === 'solo' || !candidate.deputy_name" class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                                <span class="text-xs uppercase font-bold text-slate-700 tracking-wider block">
                                    Calon Ketua / Calon Tunggal
                                </span>
                                <h2 class="text-sm sm:text-base font-extrabold text-slate-950 mt-0.5 leading-snug">
                                    {{ candidate.chairman_name }}
                                </h2>
                            </div>

                            <!-- Duo Paslon -->
                            <div v-else class="grid sm:grid-cols-2 gap-2.5">
                                <!-- Presma -->
                                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                                    <span class="text-xs uppercase font-bold text-slate-700 tracking-wider block">
                                        Calon Presiden Mahasiswa
                                    </span>
                                    <h2 class="text-sm sm:text-base font-extrabold text-slate-950 mt-0.5 leading-snug">
                                        {{ candidate.chairman_name }}
                                    </h2>
                                </div>

                                <!-- Wapresma -->
                                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                                    <span class="text-xs uppercase font-bold text-slate-700 tracking-wider block">
                                        Calon Wakil Presiden Mahasiswa
                                    </span>
                                    <h2 class="text-sm sm:text-base font-extrabold text-slate-950 mt-0.5 leading-snug">
                                        {{ candidate.deputy_name }}
                                    </h2>
                                </div>
                            </div>
                        </div>

                        <!-- Info Asal Jurusan Polkesmar -->
                        <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-800 font-semibold py-0.5">
                            <GraduationCap class="w-4 h-4 text-slate-700 shrink-0" />
                            <span>{{ candidate.faculty_info }}</span>
                        </div>

                        <!-- Visi Kandidat -->
                        <div class="space-y-1 pt-0.5">
                            <div class="text-xs uppercase font-bold text-slate-800 tracking-wider">
                                Visi Utama:
                            </div>
                            <blockquote class="text-xs sm:text-sm text-slate-800 font-medium leading-relaxed italic border-l-2 border-slate-900 pl-3 py-0.5 bg-slate-50 rounded-r">
                                "{{ candidate.vision }}"
                            </blockquote>
                        </div>

                        <!-- Misi Accordion -->
                        <div class="pt-0.5">
                            <button
                                type="button"
                                @click="toggleExpand(candidate.id)"
                                class="w-full text-xs sm:text-sm text-slate-900 hover:text-slate-950 font-bold flex items-center justify-between p-2.5 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors border border-slate-200 focus:ring-2 focus:ring-slate-900 focus:outline-none"
                            >
                                <span class="flex items-center gap-1.5">
                                    <span>Program & Misi Strategis ({{ candidate.missions?.length || 0 }})</span>
                                </span>
                                <ChevronUp v-if="expandedCandidateId === candidate.id" class="w-4 h-4 text-slate-700" />
                                <ChevronDown v-else class="w-4 h-4 text-slate-700" />
                            </button>

                            <div
                                v-if="expandedCandidateId === candidate.id"
                                class="mt-2 text-xs sm:text-sm text-slate-800 bg-white p-3 rounded-lg border border-slate-200 space-y-2 shadow-xs"
                            >
                                <ol class="list-decimal list-outside pl-4 space-y-1.5 text-xs sm:text-sm leading-relaxed font-medium">
                                    <li v-for="(misi, idx) in candidate.missions" :key="idx" class="pl-1">
                                        {{ misi }}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Coblos Paslon (Authentic Action Bar) -->
                    <div class="p-4 sm:p-5 pt-2.5 bg-slate-50 border-t border-slate-200">
                        <button
                            type="button"
                            @click="selectCandidate(candidate)"
                            class="w-full py-2.5 sm:py-3 px-4 rounded-xl text-white font-bold text-sm sm:text-base transition-all duration-150 flex items-center justify-center gap-2 shadow-sm active:scale-[0.99] focus:ring-2 focus:ring-offset-2 focus:outline-none"
                            :class="selectedCandidate?.id === candidate.id
                                ? 'bg-slate-950 hover:bg-slate-900 focus:ring-slate-950'
                                : 'bg-slate-900 hover:bg-slate-800 focus:ring-slate-900'"
                        >
                            <CheckCircle2 class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-400" />
                            <span>Coblos Paslon {{ String(candidate.candidate_number).padStart(2, '0') }}</span>
                        </button>
                    </div>
                </article>
            </div>
            </template>

            <!-- Mode 2: Surat Suara Belum Dibuka / Belum Ada Paslon -->
            <div
                v-else
                class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden"
            >
                <div class="p-6 sm:p-8 border-b border-slate-100">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div class="space-y-1.5 max-w-2xl">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700 block">
                                {{ setting.title || 'Pemilihan Raya Mahasiswa' }}
                            </span>
                            <h1 class="text-xl sm:text-2xl font-black text-slate-950 tracking-tight">
                                Surat Suara Belum Tersedia
                            </h1>
                            <p class="text-sm text-slate-700 font-medium leading-relaxed">
                                Daftar pasangan calon belum diterbitkan oleh panitia pemilihan. Anda telah berhasil masuk dan data hak suara Anda telah diverifikasi oleh sistem.
                            </p>
                        </div>

                        <div v-if="currentSession" class="bg-slate-50 border border-slate-200 rounded-lg p-3 sm:text-right shrink-0">
                            <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Sesi Pemilihan</div>
                            <div class="text-sm font-bold text-slate-950">{{ currentSession.name }}</div>
                            <div class="text-xs font-mono font-medium text-slate-700">{{ currentSession.time_range }}</div>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8 bg-slate-50/50 space-y-6">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="bg-white border border-slate-200 rounded-lg p-4 space-y-2">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-700">
                                Identitas Pemilih
                            </div>
                            <div class="space-y-1">
                                <div class="text-sm font-bold text-slate-950">{{ voter.name }}</div>
                                <div class="text-xs font-mono text-slate-700 font-medium">NIM: {{ voter.student_id }}</div>
                            </div>
                            <div class="pt-2 border-t border-slate-100 flex items-center gap-1.5 text-xs text-slate-800 font-semibold">
                                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                <span>Terdaftar di Daftar Pemilih Tetap (DPT)</span>
                            </div>
                        </div>

                        <div class="bg-white border border-slate-200 rounded-lg p-4 space-y-2">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-700">
                                Status Pemungutan Suara
                            </div>
                            <p class="text-xs text-slate-700 leading-relaxed font-medium">
                                Surat suara elektronik akan tampil otomatis begitu panitia pemilihan menetapkan dan mengaktifkan data pasangan calon.
                            </p>
                            <div class="pt-2 border-t border-slate-100 text-xs text-slate-600">
                                Tidak ada tindakan yang diperlukan saat ini.
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button
                            type="button"
                            @click="reloadPage"
                            class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs sm:text-sm rounded-lg transition-colors flex items-center gap-2 shadow-xs focus:ring-2 focus:ring-slate-900 focus:outline-none"
                        >
                            <RefreshCw class="w-4 h-4" />
                            <span>Periksa Pembaruan</span>
                        </button>
                        <a
                            :href="route('home')"
                            class="px-4 py-2.5 border border-slate-300 hover:border-slate-400 hover:bg-white text-slate-800 font-bold text-xs sm:text-sm rounded-lg transition-colors focus:ring-2 focus:ring-slate-900 focus:outline-none flex items-center gap-2"
                        >
                            <LogOut class="w-4 h-4" />
                            <span>Kembali ke Beranda</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modal Konfirmasi Coblos Final -->
        <div
            v-if="showConfirmModal && selectedCandidate"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
            @keydown.esc="showConfirmModal = false"
        >
            <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 sm:p-7 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <!-- Header Modal -->
                <div class="flex items-start gap-3 border-b border-slate-100 pb-3.5">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <Vote class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-950">
                            Konfirmasi Pilihan Surat Suara
                        </h2>
                        <p class="text-xs text-slate-700 font-medium mt-0.5">
                            Periksa kembali pasangan calon yang Anda pilih sebelum mengirimkan suara.
                        </p>
                    </div>
                </div>

                <!-- Kartu Ringkasan Paslon Yang Dipilih -->
                <div class="border-2 border-slate-900 rounded-xl p-4 bg-slate-50 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Pilihan Calon Anda:
                        </span>
                        <span class="font-mono font-black text-xs sm:text-sm text-white bg-slate-900 px-2.5 py-0.5 rounded">
                            NOMOR URUT {{ String(selectedCandidate.candidate_number).padStart(2, '0') }}
                        </span>
                    </div>

                    <div class="space-y-1 pt-0.5">
                        <div class="text-xs text-slate-700 font-semibold">
                            {{ selectedCandidate.candidate_type === 'solo' || !selectedCandidate.deputy_name ? 'Calon Ketua / Calon Tunggal:' : 'Calon Ketua & Wakil Ketua:' }}
                        </div>
                        <div class="text-base sm:text-lg font-extrabold text-slate-950">
                            {{ selectedCandidate.chairman_name }}
                            <span v-if="selectedCandidate.candidate_type !== 'solo' && selectedCandidate.deputy_name">
                                & {{ selectedCandidate.deputy_name }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-800 font-semibold pt-0.5">
                            {{ selectedCandidate.faculty_info }}
                        </div>
                    </div>
                </div>

                <!-- Pernyataan Integritas & Peringatan Final -->
                <div class="text-xs text-amber-950 bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-start gap-2.5 leading-relaxed">
                    <AlertTriangle class="w-4 h-4 text-amber-700 mt-0.5 shrink-0" />
                    <div>
                        <strong>Penting:</strong> Setelah Anda menekan tombol konfirmasi, hak suara Anda tercatat secara resmi dan tidak dapat diubah kembali dengan alasan apa pun.
                    </div>
                </div>

                <!-- Tombol Aksi Bawah -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 pt-1">
                    <button
                        type="button"
                        @click="showConfirmModal = false"
                        :disabled="form.processing"
                        class="w-full sm:w-auto px-4 py-2.5 border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-slate-800 font-bold text-xs sm:text-sm rounded-lg transition-colors focus:ring-2 focus:ring-slate-900 focus:outline-none"
                    >
                        Periksa Ulang
                    </button>

                    <button
                        type="button"
                        @click="submitVote"
                        :disabled="form.processing"
                        class="w-full sm:w-auto px-5 py-2.5 bg-slate-900 hover:bg-slate-800 disabled:bg-slate-400 text-white font-bold text-xs sm:text-sm rounded-lg transition-colors flex items-center justify-center gap-2 shadow-xs focus:ring-2 focus:ring-slate-900 focus:outline-none"
                    >
                        <CheckCircle2 v-if="!form.processing" class="w-4 h-4 text-emerald-400" />
                        <span v-if="form.processing">Merekam Suara ke Kotak Digital...</span>
                        <span v-else>Ya, Saya Yakin & Kirim Suara</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer Resmi KPU DEMA -->
        <footer class="border-t border-slate-200 bg-white py-4 mt-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-700 font-medium">
                <div>
                    Komisi Pemilihan Umum Mahasiswa (KPUM) {{ setting.organization_name || 'DEMA Poltekkes Kemenkes Semarang' }}
                </div>
                <div class="flex items-center gap-3 text-[11px] text-slate-600 font-semibold">
                    <span>Asas LUBER JURDIL</span>
                    <span>&bull;</span>
                    <span>Hak Suara Terlindungi Sistem</span>
                </div>
            </div>
        </footer>
    </div>
</template>
