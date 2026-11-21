<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { updateFavicon } from '@/Utils/favicon';
import {
    Settings,
    Save,
    Clock,
    ShieldCheck,
    BarChart3,
    ExternalLink,
    Lock,
    CheckCircle2,
    Calendar,
    Upload,
    RotateCcw,
    Image,
    Building2,
    MessageSquare,
    Phone
} from 'lucide-vue-next';

const props = defineProps({
    setting: Object,
});

const logoInputRef = ref(null);
const logoPreview = ref(props.setting.logo_url || '/images/logo-default.png');

const form = useForm({
    title: props.setting.title || '',
    organization_name: props.setting.organization_name || '',
    announcement: props.setting.announcement || '',
    start_time: props.setting.start_time ? props.setting.start_time.substring(0, 16) : '',
    end_time: props.setting.end_time ? props.setting.end_time.substring(0, 16) : '',
    is_active: Boolean(props.setting.is_active),
    show_quick_count: Boolean(props.setting.show_quick_count),
    helpdesk_contact: props.setting.helpdesk_contact || '',
    logo: null,
    reset_logo: false,
});

const onLogoSelected = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        form.reset_logo = false;
        const objectUrl = URL.createObjectURL(file);
        logoPreview.value = objectUrl;
        updateFavicon(objectUrl);
    }
};

const triggerLogoInput = () => {
    if (logoInputRef.value) logoInputRef.value.click();
};

const resetToDefaultLogo = () => {
    form.logo = null;
    form.reset_logo = true;
    logoPreview.value = '/images/logo-default.png';
    updateFavicon('/images/logo-default.png');
    if (logoInputRef.value) logoInputRef.value.value = '';
};

const previewWhatsAppUrl = computed(() => {
    const raw = form.helpdesk_contact?.trim();
    if (!raw) return null;
    if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;
    let digits = raw.replace(/\D/g, '');
    if (digits.startsWith('0')) {
        digits = '62' + digits.substring(1);
    }
    if (!digits) return null;
    return `https://wa.me/${digits}?text=${encodeURIComponent('Halo Panitia Pemira, ini pesan uji coba kontak helpdesk WhatsApp.')}`;
});

const submit = () => {
    form.post(route('admin.settings.update'), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Pemilihan" />

    <AuthenticatedLayout>
        <template #title>
            Pengaturan Sistem Pemira
        </template>

        <template #actions>
            <button
                type="button"
                @click="submit"
                :disabled="form.processing"
                class="bg-slate-900 hover:bg-slate-800 disabled:bg-slate-400 text-white text-xs font-semibold py-2 px-3.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-xs"
            >
                <Save class="w-3.5 h-3.5" />
                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
            </button>
        </template>

        <!-- Tata Letak 2 Kolom Seimbang -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Kolom Kiri (Formulir Pengaturan): Span 8 Kolom -->
            <div class="lg:col-span-8">
                <form @submit.prevent="submit" class="space-y-6 text-xs">
                    <!-- Bagian 1: Identitas Lembaga & Logo -->
                    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs space-y-5">
                        <div class="border-b border-slate-100 pb-3 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-bold text-slate-950">Identitas Lembaga & Logo Resmi</h2>
                                <p class="text-xs text-slate-700 font-medium">Logo dan nama lembaga ditampilkan di sidebar kontrol admin, header mobile, dan seluruh portal pemilih.</p>
                            </div>
                        </div>

                        <!-- Upload Logo & Pratinjau Tampilan Sidebar -->
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-center p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <!-- Kiri: Area Upload Logo -->
                            <div class="sm:col-span-7 space-y-2.5">
                                <label class="block font-bold text-slate-900 text-xs">
                                    Logo Lembaga / Organisasi
                                </label>
                                <div class="flex items-center gap-3.5">
                                    <div class="w-16 h-16 rounded-xl bg-white border border-slate-300 p-2 flex items-center justify-center shrink-0 shadow-2xs">
                                        <img
                                            :src="logoPreview"
                                            alt="Pratinjau Logo"
                                            class="w-full h-full object-contain"
                                        />
                                    </div>
                                    <div class="space-y-1.5 flex-1">
                                        <input
                                            ref="logoInputRef"
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                            @change="onLogoSelected"
                                            class="hidden"
                                        />
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <button
                                                type="button"
                                                @click="triggerLogoInput"
                                                class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-lg transition-colors flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                            >
                                                <Upload class="w-3.5 h-3.5" />
                                                <span>Pilih Berkas Logo</span>
                                            </button>
                                            <button
                                                v-if="form.logo || props.setting.logo_path"
                                                type="button"
                                                @click="resetToDefaultLogo"
                                                class="px-2.5 py-1.5 border border-slate-300 hover:bg-slate-200 text-slate-800 text-xs font-semibold rounded-lg transition-colors flex items-center gap-1 cursor-pointer"
                                                title="Kembalikan ke logo default"
                                            >
                                                <RotateCcw class="w-3.5 h-3.5" />
                                                <span>Reset Default</span>
                                            </button>
                                        </div>
                                        <div class="text-[11px] text-slate-700 font-medium">
                                            Format: PNG, JPG, WEBP, SVG (Maks. 2MB).
                                        </div>
                                    </div>
                                </div>
                                <div v-if="form.errors.logo" class="text-rose-600 text-xs font-semibold">{{ form.errors.logo }}</div>
                            </div>

                            <!-- Kanan: Pratinjau Tampilan Header/Sidebar Sesuai Screenshot User -->
                            <div class="sm:col-span-5 bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs space-y-1.5">
                                <div class="text-[10px] uppercase font-bold tracking-wider text-slate-700">
                                    Pratinjau Tampilan Sidebar
                                </div>
                                <div class="flex items-center gap-3 p-2 bg-slate-50 rounded-lg border border-slate-200">
                                    <img
                                        :src="logoPreview"
                                        alt="Logo"
                                        class="w-10 h-10 object-contain shrink-0"
                                    />
                                    <div class="overflow-hidden">
                                        <div class="font-bold text-sm text-slate-950 tracking-tight leading-tight truncate">
                                            {{ form.organization_name || 'DEMA Polkesmar' }}
                                        </div>
                                        <div class="text-xs text-slate-800 font-semibold leading-tight truncate">
                                            Panel Kontrol Pemira
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 pt-1">
                            <div>
                                <label class="block font-bold text-slate-900 mb-1">
                                    Nama Lembaga Penyelenggara (Sidebar & Header Admin) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    v-model="form.organization_name"
                                    required
                                    placeholder="Contoh: DEMA Polkesmar atau KPUM"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-slate-950 font-semibold focus:border-slate-900 focus:outline-none"
                                />
                                <p class="text-[11px] text-slate-700 font-medium mt-1">Nama ini langsung tampil di sudut kiri atas sidebar kontrol dan kop surat laporan.</p>
                                <div v-if="form.errors.organization_name" class="text-rose-600 text-xs font-semibold mt-1">{{ form.errors.organization_name }}</div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-900 mb-1">
                                    Nama Kegiatan Pemilihan <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    v-model="form.title"
                                    required
                                    placeholder="Contoh: Pemilihan Raya Mahasiswa 2026/2027"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-slate-950 font-semibold focus:border-slate-900 focus:outline-none"
                                />
                                <div v-if="form.errors.title" class="text-rose-600 text-xs font-semibold mt-1">{{ form.errors.title }}</div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-900 mb-1">
                                    Teks Pengumuman Resmi di Halaman Depan
                                </label>
                                <textarea
                                    v-model="form.announcement"
                                    rows="3"
                                    placeholder="Tuliskan pengumuman atau himbauan resmi kepada pemilih..."
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-slate-950 font-medium focus:border-slate-900 focus:outline-none"
                                ></textarea>
                                <p class="text-[11px] text-slate-700 font-medium mt-1">Pesan ini akan dibaca oleh mahasiswa di halaman masuk bilik suara.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 2: Layanan Bantuan & WhatsApp Helpdesk DPT -->
                    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-bold text-slate-950 flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-800 flex items-center justify-center">
                                        <MessageSquare class="w-3.5 h-3.5" />
                                    </div>
                                    <span>Pusat Bantuan & WhatsApp Pengaduan DPT</span>
                                </h2>
                                <p class="text-xs text-slate-700 font-medium mt-1">
                                    Nomor ini otomatis terhubung ke tombol <strong>"Kirim Pesan ke WhatsApp Panitia"</strong> pada formulir registrasi jika mahasiswa menemukan data DPT (nama/jurusan) yang tidak sesuai.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block font-bold text-slate-900 mb-1">
                                    Nomor WhatsApp Panitia / Admin Helpdesk
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <Phone class="w-4 h-4 text-emerald-600" />
                                    </div>
                                    <input
                                        type="text"
                                        v-model="form.helpdesk_contact"
                                        placeholder="Contoh: 081234567890 atau 6281234567890"
                                        class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-slate-950 font-semibold focus:border-slate-900 focus:outline-none"
                                    />
                                </div>
                                <p class="text-[11px] text-slate-700 font-medium mt-1.5">
                                    Bisa diisi nomor biasa (awalan 08... atau 628...) maupun tautan lengkap wa.me. Sistem akan otomatis memformat tautan chat langsung beserta rincian data diri mahasiswa.
                                </p>
                                <div v-if="form.errors.helpdesk_contact" class="text-rose-600 text-xs font-semibold mt-1">{{ form.errors.helpdesk_contact }}</div>
                            </div>

                            <!-- Pratinjau Tautan WhatsApp Langsung -->
                            <div v-if="previewWhatsAppUrl" class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-2 text-emerald-950 font-medium min-w-0">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span class="truncate">Nomor WhatsApp terhubung: <strong class="font-bold">{{ form.helpdesk_contact }}</strong></span>
                                </div>
                                <a
                                    :href="previewWhatsAppUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="px-2.5 py-1 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded text-[11px] flex items-center gap-1 shrink-0 transition-colors shadow-2xs"
                                >
                                    <span>Tes Tautan WA</span>
                                    <ExternalLink class="w-3 h-3" />
                                </a>
                            </div>

                            <div v-else class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-900 font-medium">
                                Saat ini nomor WhatsApp belum diatur. Pemilih yang mengalami kendala DPT akan diminta menyalin format pesan secara manual.
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 2: Jadwal Buka & Tutup Bilik Suara -->
                    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-bold text-slate-950">Jadwal Operasional Bilik Suara</h2>
                                <p class="text-xs text-slate-700 font-medium">Sistem akan memvalidasi waktu saat mahasiswa login ke bilik suara.</p>
                            </div>
                            <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                                <Clock class="w-4 h-4" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-900 mb-1">
                                    Waktu Buka Bilik Suara
                                </label>
                                <input
                                    type="datetime-local"
                                    v-model="form.start_time"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-slate-950 font-medium focus:border-slate-900 focus:outline-none"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-slate-900 mb-1">
                                    Waktu Tutup Bilik Suara
                                </label>
                                <input
                                    type="datetime-local"
                                    v-model="form.end_time"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-slate-950 font-medium focus:border-slate-900 focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 3: Kontrol Aktivasi & Quick Count -->
                    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-bold text-slate-950">Kontrol Visibilitas & Darurat</h2>
                            <p class="text-xs text-slate-700 font-medium">Saklar manual untuk situasi khusus atau penutupan mendadak.</p>
                        </div>

                        <div class="space-y-3">
                            <!-- Switch Bilik Suara Aktif -->
                            <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50">
                                <div class="space-y-0.5">
                                    <div class="font-bold text-slate-950 flex items-center gap-2">
                                        <ShieldCheck class="w-4 h-4 text-emerald-600" />
                                        <span>Status Bilik Suara Digital</span>
                                    </div>
                                    <div class="text-[11px] text-slate-700 font-medium">
                                        Jika dimatikan, mahasiswa tidak dapat masuk ke bilik suara meskipun jadwal pemilihan aktif.
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>

                            <!-- Switch Quick Count Publik -->
                            <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50">
                                <div class="space-y-0.5">
                                    <div class="font-bold text-slate-950 flex items-center gap-2">
                                        <BarChart3 class="w-4 h-4 text-slate-900" />
                                        <span>Akses Layar Quick Count Publik</span>
                                    </div>
                                    <div class="text-[11px] text-slate-700 font-medium">
                                        Menampilkan tombol dan grafik perolehan suara sementara di halaman portal pemilih.
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                    <input type="checkbox" v-model="form.show_quick_count" class="sr-only peer" />
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 disabled:bg-slate-400 text-white font-semibold rounded-lg transition-colors flex items-center gap-2 shadow-xs"
                        >
                            <Save class="w-4 h-4" />
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Seluruh Pengaturan' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Kolom Kanan (Informasi Status & Panduan Sistem): Span 4 Kolom -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Kartu Status Pemilihan Langsung -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-700">Status Saat Ini</div>
                        <h3 class="text-sm font-bold text-slate-950 mt-0.5">Ringkasan Konfigurasi</h3>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="text-slate-800 font-semibold">Bilik Suara:</span>
                            <span
                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-bold"
                                :class="form.is_active ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-slate-200 text-slate-800 border border-slate-300'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="form.is_active ? 'bg-emerald-600' : 'bg-slate-600'"></span>
                                <span>{{ form.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="text-slate-800 font-semibold">Layar Quick Count:</span>
                            <span
                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-bold"
                                :class="form.show_quick_count ? 'bg-blue-100 text-blue-900 border border-blue-300' : 'bg-slate-200 text-slate-800 border border-slate-300'"
                            >
                                <span>{{ form.show_quick_count ? 'Dibuka Publik' : 'Disembunyikan' }}</span>
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="text-slate-800 font-semibold">WhatsApp DPT:</span>
                            <span
                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-bold max-w-[140px] truncate"
                                :class="form.helpdesk_contact ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-amber-100 text-amber-900 border border-amber-300'"
                            >
                                <span class="truncate">{{ form.helpdesk_contact ? form.helpdesk_contact : 'Belum Diatur' }}</span>
                            </span>
                        </div>

                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 space-y-1">
                            <div class="text-[11px] text-slate-700 font-medium">Rentang Waktu Terdaftar:</div>
                            <div class="font-mono text-[11px] font-bold text-slate-950">
                                {{ form.start_time || 'Tidak diatur' }} s/d {{ form.end_time || 'Tidak diatur' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kartu Pintasan Pratinjau -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3">
                    <div class="text-xs font-bold text-slate-950">Pratinjau Langsung Portal</div>
                    <p class="text-xs text-slate-700 font-medium leading-relaxed">
                        Lihat bagaimana pemilih melihat tampilan portal dan grafik hasil suara.
                    </p>

                    <div class="space-y-2 pt-1 text-xs">
                        <Link
                            :href="route('home')"
                            target="_blank"
                            class="w-full flex items-center justify-between p-2.5 rounded-lg border border-slate-300 hover:border-slate-400 text-slate-800 font-semibold hover:text-slate-950 hover:bg-slate-50 transition-colors"
                        >
                            <span>Buka Portal Pemilih</span>
                            <ExternalLink class="w-3.5 h-3.5 text-slate-600" />
                        </Link>

                        <Link
                            :href="route('quick_count.show')"
                            target="_blank"
                            class="w-full flex items-center justify-between p-2.5 rounded-lg border border-slate-300 hover:border-slate-400 text-slate-800 font-semibold hover:text-slate-950 hover:bg-slate-50 transition-colors"
                        >
                            <span>Buka Layar Quick Count</span>
                            <ExternalLink class="w-3.5 h-3.5 text-slate-600" />
                        </Link>
                    </div>
                </div>

                <!-- Prinsip Keamanan & Integritas -->
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-2 text-xs text-slate-800">
                    <div class="font-bold text-slate-950 flex items-center gap-1.5">
                        <Lock class="w-3.5 h-3.5 text-slate-800" />
                        <span>Integritas Bilik Suara</span>
                    </div>
                    <p class="leading-relaxed text-[11px] text-slate-700 font-medium">
                        Perubahan jadwal atau penutupan darurat bilik suara berlaku seketika pada semua sesi pemilih baru tanpa mengganggu suara yang sudah masuk.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
