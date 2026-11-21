<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    User,
    Mail,
    Lock,
    ShieldCheck,
    KeyRound,
    CheckCircle2,
    AlertCircle,
    Save,
    ShieldAlert
} from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

// Form 1: Ubah Nama dan Alamat Email
const profileForm = useForm({
    name: user.name,
    email: user.email,
});

const updateProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

// Form 2: Ubah Kata Sandi (Password)
const currentPasswordInput = ref(null);
const newPasswordInput = ref(null);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
                newPasswordInput.value?.focus();
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <Head title="Pengaturan Akun & Profil Panitia" />

    <AuthenticatedLayout>
        <template #title>
            Pengaturan Akun & Profil
        </template>

        <div class="max-w-5xl space-y-6">
            <!-- Header Pengantar -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-2xs">
                        {{ user.name.charAt(0) }}
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-950">
                            {{ user.name }}
                        </h2>
                        <div class="flex flex-wrap items-center gap-2 mt-0.5 text-xs text-slate-500">
                            <span>{{ user.email }}</span>
                            <span>&bull;</span>
                            <span class="inline-flex items-center gap-1 font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                <ShieldCheck class="w-3 h-3 text-emerald-600" />
                                <span>Administrator Utama</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 shrink-0">
                    Sistem Pemilihan Terlindungi Polkesmar
                </div>
            </div>

            <!-- Grid 2 Kolom Formulir -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Kartu 1: Ubah Informasi Profil & Email -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                                <User class="w-4 h-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-950">
                                    Informasi Profil & Email
                                </h3>
                                <p class="text-xs text-slate-500">
                                    Perbarui nama akun panitia dan alamat email login Anda.
                                </p>
                            </div>
                        </div>

                        <!-- Status Toast Berhasil -->
                        <div
                            v-if="profileForm.recentlySuccessful"
                            class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-2 animate-in fade-in"
                        >
                            <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                            <span>Profil dan alamat email berhasil diperbarui.</span>
                        </div>

                        <form @submit.prevent="updateProfile" id="profileForm" class="space-y-3.5 text-xs">
                            <div>
                                <label for="name" class="block font-semibold text-slate-700 mb-1">
                                    Nama Panitia / Administrator
                                </label>
                                <div class="relative">
                                    <User class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    <input
                                        id="name"
                                        type="text"
                                        v-model="profileForm.name"
                                        required
                                        autocomplete="name"
                                        placeholder="Nama Lengkap Panitia"
                                        class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:border-slate-900 text-sm focus:outline-none"
                                    />
                                </div>
                                <div v-if="profileForm.errors.name" class="mt-1 text-rose-600 text-xs">
                                    {{ profileForm.errors.name }}
                                </div>
                            </div>

                            <div>
                                <label for="email" class="block font-semibold text-slate-700 mb-1">
                                    Alamat Email Login
                                </label>
                                <div class="relative">
                                    <Mail class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    <input
                                        id="email"
                                        type="email"
                                        v-model="profileForm.email"
                                        required
                                        autocomplete="username"
                                        placeholder="admin@kampus.ac.id"
                                        class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:border-slate-900 text-sm focus:outline-none"
                                    />
                                </div>
                                <div v-if="profileForm.errors.email" class="mt-1 text-rose-600 text-xs">
                                    {{ profileForm.errors.email }}
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button
                            type="submit"
                            form="profileForm"
                            :disabled="profileForm.processing"
                            class="bg-slate-900 hover:bg-slate-800 disabled:bg-slate-400 text-white font-semibold text-xs py-2 px-4 rounded-lg flex items-center gap-1.5 transition-colors shadow-xs"
                        >
                            <Save class="w-3.5 h-3.5" />
                            <span>{{ profileForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Kartu 2: Ubah Kata Sandi (Password) -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                                <KeyRound class="w-4 h-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-950">
                                    Perbarui Kata Sandi
                                </h3>
                                <p class="text-xs text-slate-500">
                                    Gunakan kata sandi yang kuat untuk melindungi hak akses panel.
                                </p>
                            </div>
                        </div>

                        <!-- Status Toast Berhasil -->
                        <div
                            v-if="passwordForm.recentlySuccessful"
                            class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-2 animate-in fade-in"
                        >
                            <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                            <span>Kata sandi baru berhasil disimpan.</span>
                        </div>

                        <form @submit.prevent="updatePassword" id="passwordForm" class="space-y-3.5 text-xs">
                            <div>
                                <label for="current_password" class="block font-semibold text-slate-700 mb-1">
                                    Kata Sandi Saat Ini
                                </label>
                                <div class="relative">
                                    <Lock class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    <input
                                        id="current_password"
                                        ref="currentPasswordInput"
                                        type="password"
                                        v-model="passwordForm.current_password"
                                        required
                                        autocomplete="current-password"
                                        placeholder="Masukkan kata sandi saat ini"
                                        class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:border-slate-900 text-sm focus:outline-none"
                                    />
                                </div>
                                <div v-if="passwordForm.errors.current_password" class="mt-1 text-rose-600 text-xs">
                                    {{ passwordForm.errors.current_password }}
                                </div>
                            </div>

                            <div>
                                <label for="password" class="block font-semibold text-slate-700 mb-1">
                                    Kata Sandi Baru
                                </label>
                                <div class="relative">
                                    <Lock class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    <input
                                        id="password"
                                        ref="newPasswordInput"
                                        type="password"
                                        v-model="passwordForm.password"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Minimal 8 karakter"
                                        class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:border-slate-900 text-sm focus:outline-none"
                                    />
                                </div>
                                <div v-if="passwordForm.errors.password" class="mt-1 text-rose-600 text-xs">
                                    {{ passwordForm.errors.password }}
                                </div>
                            </div>

                            <div>
                                <label for="password_confirmation" class="block font-semibold text-slate-700 mb-1">
                                    Konfirmasi Kata Sandi Baru
                                </label>
                                <div class="relative">
                                    <Lock class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        v-model="passwordForm.password_confirmation"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Ketik ulang kata sandi baru"
                                        class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:border-slate-900 text-sm focus:outline-none"
                                    />
                                </div>
                                <div v-if="passwordForm.errors.password_confirmation" class="mt-1 text-rose-600 text-xs">
                                    {{ passwordForm.errors.password_confirmation }}
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button
                            type="submit"
                            form="passwordForm"
                            :disabled="passwordForm.processing"
                            class="bg-slate-900 hover:bg-slate-800 disabled:bg-slate-400 text-white font-semibold text-xs py-2 px-4 rounded-lg flex items-center gap-1.5 transition-colors shadow-xs"
                        >
                            <KeyRound class="w-3.5 h-3.5" />
                            <span>{{ passwordForm.processing ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Kartu 3: Ringkasan Keamanan & Standar Integritas -->
            <div class="bg-slate-900 text-white border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-emerald-400 shrink-0">
                        <ShieldCheck class="w-5 h-5" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-white">
                            Standar Keamanan Panel Pemira
                        </h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Panel ini dilindungi enkripsi sandi Bcrypt 12 putaran, regenerasi token sesi otomatis untuk mencegah pembajakan sesi (*session fixation*), serta pembatasan percobaan masuk (*rate limit lockout*). Seluruh perubahan data terekam pada sistem audit pemilihan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
