<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Printer, Home, ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
    receipt: Object,
    setting: Object,
});

const printReceipt = () => {
    window.print();
};
</script>

<template>
    <Head title="Tanda Terima Pemilihan Digital" />

    <div class="min-h-screen bg-slate-50 text-slate-900 flex flex-col justify-center py-12 px-4 sm:px-6 selection:bg-slate-900 selection:text-white">
        <main class="max-w-md mx-auto w-full space-y-6">
            <!-- Kartu Bukti Pemilihan -->
            <div class="bg-white border border-slate-200 rounded-lg p-6 sm:p-8 shadow-sm space-y-6 print:border-none print:shadow-none">
                <!-- Status Puncak -->
                <div class="text-center space-y-1.5">
                    <div class="w-12 h-12 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center mx-auto">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-950">
                        Suara Berhasil Dikirim
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500">
                        Terima kasih atas partisipasi Anda dalam menentukan arah kepengurusan baru.
                    </p>
                </div>

                <!-- Bagian Tanda Terima Formal -->
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 space-y-3 font-mono text-xs sm:text-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                        <span class="text-slate-500 font-sans">Penyelenggara:</span>
                        <span class="font-sans font-semibold text-slate-900">{{ setting.organization_name }}</span>
                    </div>

                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                        <span class="text-slate-500 font-sans">Kode Tanda Terima:</span>
                        <span class="font-bold text-slate-950 tracking-wider bg-white border border-slate-200 px-2.5 py-0.5 rounded text-xs sm:text-sm">
                            {{ receipt.code }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-sans">Waktu Pencatatan:</span>
                        <span class="text-slate-800 font-sans">{{ receipt.voted_at }}</span>
                    </div>
                </div>

                <!-- Pernyataan Integritas Anonim -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-start gap-2.5 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    <ShieldCheck class="w-4 h-4 text-slate-700 shrink-0 mt-0.5" />
                    <span>
                        Pilihan Anda tersimpan secara anonim dan terpisah dari identitas pemilih untuk menjamin prinsip kerahasiaan suara.
                    </span>
                </div>

                <!-- Tombol Aksi (disembunyikan saat cetak) -->
                <div class="flex flex-col sm:flex-row gap-2.5 pt-1.5 print:hidden">
                    <button
                        type="button"
                        @click="printReceipt"
                        class="flex-1 border border-slate-300 hover:border-slate-400 text-slate-700 font-semibold text-xs sm:text-sm py-2.5 px-3.5 rounded-lg transition-colors flex items-center justify-center gap-2 focus:ring-2 focus:ring-slate-900 focus:outline-none"
                    >
                        <Printer class="w-4 h-4" />
                        <span>Cetak Tanda Terima</span>
                    </button>

                    <Link
                        :href="route('home')"
                        class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs sm:text-sm py-2.5 px-3.5 rounded-lg transition-colors flex items-center justify-center gap-2 focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 focus:outline-none"
                    >
                        <Home class="w-4 h-4" />
                        <span>Selesai & Keluar</span>
                    </Link>
                </div>
            </div>

            <!-- Petunjuk Tambahan -->
            <p class="text-[11px] text-slate-400 text-center print:hidden">
                Simpan kode tanda terima sebagai bukti bahwa hak suara Anda telah sah digunakan dalam sistem.
            </p>
        </main>
    </div>
</template>
