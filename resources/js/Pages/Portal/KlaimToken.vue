<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    KeyRound,
    Calendar,
    ArrowLeft,
    CheckCircle2,
    Copy,
    ArrowRight,
    AlertCircle,
    ShieldCheck,
    UserCheck,
    Check
} from 'lucide-vue-next';

const props = defineProps({
    setting: Object,
    claimed: Object,
});

const isCopied = ref(false);

const form = useForm({
    student_id: '',
    birth_date: '',
});

const submit = () => {
    form.post(route('token.claim.submit'), {
        preserveScroll: true,
    });
};

const copyToken = () => {
    if (props.claimed?.token) {
        navigator.clipboard.writeText(props.claimed.token);
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 2000);
    }
};
</script>

<template>
    <Head title="Klaim Token Pemilihan Mandiri" />

    <div class="min-h-screen bg-slate-50 text-slate-900 flex flex-col justify-center py-10 px-4 sm:px-6 selection:bg-slate-900 selection:text-white">
        <div class="max-w-md mx-auto w-full space-y-4">
            <!-- Header Kecil -->
            <div class="flex items-center justify-between">
                <Link
                    :href="route('home')"
                    class="text-xs text-slate-800 hover:text-slate-950 flex items-center gap-1.5 transition-colors font-semibold"
                >
                    <ArrowLeft class="w-3.5 h-3.5 text-slate-800" />
                    <span>Kembali ke Beranda</span>
                </Link>

                <span class="text-[11px] text-slate-700 font-bold">Layanan Mandiri DPT</span>
            </div>

            <main class="space-y-4">
            <!-- Kartu Form Klaim (Jika Belum Diklaim) -->
            <div v-if="!claimed" class="bg-white border border-slate-200 rounded-xl p-6 sm:p-7 shadow-xs space-y-6">
                <!-- Branding Header -->
                <div class="text-center space-y-2">
                    <img
                        :src="$page.props.app_setting?.logo_url || '/images/logo-default.png'"
                        alt="Logo Lembaga"
                        class="w-12 h-12 object-contain mx-auto"
                    />
                    <div>
                        <h1 class="text-lg font-bold text-slate-950">
                            Klaim Token Pemilihan Mandiri
                        </h1>
                        <p class="text-xs text-slate-700 font-medium mt-1">
                            Verifikasi identitas Anda untuk mendapatkan token akses bilik suara tanpa disebar di grup publik.
                        </p>
                    </div>
                </div>

                <!-- Error Alert -->
                <div
                    v-if="form.errors.claim_error || form.errors.student_id || form.errors.birth_date"
                    class="p-3.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2.5"
                >
                    <AlertCircle class="w-4 h-4 text-rose-600 mt-0.5 shrink-0" />
                    <div>{{ form.errors.claim_error || form.errors.student_id || form.errors.birth_date }}</div>
                </div>

                <!-- Formulir Verifikasi NIM & Tanggal Lahir -->
                <form @submit.prevent="submit" class="space-y-4 text-xs">
                    <div>
                        <label for="student_id" class="block font-bold text-slate-900 mb-1">
                            Nomor Induk Mahasiswa (NIM)
                        </label>
                        <input
                            id="student_id"
                            type="text"
                            v-model="form.student_id"
                            required
                            autofocus
                            placeholder="Contoh: P1337434124001"
                            class="w-full font-mono text-xs px-3 py-2 border border-slate-300 rounded-lg text-slate-950 font-bold focus:border-slate-900 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label for="birth_date" class="block font-bold text-slate-900 mb-1">
                            Tanggal Lahir (Sebagai Kode Rahasia)
                        </label>
                        <div class="relative">
                            <input
                                id="birth_date"
                                type="date"
                                v-model="form.birth_date"
                                required
                                class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg text-slate-950 font-semibold focus:border-slate-900 focus:outline-none"
                            />
                        </div>
                        <p class="text-[11px] text-slate-600 font-medium mt-1">
                            Hanya Anda yang mengetahui tanggal lahir Anda, sehingga token tidak bisa diklaim oleh orang lain.
                        </p>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-slate-900 hover:bg-slate-800 disabled:bg-slate-300 text-white font-bold py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 transition-colors shadow-xs"
                        >
                            <KeyRound class="w-3.5 h-3.5" />
                            <span>{{ form.processing ? 'Memverifikasi Data...' : 'Verifikasi & Tampilkan Token' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Kartu Hasil Token Berhasil Didapatkan -->
            <div v-else class="bg-white border border-slate-200 rounded-xl p-6 sm:p-7 shadow-xs space-y-5 animate-in fade-in zoom-in-95">
                <div class="text-center space-y-1.5">
                    <div class="w-12 h-12 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center mx-auto">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                    <h2 class="text-base font-bold text-slate-950">
                        Token Berhasil Diverifikasi
                    </h2>
                    <p class="text-xs text-slate-700 font-medium">
                        Identitas Anda cocok dengan Daftar Pemilih Tetap (DPT).
                    </p>
                </div>

                <!-- Ringkasan Mahasiswa -->
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 space-y-2 text-xs">
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5">
                        <span class="text-slate-700 font-semibold">Nama Mahasiswa:</span>
                        <span class="font-bold text-slate-950">{{ claimed.name }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5">
                        <span class="text-slate-700 font-semibold">NIM:</span>
                        <span class="font-mono font-bold text-slate-950">{{ claimed.student_id }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-700 font-semibold">Fakultas / Prodi:</span>
                        <span class="text-slate-900 font-bold">{{ claimed.faculty }}</span>
                    </div>
                </div>

                <!-- Kotak Token Menonjol -->
                <div class="border-2 border-slate-900 rounded-xl p-4 text-center bg-slate-900 text-white space-y-2 shadow-xs">
                    <div class="text-[11px] font-bold tracking-wider uppercase text-slate-200">
                        Kode Token Pemilihan Anda
                    </div>
                    <div class="text-2xl sm:text-3xl font-mono font-bold tracking-widest text-emerald-400">
                        {{ claimed.token }}
                    </div>
                    <div class="pt-1">
                        <button
                            type="button"
                            @click="copyToken"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold transition-colors"
                        >
                            <Check v-if="isCopied" class="w-3.5 h-3.5 text-emerald-400" />
                            <Copy v-else class="w-3.5 h-3.5" />
                            <span>{{ isCopied ? 'Tersalin ke Clipboard' : 'Salin Token' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Tombol Langsung Masuk Bilik Suara -->
                <div class="space-y-2 pt-2">
                    <Link
                        :href="route('booth.show')"
                        class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 transition-colors shadow-xs"
                    >
                        <span>Langsung Masuk ke Bilik Suara</span>
                        <ArrowRight class="w-4 h-4" />
                    </Link>

                    <p class="text-[11px] text-slate-700 font-medium text-center leading-relaxed">
                        Sesi verifikasi Anda sudah aktif. Anda dapat langsung memilih atau menyimpan token ini untuk digunakan nanti sebelum pemilihan ditutup.
                    </p>
                </div>
            </div>

            <!-- Petunjuk Bantuan -->
            <div class="p-3.5 rounded-lg border border-slate-200 bg-white text-xs text-slate-700 font-medium flex items-start gap-2.5">
                <ShieldCheck class="w-4 h-4 text-slate-900 mt-0.5 shrink-0" />
                <span class="text-[11px] leading-relaxed">
                    Jika tanggal lahir Anda tidak cocok dengan data kampus, silakan hubungi panitia KPU DEMA di sekretariat pemilihan.
                </span>
            </div>
        </main>
        </div>
    </div>
</template>
