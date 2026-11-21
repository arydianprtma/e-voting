<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ShieldCheck,
    Vote,
    BarChart3,
    Lock,
    AlertCircle,
    ArrowRight,
    UserCheck,
    Clock,
    UserPlus
} from 'lucide-vue-next';

const props = defineProps({
    setting: Object,
    summary: Object,
    activeSession: Object,
    sessions: Array,
});

// Form Login dengan NIM dan Password Akun
const passwordForm = useForm({
    student_id: '',
    password: '',
});

const submitPasswordLogin = () => {
    passwordForm.post(route('voter.login'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="setting.title" />

    <div class="min-h-screen bg-slate-50 text-slate-900 flex flex-col justify-between selection:bg-slate-900 selection:text-white">
        <!-- Header Navigasi -->
        <header class="border-b border-slate-200 bg-white sticky top-0 z-30 shadow-2xs pt-[env(safe-area-inset-top,0px)]">
            <div class="max-w-5xl mx-auto px-3 sm:px-6 h-14 sm:h-16 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                    <img
                        :src="setting.logo_url || $page.props.app_setting?.logo_url || '/images/logo-default.png'"
                        :alt="setting.organization_name"
                        class="w-8 h-8 sm:w-9 sm:h-9 object-contain shrink-0"
                    />
                    <div class="min-w-0 leading-tight">
                        <div class="font-bold text-xs sm:text-sm text-slate-950 truncate max-w-[130px] xs:max-w-[190px] sm:max-w-none">
                            {{ setting.organization_name }}
                        </div>
                        <div class="text-[11px] text-slate-700 font-semibold truncate hidden xs:block sm:block">
                            Portal Pemilihan Resmi
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                    <Link
                        :href="route('voter.register')"
                        class="text-xs font-bold text-slate-950 bg-slate-100 hover:bg-slate-200 border border-slate-400 rounded-md px-2.5 sm:px-3 py-1.5 flex items-center gap-1.5 transition-colors focus:ring-2 focus:ring-slate-900 focus:outline-none whitespace-nowrap shrink-0 shadow-2xs"
                    >
                        <UserPlus class="w-3.5 h-3.5 shrink-0 text-slate-900 stroke-[2.5]" />
                        <span class="hidden sm:inline">Daftar Pemilih</span>
                        <span class="sm:hidden">Daftar</span>
                    </Link>

                    <Link
                        v-if="setting.show_quick_count"
                        :href="route('quick_count.show')"
                        class="text-xs font-bold text-slate-800 hover:text-slate-950 border border-slate-300 hover:border-slate-400 rounded-md p-1.5 sm:px-2.5 sm:py-1.5 flex items-center gap-1.5 transition-colors focus:ring-2 focus:ring-slate-900 focus:outline-none whitespace-nowrap shrink-0"
                        title="Quick Count Hasil Suara"
                    >
                        <BarChart3 class="w-3.5 h-3.5 shrink-0 stroke-[2.5]" />
                        <span class="hidden md:inline">Quick Count</span>
                    </Link>

                    <Link
                        :href="route('login')"
                        class="text-xs font-bold text-slate-800 hover:text-slate-950 px-1.5 sm:px-2 py-1.5 transition-colors focus:ring-2 focus:ring-slate-900 focus:outline-none rounded whitespace-nowrap shrink-0"
                    >
                        Panitia
                    </Link>
                </div>
            </div>
        </header>

        <!-- Konten Utama -->
        <main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-12 flex-1 w-full grid md:grid-cols-12 gap-8 items-start">
            <!-- Kolom Kiri: Informasi Pemilihan -->
            <div class="md:col-span-7 space-y-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded bg-slate-100 text-slate-950 text-xs font-bold mb-3 border border-slate-300">
                        <span class="w-2 h-2 rounded-full" :class="setting.is_active ? 'bg-emerald-600' : 'bg-rose-600'"></span>
                        <span>{{ setting.is_active ? 'Bilik Suara Dibuka' : 'Bilik Suara Ditutup' }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-950">
                        {{ setting.title }}
                    </h1>
                    <p class="mt-2 text-sm text-slate-800 font-medium leading-relaxed">
                        {{ setting.announcement || 'Gunakan hak suara Anda secara jujur, adil, langsung, umum, bebas, dan rahasia untuk menentukan kepengurusan baru.' }}
                    </p>
                </div>

                <!-- Informasi Sesi Pemilihan Aktif -->
                <div v-if="activeSession" class="p-4 rounded-xl border border-slate-300 bg-slate-100/70 text-xs sm:text-sm flex items-start gap-3 text-slate-950 shadow-2xs">
                    <Clock class="w-4 h-4 text-slate-800 mt-0.5 shrink-0 stroke-[2.5]" />
                    <div>
                        <div class="font-extrabold flex items-center gap-1.5 text-xs sm:text-sm text-slate-950">
                            <span>Sesi Pemilihan Sedang Berlangsung</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                        </div>
                        <div class="mt-0.5 font-bold text-slate-900 text-xs sm:text-sm">
                            {{ activeSession.name }} ({{ activeSession.start_time }} - {{ activeSession.end_time }} WIB)
                        </div>
                        <div class="text-xs text-slate-700 font-medium mt-0.5">
                            Target: <span class="font-bold text-slate-950">{{ activeSession.target_faculty || 'Semua Jurusan DPT' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Prinsip Integritas -->
                <div class="border border-slate-200 rounded-xl p-5 bg-white space-y-3.5 shadow-2xs">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                        Ketentuan Pemilihan Digital
                    </h2>
                    <ul class="text-xs sm:text-sm text-slate-800 font-medium space-y-2.5 leading-relaxed">
                        <li class="flex items-start gap-2.5">
                            <ShieldCheck class="w-4 h-4 text-slate-950 mt-0.5 shrink-0 stroke-[2.5]" />
                            <span><strong class="text-slate-950 font-bold">Satu Mahasiswa, Satu Suara:</strong> Setiap akun terdaftar di DPT hanya dapat mengirim 1 kali suara sah ke bilik suara.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <Lock class="w-4 h-4 text-slate-950 mt-0.5 shrink-0 stroke-[2.5]" />
                            <span><strong class="text-slate-950 font-bold">Kerahasiaan Terjamin:</strong> Pilihan pasangan calon disimpan secara anonim dan terpisah dari identitas pemilih.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <Vote class="w-4 h-4 text-slate-950 mt-0.5 shrink-0 stroke-[2.5]" />
                            <span><strong class="text-slate-950 font-bold">Tanda Terima Digital:</strong> Anda akan menerima kode unik bukti partisipasi setelah suara selesai dikirim.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Kolom Kanan: Form Masuk Bilik Suara -->
            <div class="md:col-span-5">
                <div class="border border-slate-200 rounded-2xl bg-white p-5 sm:p-6 shadow-xs space-y-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-100 text-slate-900 border border-slate-200 text-xs font-bold mb-1.5">
                            <Lock class="w-3 h-3 text-slate-950 stroke-[2.5]" />
                            <span>Autentikasi Pemilih</span>
                        </div>
                        <h2 class="text-base font-extrabold text-slate-950">
                            Masuk ke Bilik Suara
                        </h2>
                        <p class="text-xs text-slate-700 font-medium mt-0.5">
                            Gunakan NIM dan password akun pemilih yang telah Anda daftarkan.
                        </p>
                    </div>

                    <!-- Notifikasi Error Form Password -->
                    <div
                        v-if="passwordForm.errors.student_id || passwordForm.errors.password"
                        class="p-3 rounded-lg bg-rose-50 border border-rose-300 text-rose-900 text-xs font-bold flex items-start gap-2"
                    >
                        <AlertCircle class="w-4 h-4 text-rose-700 mt-0.5 shrink-0" />
                        <div>{{ passwordForm.errors.student_id || passwordForm.errors.password }}</div>
                    </div>

                    <!-- Form Login dengan Password -->
                    <form @submit.prevent="submitPasswordLogin" class="space-y-3.5">
                        <div>
                            <label for="pwd_student_id" class="block text-xs font-bold text-slate-900 mb-1">
                                Nomor Induk Mahasiswa (NIM)
                            </label>
                            <input
                                id="pwd_student_id"
                                type="text"
                                v-model="passwordForm.student_id"
                                required
                                autofocus
                                placeholder="Contoh: P1337434124001"
                                class="w-full text-sm font-mono uppercase tracking-wider px-3 py-2 border border-slate-300 rounded-lg text-slate-950 font-bold placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label for="pwd_password" class="block text-xs font-bold text-slate-900 mb-1">
                                Password Akun
                            </label>
                            <input
                                id="pwd_password"
                                type="password"
                                v-model="passwordForm.password"
                                required
                                placeholder="Masukkan password Anda"
                                class="w-full text-sm px-3 py-2 border border-slate-300 rounded-lg text-slate-950 font-bold placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none"
                            />
                        </div>

                        <div class="pt-1">
                            <button
                                type="submit"
                                :disabled="passwordForm.processing || !setting.is_active"
                                class="w-full bg-slate-950 hover:bg-slate-800 disabled:bg-slate-400 text-white font-bold text-xs sm:text-sm py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 transition-colors shadow-xs focus:ring-2 focus:ring-slate-900 focus:outline-none cursor-pointer"
                            >
                                <span v-if="passwordForm.processing">Memverifikasi Akun...</span>
                                <span v-else>Masuk ke Bilik Suara</span>
                                <ArrowRight v-if="!passwordForm.processing" class="w-4 h-4 stroke-[2.5]" />
                            </button>
                        </div>
                    </form>

                    <!-- Link Registrasi Akun Mandiri -->
                    <div class="pt-3.5 border-t border-slate-200 space-y-2 text-center text-xs">
                        <div class="text-slate-700 font-medium">
                            Belum memiliki akun pemilih?
                        </div>
                        <Link
                            :href="route('voter.register')"
                            class="w-full py-2 px-3 border border-slate-400 hover:border-slate-500 hover:bg-slate-100 rounded-lg text-slate-950 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors shadow-2xs"
                        >
                            <UserPlus class="w-3.5 h-3.5 text-slate-900 stroke-[2.5]" />
                            <span>Daftar / Aktivasi Akun Pemilih</span>
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white py-4 mt-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-700 font-medium">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-slate-900 font-bold">{{ setting.organization_name }} &copy; 2026.</span>
                    <span class="text-slate-400 hidden sm:inline">&bull;</span>
                    <span>
                        Developed by
                        <a
                            href="https://www.instagram.com/arydianprtma?utm_source=ig_web_button_share_sheet&vrfl=ZDNlZDc0MzIxNw=="
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-bold text-slate-900 hover:text-slate-950 hover:underline transition-colors"
                        >
                            Ary Dian Pratama
                        </a>
                    </span>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-600 font-medium">
                    <span>Sistem Pemilihan Terlindungi</span>
                    <span>&bull;</span>
                    <span>Asas LUBER JURDIL</span>
                </div>
            </div>
        </footer>
    </div>
</template>
