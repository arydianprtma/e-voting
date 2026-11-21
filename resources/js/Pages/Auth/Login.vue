<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Lock, Mail, ShieldCheck, UserCheck, ArrowRight } from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    showDemoCredentials: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const fillAdminDemo = () => {
    form.email = 'admin@kampus.ac.id';
    form.password = 'password';
};
</script>

<template>
    <Head title="Masuk Panitia Pemilihan - DEMA Polkesmar" />

    <div class="min-h-screen bg-slate-50 text-slate-900 flex flex-col justify-center py-10 px-4 sm:px-6 selection:bg-slate-900 selection:text-white">
        <div class="max-w-md mx-auto w-full space-y-4">
            <main class="w-full">
                <!-- Kartu Utama Login Panitia -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-7 shadow-xs space-y-5">
                    <!-- Branding Header -->
                    <div class="text-center space-y-2 border-b border-slate-100 pb-4">
                        <img
                            :src="$page.props.app_setting?.logo_url || '/images/logo-default.png'"
                            alt="Logo Lembaga"
                            class="w-12 h-12 object-contain mx-auto"
                        />
                        <div>
                            <h1 class="text-lg font-extrabold text-slate-950">
                                Masuk Panel Panitia
                            </h1>
                            <p class="text-xs text-slate-700 font-medium mt-1">
                                Akses khusus administrator dan panitia pemilihan umum mahasiswa.
                            </p>
                        </div>

                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-900 text-xs font-bold border border-slate-300">
                            <ShieldCheck class="w-3.5 h-3.5 text-slate-700 stroke-[2.5]" />
                            <span>Khusus Penyelenggara Pemilihan</span>
                        </div>
                    </div>

                    <!-- Notifikasi Status -->
                    <div v-if="status" class="p-3 rounded-lg bg-emerald-50 border border-emerald-300 text-emerald-950 text-xs font-bold">
                        {{ status }}
                    </div>

                    <!-- Form Login -->
                    <form @submit.prevent="submit" class="space-y-3.5 text-xs">
                        <div>
                            <label for="email" class="block font-bold text-slate-900 mb-1">
                                Alamat Email Panitia
                            </label>
                            <div class="relative">
                                <Mail class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                <input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    placeholder="admin@kampus.ac.id"
                                    class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-slate-950 placeholder:text-slate-400 font-semibold focus:border-slate-900 text-sm focus:outline-none"
                                />
                            </div>
                            <div v-if="form.errors.email" class="mt-1 text-rose-700 font-bold text-xs">
                                {{ form.errors.email }}
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block font-bold text-slate-900 mb-1">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <Lock class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                <input
                                    id="password"
                                    type="password"
                                    v-model="form.password"
                                    required
                                    placeholder="Masukkan kata sandi panitia"
                                    class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-slate-950 placeholder:text-slate-400 font-semibold focus:border-slate-900 text-sm focus:outline-none"
                                />
                            </div>
                            <div v-if="form.errors.password" class="mt-1 text-rose-700 font-bold text-xs">
                                {{ form.errors.password }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-0.5">
                            <label class="flex items-center gap-2 cursor-pointer text-slate-800 font-medium select-none">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="rounded border-slate-300 text-slate-950 focus:ring-slate-900"
                                />
                                <span>Ingat sesi saya</span>
                            </label>
                        </div>

                        <div class="pt-1.5">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full bg-slate-950 hover:bg-slate-800 disabled:bg-slate-400 text-white font-bold text-xs sm:text-sm py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 focus:ring-2 focus:ring-slate-950 focus:outline-none shadow-xs cursor-pointer"
                            >
                                <span v-if="form.processing">Memproses Masuk...</span>
                                <span v-else>Masuk ke Dashboard Panitia</span>
                                <ArrowRight v-if="!form.processing" class="w-4 h-4 stroke-[2.5]" />
                            </button>
                        </div>
                    </form>

                    <!-- Shortcut Pengujian Demo (Hanya tampil di environment lokal / debug) -->
                    <div v-if="showDemoCredentials" class="pt-2.5 border-t border-slate-200">
                        <button
                            type="button"
                            @click="fillAdminDemo"
                            class="w-full py-2 px-3 border border-dashed border-slate-300 hover:border-slate-400 rounded-lg text-slate-800 hover:text-slate-950 text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
                        >
                            <UserCheck class="w-3.5 h-3.5 text-slate-700" />
                            <span>Isi Kredensial Uji Coba (admin@kampus.ac.id / password)</span>
                        </button>
                    </div>
                </div>
            </main>

            <!-- Bantuan / Kembali ke Beranda -->
            <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200 bg-white text-xs sm:text-sm text-slate-800 font-medium flex items-center justify-between shadow-2xs">
                <span>Bukan panitia pemilihan?</span>
                <Link
                    :href="route('home')"
                    class="font-bold text-slate-950 hover:underline"
                >
                    Kembali ke Beranda &rarr;
                </Link>
            </div>

            <!-- Footer Hak Cipta & Developer -->
            <footer class="text-center text-xs text-slate-600 pt-2 space-y-1 font-medium">
                <div class="text-slate-700">Sistem Pemilihan Digital Terlindungi &copy; 2026.</div>
                <div class="text-xs text-slate-600">
                    Developed by
                    <a
                        href="https://www.instagram.com/arydianprtma?utm_source=ig_web_button_share_sheet&vrfl=ZDNlZDc0MzIxNw=="
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-bold text-slate-900 hover:text-slate-950 hover:underline transition-colors"
                    >
                        Ary Dian Pratama
                    </a>
                </div>
            </footer>
        </div>
    </div>
</template>
