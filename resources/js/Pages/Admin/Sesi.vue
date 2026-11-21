<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Clock,
    Plus,
    Edit2,
    Trash2,
    CheckCircle2,
    AlertCircle,
    Power,
    Users,
    Vote,
    Calendar,
    Radio
} from 'lucide-vue-next';

const props = defineProps({
    sessions: Array,
    faculties: Array,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingSessionId = ref(null);
const deleteTarget = ref(null);

const form = useForm({
    name: '',
    target_faculty: '',
    start_time: '',
    end_time: '',
    is_active: true,
    description: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingSessionId.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    isModalOpen.value = true;
};

const openEditModal = (session) => {
    isEditing.value = true;
    editingSessionId.value = session.id;
    form.clearErrors();
    form.name = session.name;
    form.target_faculty = session.target_faculty || '';
    form.start_time = session.start_time;
    form.end_time = session.end_time;
    form.is_active = session.is_active;
    form.description = session.description || '';
    isModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.sessions.update', editingSessionId.value), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route('admin.sessions.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
};

const toggleStatus = (session) => {
    const toggleForm = useForm({});
    toggleForm.post(route('admin.sessions.toggle', session.id), {
        preserveScroll: true,
    });
};

const confirmDelete = (session) => {
    deleteTarget.value = session;
};

const executeDelete = () => {
    if (!deleteTarget.value) return;
    const deleteForm = useForm({});
    deleteForm.delete(route('admin.sessions.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};
</script>

<template>
    <Head title="Manajemen Sesi Pemilihan" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-950 flex items-center gap-2">
                        <Clock class="w-5 h-5 text-slate-900" />
                        <span>Sesi Pemilihan (Voting Sessions)</span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Atur jadwal gelombang bilik suara per jurusan atau sesi umum untuk menjaga kelancaran dan ketertiban pemilihan.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs py-2 px-3.5 rounded-md transition-colors focus:ring-2 focus:ring-slate-900 focus:outline-none"
                >
                    <Plus class="w-4 h-4" />
                    <span>Tambah Sesi Baru</span>
                </button>
            </div>

            <!-- Tabel Daftar Sesi -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-950">Daftar Sesi Pemilihan</h2>
                        <p class="text-xs text-slate-700 font-medium">
                            Total {{ sessions.length }} sesi terdaftar dalam sistem.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-900 font-bold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3 px-4">Nama Sesi & Keterangan</th>
                                <th class="py-3 px-4">Target Jurusan</th>
                                <th class="py-3 px-4">Jadwal Sesi (WIB)</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Suara</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            <tr
                                v-for="s in sessions"
                                :key="s.id"
                                class="hover:bg-slate-50/50 transition-colors"
                            >
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-950 text-sm flex items-center gap-2">
                                        <span>{{ s.name }}</span>
                                        <span
                                            v-if="s.is_open_now"
                                            class="inline-flex items-center gap-1 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                            Aktif Sekarang
                                        </span>
                                    </div>
                                    <div v-if="s.description" class="text-[11px] text-slate-700 font-medium mt-0.5">
                                        {{ s.description }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span
                                        v-if="s.target_faculty"
                                        class="inline-block px-2 py-0.5 rounded font-bold text-xs bg-slate-100 text-slate-900 border border-slate-300"
                                    >
                                        {{ s.target_faculty }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-block px-2 py-0.5 rounded font-bold text-xs bg-emerald-100 text-emerald-950 border border-emerald-300"
                                    >
                                        Semua Jurusan (Umum)
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-800 font-semibold">
                                    <div>{{ s.start_time_formatted }}</div>
                                    <div class="text-slate-600 font-medium">s/d {{ s.end_time_formatted }}</div>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="!s.is_active"
                                        class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-slate-200 text-slate-800 border border-slate-300"
                                    >
                                        Nonaktif
                                    </span>
                                    <span
                                        v-else-if="s.status_label === 'running'"
                                        class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300"
                                    >
                                        Berlangsung
                                    </span>
                                    <span
                                        v-else-if="s.status_label === 'upcoming'"
                                        class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300"
                                    >
                                        Akan Datang
                                    </span>
                                    <span
                                        v-else
                                        class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-slate-200 text-slate-800 border border-slate-300"
                                    >
                                        Selesai
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-950">
                                    {{ s.votes_count }}
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Tombol Toggle Aktif/Nonaktif -->
                                        <button
                                            type="button"
                                            @click="toggleStatus(s)"
                                            class="p-1.5 rounded transition-colors"
                                            :class="s.is_active ? 'text-emerald-700 hover:bg-emerald-50' : 'text-slate-400 hover:bg-slate-100'"
                                            :title="s.is_active ? 'Nonaktifkan Sesi' : 'Aktifkan Sesi'"
                                        >
                                            <Power class="w-4 h-4" />
                                        </button>

                                        <!-- Tombol Edit -->
                                        <button
                                            type="button"
                                            @click="openEditModal(s)"
                                            class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded transition-colors"
                                            title="Edit Sesi"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>

                                        <!-- Tombol Hapus -->
                                        <button
                                            type="button"
                                            @click="confirmDelete(s)"
                                            class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded transition-colors"
                                            title="Hapus Sesi"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="sessions.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    Belum ada sesi pemilihan yang dibuat. Klik tombol "Tambah Sesi Baru" untuk memulai.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Sesi -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-xs"
            @keydown.esc="isModalOpen = false"
        >
            <div class="bg-white rounded-xl border border-slate-200 max-w-lg w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-950">
                        {{ isEditing ? 'Edit Sesi Pemilihan' : 'Tambah Sesi Pemilihan Baru' }}
                    </h3>
                    <button
                        type="button"
                        @click="isModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 text-lg leading-none"
                    >
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Nama Sesi Pemilihan <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Contoh: Sesi 1 - Pagi (Jurusan Keperawatan & Kebidanan)"
                            class="w-full rounded-md border-slate-300 text-xs focus:border-slate-900 focus:ring-slate-900"
                        />
                        <div v-if="form.errors.name" class="text-rose-600 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Target Jurusan / Prodi
                        </label>
                        <select
                            v-model="form.target_faculty"
                            class="w-full rounded-md border-slate-300 text-xs focus:border-slate-900 focus:ring-slate-900"
                        >
                            <option value="">Semua Jurusan (Sesi Umum Terbuka)</option>
                            <option v-for="f in faculties" :key="f" :value="f">
                                Khusus {{ f }}
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Pilih "Semua Jurusan" jika sesi ini terbuka untuk semua pemilih DPT.
                        </p>
                        <div v-if="form.errors.target_faculty" class="text-rose-600 mt-1">{{ form.errors.target_faculty }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">
                                Waktu Mulai Sesi <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.start_time"
                                type="datetime-local"
                                required
                                class="w-full rounded-md border-slate-300 text-xs focus:border-slate-900 focus:ring-slate-900"
                            />
                            <div v-if="form.errors.start_time" class="text-rose-600 mt-1">{{ form.errors.start_time }}</div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">
                                Waktu Selesai Sesi <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.end_time"
                                type="datetime-local"
                                required
                                class="w-full rounded-md border-slate-300 text-xs focus:border-slate-900 focus:ring-slate-900"
                            />
                            <div v-if="form.errors.end_time" class="text-rose-600 mt-1">{{ form.errors.end_time }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Keterangan Tambahan (Opsional)
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Catatan pelaksanaan, instruksi kehadiran, atau informasi lokasi..."
                            class="w-full rounded-md border-slate-300 text-xs focus:border-slate-900 focus:ring-slate-900"
                        ></textarea>
                        <div v-if="form.errors.description" class="text-rose-600 mt-1">{{ form.errors.description }}</div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            v-model="form.is_active"
                            id="is_active_checkbox"
                            type="checkbox"
                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                        />
                        <label for="is_active_checkbox" class="text-xs text-slate-700 font-medium">
                            Aktifkan sesi pemilihan ini
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-3.5 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-md transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 disabled:bg-slate-400 text-white font-medium rounded-md transition-colors"
                        >
                            {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Perbarui Sesi' : 'Simpan Sesi') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <div
            v-if="deleteTarget"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-xs"
        >
            <div class="bg-white rounded-xl border border-slate-200 max-w-sm w-full p-5 shadow-xl space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-700 shrink-0">
                        <AlertCircle class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-950">Hapus Sesi Pemilihan?</h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Apakah Anda yakin ingin menghapus sesi <strong>{{ deleteTarget.name }}</strong>? Data riwayat sesi ini akan dibersihkan.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="deleteTarget = null"
                        class="px-3 py-1.5 border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium text-xs rounded-md transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-md transition-colors"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
