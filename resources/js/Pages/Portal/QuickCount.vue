<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RollingNumber from '@/Components/RollingNumber.vue';
import {
    BarChart3,
    Users,
    CheckCircle2,
    ArrowLeft,
    RefreshCw,
    Clock,
    ShieldCheck,
    GraduationCap,
    TrendingUp,
    Radio
} from 'lucide-vue-next';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    setting: Object,
    candidates: Array,
    summary: Object,
    sessions: Array,
});

const isRefreshing = ref(false);

const reload = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['candidates', 'summary', 'sessions'],
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

// Palet warna resmi terdistribusi untuk masing-masing paslon
const candidateColors = ['#0f766e', '#3730a3', '#b45309', '#0369a1'];

// Cari suara tertinggi untuk status pemimpin sementara
const maxVotes = computed(() => {
    if (!props.candidates?.length) return 0;
    return Math.max(...props.candidates.map((c) => c.votes));
});

const isTied = computed(() => {
    if (maxVotes.value === 0) return false;
    const leaders = props.candidates.filter((c) => c.votes === maxVotes.value);
    return leaders.length > 1;
});

const series = computed(() => [
    {
        name: 'Perolehan Suara',
        data: props.candidates.map((c) => c.votes),
    },
]);

const chartOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '38%',
            borderRadius: 6,
            distributed: true,
            dataLabels: {
                position: 'top',
            },
        },
    },
    dataLabels: {
        enabled: true,
        formatter: (val, opt) => {
            const candidate = props.candidates[opt.dataPointIndex];
            const pct = candidate ? candidate.percentage : 0;
            return `${val} Suara (${pct}%)`;
        },
        offsetY: -22,
        style: {
            fontSize: '12px',
            fontWeight: 700,
            colors: ['#0F172A'],
        },
    },
    colors: candidateColors,
    legend: {
        show: false,
    },
    xaxis: {
        categories: props.candidates.map((c) => `Paslon ${String(c.number).padStart(2, '0')}`),
        labels: {
            style: {
                fontSize: '13px',
                fontWeight: 700,
                colors: '#334155',
            },
        },
        axisBorder: {
            color: '#E2E8F0',
        },
    },
    yaxis: {
        labels: {
            formatter: (val) => Math.round(val),
            style: {
                colors: '#64748B',
                fontSize: '11px',
            },
        },
    },
    grid: {
        borderColor: '#F1F5F9',
        strokeDashArray: 4,
    },
    tooltip: {
        y: {
            formatter: (val, opt) => {
                const candidate = props.candidates[opt.dataPointIndex];
                const pct = candidate ? candidate.percentage : 0;
                return `${val} Suara (${pct}% dari suara masuk)`;
            },
        },
    },
}));
</script>

<template>
    <Head :title="'Quick Count - ' + (setting.organization_name || 'Hasil Pemilihan')" />

    <div class="min-h-screen bg-slate-100/70 text-slate-900 flex flex-col justify-between selection:bg-slate-900 selection:text-white">
        <!-- Top Navigation Bar -->
        <header class="border-b border-slate-200 bg-white sticky top-0 z-30 shadow-xs pt-[env(safe-area-inset-top,0px)]">
            <div class="max-w-6xl mx-auto px-3 sm:px-6 h-14 sm:h-16 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                    <Link
                        :href="route('home')"
                        class="border border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-lg p-2 text-slate-600 hover:text-slate-900 transition-colors focus:ring-2 focus:ring-slate-900 focus:outline-none shrink-0"
                        title="Kembali ke Beranda Portal"
                    >
                        <ArrowLeft class="w-4 h-4" />
                    </Link>

                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <img
                            :src="setting?.logo_url || $page.props.app_setting?.logo_url || '/images/logo-default.png'"
                            :alt="setting?.organization_name || 'Logo Lembaga'"
                            class="w-8 h-8 sm:w-9 sm:h-9 object-contain shrink-0"
                        />
                        <div class="leading-tight min-w-0">
                            <div class="font-bold text-xs sm:text-sm text-slate-950 flex items-center gap-2">
                                <span class="truncate">Quick Count</span>
                                <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    <span>Live</span>
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-500 hidden sm:block truncate">
                                {{ setting.title || 'Pemilihan Raya DEMA Polkesmar 2026/2027' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Refresh & Indikator Waktu -->
                <div class="flex items-center gap-3">
                    <div class="hidden md:block text-right text-[11px] text-slate-500">
                        <div>Pembaruan Terakhir</div>
                        <div class="font-mono font-medium text-slate-700">{{ summary.last_updated }} WIB</div>
                    </div>

                    <button
                        type="button"
                        @click="reload"
                        :disabled="isRefreshing"
                        class="border border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-lg px-3 py-1.5 text-xs text-slate-700 hover:text-slate-950 font-semibold flex items-center gap-1.5 transition-colors shadow-2xs focus:ring-2 focus:ring-slate-900 focus:outline-none disabled:opacity-50"
                    >
                        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isRefreshing }" />
                        <span>Segarkan Data</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8 flex-1 w-full space-y-6">
            <!-- Banner Pemantauan Pemilu -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-600">
                        <Radio class="w-4 h-4 text-emerald-600 animate-pulse" />
                        <span>Papan Rekapitulasi Suara Resmi</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-950 tracking-tight">
                        Hitung Cepat Hasil Perolehan Suara
                    </h1>
                    <p class="text-sm text-slate-600">
                        Hasil tabulasi suara sementara yang masuk secara sah melalui bilik suara elektronik terenkripsi.
                    </p>
                </div>

                <div class="flex items-center gap-2 self-start md:self-center shrink-0">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold border border-slate-200">
                        <ShieldCheck class="w-4 h-4 text-slate-700 shrink-0" />
                        <span>Data Terkunci Kriptografi</span>
                    </div>
                </div>
            </div>

            <!-- Kartu Statistik Partisipasi (4 Kartu Seimbang) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total DPT -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
                    <div class="flex items-center justify-between text-slate-700 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Total DPT</span>
                        <Users class="w-4 h-4 text-slate-700" />
                    </div>
                    <div class="text-2xl sm:text-3xl font-black font-mono text-slate-950">
                        <RollingNumber :value="summary.total_voters" />
                    </div>
                    <div class="text-xs text-slate-700 font-medium mt-1">
                        Daftar Pemilih Tetap
                    </div>
                </div>

                <!-- Suara Masuk -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
                    <div class="flex items-center justify-between text-slate-700 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Suara Masuk</span>
                        <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                    </div>
                    <div class="text-2xl sm:text-3xl font-black font-mono text-emerald-800">
                        <RollingNumber :value="summary.total_votes" />
                    </div>
                    <div class="text-xs text-slate-700 font-medium mt-1">
                        Suara sah terverifikasi
                    </div>
                </div>

                <!-- Belum Memilih -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
                    <div class="flex items-center justify-between text-slate-700 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Belum Memilih</span>
                        <Clock class="w-4 h-4 text-slate-700" />
                    </div>
                    <div class="text-2xl sm:text-3xl font-black font-mono text-slate-800">
                        <RollingNumber :value="summary.remaining_votes" />
                    </div>
                    <div class="text-xs text-slate-700 font-medium mt-1">
                        Hak suara belum digunakan
                    </div>
                </div>

                <!-- Tingkat Partisipasi -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
                    <div class="flex items-center justify-between text-slate-700 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Tingkat Partisipasi</span>
                        <TrendingUp class="w-4 h-4 text-emerald-600" />
                    </div>
                    <div class="text-2xl sm:text-3xl font-black font-mono text-slate-950">
                        <RollingNumber :value="summary.participation_percentage" suffix="%" />
                    </div>
                    <!-- Mini Progress Bar Partisipasi -->
                    <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2.5 overflow-hidden">
                        <div
                            class="bg-emerald-600 h-1.5 rounded-full transition-all duration-500"
                            :style="{ width: `${Math.min(100, summary.participation_percentage)}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Komparasi Head-to-Head Paslon (Kartu Visual Perolehan) -->
            <div v-if="candidates && candidates.length > 0" class="grid md:grid-cols-2 gap-6">
                <article
                    v-for="(candidate, index) in candidates"
                    :key="candidate.id"
                    class="bg-white border-2 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col justify-between transition-all"
                    :class="maxVotes > 0 && candidate.votes === maxVotes && !isTied
                        ? 'border-emerald-600 ring-1 ring-emerald-600'
                        : 'border-slate-200'"
                >
                    <div>
                        <!-- Header Paslon & Status Perolehan -->
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <!-- Badge Nomor Urut Paslon -->
                                <div
                                    class="w-11 h-11 rounded-xl text-white font-black font-mono text-xl flex items-center justify-center shadow-xs shrink-0"
                                    :style="{ backgroundColor: candidateColors[index % candidateColors.length] }"
                                >
                                    {{ String(candidate.number).padStart(2, '0') }}
                                </div>

                                <!-- Foto Portrait 4x6 / 3x4 Calon -->
                                <div
                                    v-if="candidate.photo_path"
                                    class="w-12 h-16 rounded-lg border border-slate-200 bg-slate-100 overflow-hidden shrink-0 shadow-2xs flex items-center justify-center"
                                >
                                    <img
                                        :src="candidate.photo_path"
                                        :alt="'Foto Calon ' + candidate.number"
                                        class="h-full w-full object-cover object-top"
                                    />
                                </div>

                                <div>
                                    <div class="text-xs uppercase font-bold tracking-wider text-slate-700">
                                        {{ candidate.deputy ? 'Pasangan Calon' : 'Calon Tunggal' }}
                                    </div>
                                    <div class="text-sm sm:text-base font-extrabold text-slate-950">
                                        Nomor Urut {{ candidate.number }}
                                    </div>
                                </div>
                            </div>

                            <!-- Badge Status Pemimpin Sementara -->
                            <div v-if="summary.total_votes > 0">
                                <span
                                    v-if="candidate.votes === maxVotes && !isTied"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-md bg-slate-900 text-white"
                                >
                                    <TrendingUp class="w-4 h-4 text-emerald-400" />
                                    <span>Memimpin Sementara</span>
                                </span>
                                <span
                                    v-else-if="candidate.votes === maxVotes && isTied"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-md bg-slate-100 text-slate-900 border border-slate-300"
                                >
                                    <span>Perolehan Seimbang</span>
                                </span>
                            </div>
                        </div>

                        <!-- Nama Paslon & Jurusan -->
                        <div class="space-y-1.5 py-2.5 border-y border-slate-100">
                            <h2 class="text-lg sm:text-xl font-black text-slate-950 leading-snug">
                                {{ candidate.deputy ? `${candidate.chairman} & ${candidate.deputy}` : candidate.chairman }}
                            </h2>
                            <div class="flex items-center gap-2 text-sm text-slate-800 font-semibold">
                                <GraduationCap class="w-4 h-4 text-slate-700 shrink-0" />
                                <span>{{ candidate.faculty_info || 'Poltekkes Kemenkes Semarang' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Angka Perolehan & Persentase Bar -->
                    <div class="pt-4 mt-4 space-y-2">
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs font-bold text-slate-700">
                                Perolehan Suara
                            </span>
                            <div class="flex items-baseline gap-2">
                                <span class="text-sm font-bold font-mono text-slate-900">
                                    <RollingNumber :value="candidate.votes" suffix=" Suara" />
                                </span>
                                <span
                                    class="text-2xl sm:text-3xl font-black font-mono"
                                    :style="{ color: candidateColors[index % candidateColors.length] }"
                                >
                                    <RollingNumber :value="candidate.percentage" suffix="%" />
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar Paslon -->
                        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                            <div
                                class="h-3 rounded-full transition-all duration-500"
                                :style="{
                                    width: `${Math.min(100, candidate.percentage)}%`,
                                    backgroundColor: candidateColors[index % candidateColors.length]
                                }"
                            ></div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Empty State Paslon Belum Ada -->
            <div
                v-else
                class="bg-white border-2 border-dashed border-slate-300 rounded-2xl p-8 sm:p-10 text-center shadow-xs space-y-3"
            >
                <div class="w-14 h-14 rounded-2xl bg-slate-100 border border-slate-200 text-slate-700 mx-auto flex items-center justify-center shadow-xs">
                    <BarChart3 class="w-7 h-7 text-slate-800" />
                </div>
                <h3 class="text-base sm:text-lg font-bold text-slate-950">
                    Belum Ada Data Pasangan Calon
                </h3>
                <p class="text-xs sm:text-sm text-slate-700 font-medium max-w-md mx-auto leading-relaxed">
                    Data perolehan suara akan muncul otomatis di sini setelah panitia KPU menetapkan pasangan calon dan pemungutan suara berlangsung.
                </p>
            </div>

            <!-- Diagram Perolehan Suara (ApexCharts) -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-950 flex items-center gap-2">
                            <BarChart3 class="w-4 h-4 text-slate-800" />
                            <span>Visualisasi Grafik Perolehan Suara</span>
                        </h2>
                        <p class="text-xs text-slate-700 font-medium mt-0.5">
                            Perbandingan langsung jumlah suara masing-masing pasangan calon.
                        </p>
                    </div>
                </div>

                <div v-if="summary.total_votes > 0" class="pt-2">
                    <VueApexCharts
                        type="bar"
                        height="300"
                        :options="chartOptions"
                        :series="series"
                    />
                </div>
                <div v-else class="py-12 text-center text-xs text-slate-700 font-medium border border-dashed border-slate-300 rounded-xl bg-slate-50">
                    Belum ada suara yang masuk ke dalam sistem bilik suara.
                </div>
            </div>

            <!-- Tabel Rekapitulasi Rincian Hasil -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-950">
                            Tabel Rekapitulasi Suara
                        </h2>
                        <p class="text-xs text-slate-700 font-medium mt-0.5">
                            Rincian detail perolehan suara sah per pasangan calon.
                        </p>
                    </div>
                    <span class="text-xs font-bold text-slate-800">
                        Total Suara: {{ summary.total_votes }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-800 font-bold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3.5 px-4 w-20 text-center">No. Urut</th>
                                <th class="py-3.5 px-4">Pasangan Calon</th>
                                <th class="py-3.5 px-4 hidden sm:table-cell">Jurusan</th>
                                <th class="py-3.5 px-4 w-40 text-center">Proporsi</th>
                                <th class="py-3.5 px-4 text-right">Jumlah Suara</th>
                                <th class="py-3.5 px-4 text-right">Persentase</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            <template v-if="candidates && candidates.length > 0">
                                <tr
                                    v-for="(c, idx) in candidates"
                                    :key="c.id"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <td class="py-4 px-4 text-center">
                                        <span
                                            class="inline-block font-mono font-black text-xs text-white px-2 py-0.5 rounded"
                                            :style="{ backgroundColor: candidateColors[idx % candidateColors.length] }"
                                        >
                                            {{ String(c.number).padStart(2, '0') }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-slate-950 text-sm">
                                            {{ c.deputy ? `${c.chairman} & ${c.deputy}` : c.chairman }}
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 hidden sm:table-cell text-slate-800 font-medium">
                                        {{ c.faculty_info }}
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div
                                                class="h-2 rounded-full"
                                                :style="{
                                                    width: `${Math.min(100, c.percentage)}%`,
                                                    backgroundColor: candidateColors[idx % candidateColors.length]
                                                }"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-right font-mono font-bold text-slate-950 text-sm">
                                        <RollingNumber :value="c.votes" />
                                    </td>
                                    <td class="py-4 px-4 text-right font-mono font-extrabold text-sm" :style="{ color: candidateColors[idx % candidateColors.length] }">
                                        <RollingNumber :value="c.percentage" suffix="%" />
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="6" class="py-8 text-center text-xs text-slate-700 font-medium">
                                    Belum ada data pasangan calon yang terdaftar dalam pemilihan ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rekapitulasi Partisipasi Per Sesi Pemilihan -->
            <div v-if="sessions && sessions.length > 0" class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-950 flex items-center gap-2">
                            <Clock class="w-4 h-4 text-slate-800" />
                            <span>Rekapitulasi Suara per Sesi Pemilihan</span>
                        </h2>
                        <p class="text-xs text-slate-700 font-medium mt-0.5">
                            Distribusi partisipasi kehadiran mahasiswa berdasarkan jadwal gelombang sesi.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-800 font-bold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3.5 px-4">Nama Sesi Pemilihan</th>
                                <th class="py-3.5 px-4">Target Jurusan</th>
                                <th class="py-3.5 px-4">Waktu Pelaksanaan</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Suara Masuk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            <tr v-for="s in sessions" :key="s.id" class="hover:bg-slate-50/70">
                                <td class="py-3.5 px-4 font-bold text-slate-950">
                                    {{ s.name }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span v-if="s.target_faculty" class="px-2 py-0.5 rounded bg-slate-100 text-slate-900 text-[11px] font-bold border border-slate-300">
                                        {{ s.target_faculty }}
                                    </span>
                                    <span v-else class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-950 text-[11px] font-bold border border-emerald-300">
                                        Semua Jurusan DPT
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-800 font-semibold font-mono text-[11px]">
                                    {{ s.time_range }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="s.status_label === 'running'"
                                        class="inline-block px-2 py-0.5 rounded font-bold text-[10px] bg-emerald-100 text-emerald-900 border border-emerald-300"
                                    >
                                        Sedang Berlangsung
                                    </span>
                                    <span
                                        v-else-if="s.status_label === 'upcoming'"
                                        class="inline-block px-2 py-0.5 rounded font-bold text-[10px] bg-amber-100 text-amber-900 border border-amber-300"
                                    >
                                        Akan Datang
                                    </span>
                                    <span
                                        v-else
                                        class="inline-block px-2 py-0.5 rounded font-bold text-[10px] bg-slate-200 text-slate-800 border border-slate-300"
                                    >
                                        Selesai
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-950 text-sm">
                                    {{ s.votes_count }} Suara
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white py-4 mt-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-700 font-medium">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span>Komisi Pemilihan Umum Mahasiswa (KPUM) &copy; 2026.</span>
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
                <div class="flex items-center gap-3 text-[11px] text-slate-600 font-semibold">
                    <span>Transparansi Digital</span>
                    <span>&bull;</span>
                    <span>Diperbarui Otomatis</span>
                </div>
            </div>
        </footer>
    </div>
</template>
