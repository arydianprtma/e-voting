<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Users,
    CheckCircle2,
    Clock,
    BarChart3,
    ArrowUpRight,
    Settings,
    Vote,
    ExternalLink,
    ShieldCheck
} from 'lucide-vue-next';

const props = defineProps({
    setting: Object,
    candidates: Array,
    stats: Object,
});
</script>

<template>
    <Head title="Dashboard Panitia" />

    <AuthenticatedLayout>
        <template #title>
            Dashboard Ringkasan
        </template>

        <template #actions>
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border"
                    :class="setting.is_active ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
                >
                    <span class="relative flex h-2 w-2">
                        <span v-if="setting.is_active" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2" :class="setting.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                    </span>
                    <span>{{ setting.is_active ? 'Bilik Suara Aktif' : 'Bilik Suara Ditutup' }}</span>
                </span>

                <Link
                    :href="route('quick_count.show')"
                    target="_blank"
                    class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-md text-xs font-medium transition-colors"
                >
                    <BarChart3 class="w-3.5 h-3.5 text-emerald-400" />
                    <span>Quick Count Publik</span>
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Banner Pengumuman & Status Pemilihan -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-900 border border-slate-300">
                            {{ setting.organization_name }}
                        </span>
                        <span v-if="setting.show_quick_count" class="text-[11px] text-emerald-800 font-bold">
                            Hitung Cepat Terbuka Publik
                        </span>
                    </div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-950">
                        {{ setting.title }}
                    </h1>
                    <p class="text-xs text-slate-700 font-medium">
                        Pantau jalannya pemungutan suara secara transparan dan akurat secara berkala.
                    </p>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <Link
                        :href="route('admin.settings')"
                        class="flex-1 md:flex-none border border-slate-300 hover:border-slate-400 rounded-lg px-3 py-2 text-xs font-semibold text-slate-800 flex items-center justify-center gap-1.5 transition-colors"
                    >
                        <Settings class="w-3.5 h-3.5 text-slate-700" />
                        <span>Atur Jadwal</span>
                    </Link>

                    <Link
                        :href="route('home')"
                        target="_blank"
                        class="flex-1 md:flex-none border border-slate-300 hover:border-slate-400 rounded-lg px-3 py-2 text-xs font-semibold text-slate-800 flex items-center justify-center gap-1.5 transition-colors"
                    >
                        <ExternalLink class="w-3.5 h-3.5 text-slate-700" />
                        <span>Buka Bilik Suara</span>
                    </Link>
                </div>
            </div>

            <!-- 4 Kartu Metrik Utama -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total DPT -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between text-slate-700">
                        <span class="text-xs font-bold text-slate-800">Daftar Pemilih Tetap</span>
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                            <Users class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold font-mono text-slate-950">
                            {{ stats.total_voters }}
                        </div>
                        <div class="text-[11px] text-slate-700 font-medium mt-0.5">Total mahasiswa dalam DPT</div>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div class="bg-slate-900 h-1.5 rounded-full w-full"></div>
                    </div>
                </div>

                <!-- Suara Masuk -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between text-slate-700">
                        <span class="text-xs font-bold text-slate-800">Suara Masuk</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center">
                            <CheckCircle2 class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold font-mono text-slate-950">
                            {{ stats.voted_count }}
                        </div>
                        <div class="text-[11px] text-slate-700 font-medium mt-0.5">Hak suara sah terverifikasi</div>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div
                            class="bg-emerald-600 h-1.5 rounded-full transition-all duration-500"
                            :style="{ width: `${stats.turnout_percentage}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Belum Memilih -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between text-slate-700">
                        <span class="text-xs font-bold text-slate-800">Belum Memilih</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center">
                            <Clock class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold font-mono text-slate-950">
                            {{ stats.unvoted_count }}
                        </div>
                        <div class="text-[11px] text-slate-700 font-medium mt-0.5">Pemilih belum menggunakan hak</div>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div
                            class="bg-amber-500 h-1.5 rounded-full transition-all duration-500"
                            :style="{ width: `${stats.total_voters > 0 ? (stats.unvoted_count / stats.total_voters) * 100 : 0}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Partisipasi Pemilih -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between text-slate-700">
                        <span class="text-xs font-bold text-slate-800">Partisipasi DPT</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center">
                            <BarChart3 class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold font-mono text-slate-950">
                            {{ stats.turnout_percentage }}%
                        </div>
                        <div class="text-[11px] text-slate-700 font-medium mt-0.5">Persentase kehadiran suara</div>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div
                            class="bg-blue-600 h-1.5 rounded-full transition-all duration-500"
                            :style="{ width: `${stats.turnout_percentage}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Bagian Rekapitulasi Suara Paslon -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-950">Rekapitulasi Suara Pasangan Calon</h2>
                        <p class="text-xs text-slate-700 font-medium">Perolehan suara berdasarkan transaksi bilik suara digital.</p>
                    </div>

                    <Link
                        :href="route('admin.candidates')"
                        class="text-xs font-bold text-slate-950 hover:text-slate-700 flex items-center gap-1 transition-colors"
                    >
                        <span>Kelola Paslon</span>
                        <ArrowUpRight class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div
                        v-for="candidate in candidates"
                        :key="candidate.id"
                        class="border border-slate-200/90 rounded-xl p-5 bg-slate-50/50 hover:bg-white hover:border-slate-300 transition-all space-y-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <!-- Badge Nomor Urut -->
                                <div class="w-11 h-11 rounded-lg bg-white border border-slate-200 flex items-center justify-center font-mono font-bold text-slate-950 text-lg shadow-2xs shrink-0">
                                    {{ String(candidate.candidate_number).padStart(2, '0') }}
                                </div>

                                <!-- Foto Portrait 4x6 / 3x4 Calon -->
                                <div
                                    v-if="candidate.photo_path"
                                    class="w-12 h-16 rounded-lg border border-slate-200 bg-slate-100 overflow-hidden shrink-0 shadow-2xs flex items-center justify-center"
                                >
                                    <img
                                        :src="candidate.photo_path"
                                        :alt="'Foto Calon ' + candidate.candidate_number"
                                        class="h-full w-full object-cover object-top"
                                    />
                                </div>

                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-700">
                                            No. {{ candidate.candidate_number }}
                                        </span>
                                        <span
                                            class="text-[9px] font-bold px-1.5 py-0.2 rounded uppercase"
                                            :class="candidate.candidate_type === 'solo' || !candidate.deputy_name ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-blue-100 text-blue-900 border border-blue-300'"
                                        >
                                            {{ candidate.candidate_type === 'solo' || !candidate.deputy_name ? 'Solo' : 'Duo' }}
                                        </span>
                                    </div>
                                    <div class="text-sm font-bold text-slate-950 mt-0.5">{{ candidate.chairman_name }}</div>
                                    <div v-if="candidate.candidate_type !== 'solo' && candidate.deputy_name" class="text-xs text-slate-700 font-semibold">
                                        & {{ candidate.deputy_name }}
                                    </div>
                                    <div v-else class="text-[11px] text-amber-800 font-bold">
                                        Calon Tunggal
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="text-xl font-bold font-mono text-slate-950">
                                    {{ candidate.votes_count }}
                                </div>
                                <div class="text-[11px] text-slate-700 font-medium">Suara sah</div>
                            </div>
                        </div>

                        <!-- Progress Bar Suara -->
                        <div class="space-y-1.5 pt-2 border-t border-slate-200/60">
                            <div class="flex justify-between text-[11px]">
                                <span class="text-slate-700 font-medium">{{ candidate.faculty_info }}</span>
                                <span class="font-mono font-bold text-slate-950">
                                    {{ stats.total_votes > 0 ? Math.round((candidate.votes_count / stats.total_votes) * 100) : 0 }}%
                                </span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2">
                                <div
                                    class="bg-slate-900 h-2 rounded-full transition-all duration-500"
                                    :style="{ width: `${stats.total_votes > 0 ? (candidate.votes_count / stats.total_votes) * 100 : 0}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Aksi Cepat Bawah -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Link
                    :href="route('admin.voters')"
                    class="bg-white border border-slate-200 hover:border-slate-300 rounded-xl p-5 transition-all shadow-xs block group"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-950 group-hover:text-emerald-700 transition-colors">
                            Kelola Data Pemilih (DPT)
                        </span>
                        <ArrowUpRight class="w-4 h-4 text-slate-600 group-hover:text-slate-950 transition-colors" />
                    </div>
                    <p class="text-xs text-slate-700 font-medium leading-relaxed">
                        Kelola daftar pemilih tetap, impor berkas mahasiswa, dan pantau status partisipasi hak suara.
                    </p>
                </Link>

                <Link
                    :href="route('admin.candidates')"
                    class="bg-white border border-slate-200 hover:border-slate-300 rounded-xl p-5 transition-all shadow-xs block group"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-950 group-hover:text-emerald-700 transition-colors">
                            Kelola Pasangan Calon
                        </span>
                        <ArrowUpRight class="w-4 h-4 text-slate-600 group-hover:text-slate-950 transition-colors" />
                    </div>
                    <p class="text-xs text-slate-700 font-medium leading-relaxed">
                        Perbarui rincian visi, butir misi, serta identitas fakultas dari pasangan calon.
                    </p>
                </Link>

                <Link
                    :href="route('admin.settings')"
                    class="bg-white border border-slate-200 hover:border-slate-300 rounded-xl p-5 transition-all shadow-xs block group"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-950 group-hover:text-emerald-700 transition-colors">
                            Pengaturan Bilik Suara
                        </span>
                        <ArrowUpRight class="w-4 h-4 text-slate-600 group-hover:text-slate-950 transition-colors" />
                    </div>
                    <p class="text-xs text-slate-700 font-medium leading-relaxed">
                        Ubah jam mulai dan selesai pemilihan, atau buka/tutup bilik suara secara instan.
                    </p>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
