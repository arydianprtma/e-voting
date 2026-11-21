<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    GraduationCap,
    Plus,
    Edit2,
    Trash2,
    CheckCircle2,
    AlertCircle,
    Power,
    Search,
    Building2
} from 'lucide-vue-next';

const props = defineProps({
    departments: Array,
    existingFaculties: Array,
    stats: Object,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const deleteTarget = ref(null);
const selectedFacultyFilter = ref('all');
const searchQuery = ref('');
const useNewFaculty = ref(false);

const form = useForm({
    faculty_name: '',
    study_program: '',
    is_active: true,
});

const filteredDepartments = computed(() => {
    return props.departments.filter((d) => {
        const matchFaculty = selectedFacultyFilter.value === 'all' || d.faculty_name === selectedFacultyFilter.value;
        const matchSearch = searchQuery.value === '' ||
            d.study_program.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            d.faculty_name.toLowerCase().includes(searchQuery.value.toLowerCase());
        return matchFaculty && matchSearch;
    });
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    useNewFaculty.value = false;
    if (props.existingFaculties.length > 0) {
        form.faculty_name = props.existingFaculties[0];
    }
    isModalOpen.value = true;
};

const openEditModal = (dept) => {
    isEditing.value = true;
    editingId.value = dept.id;
    form.clearErrors();
    form.faculty_name = dept.faculty_name;
    form.study_program = dept.study_program;
    form.is_active = dept.is_active;
    useNewFaculty.value = false;
    isModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.departments.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route('admin.departments.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
};

const toggleStatus = (dept) => {
    const toggleForm = useForm({});
    toggleForm.post(route('admin.departments.toggle', dept.id), {
        preserveScroll: true,
    });
};

const confirmDelete = (dept) => {
    deleteTarget.value = dept;
};

const executeDelete = () => {
    if (!deleteTarget.value) return;
    const deleteForm = useForm({});
    deleteForm.delete(route('admin.departments.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};
</script>

<template>
    <Head title="Manajemen Jurusan & Program Studi" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-950 flex items-center gap-2">
                        <GraduationCap class="w-5 h-5 text-slate-900" />
                        <span>Master Data Jurusan & Program Studi</span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Kelola daftar resmi Jurusan dan Program Studi yang menjadi rujukan pendaftaran pemilih dan penetapan DPT.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs py-2 px-3.5 rounded-md transition-colors focus:ring-2 focus:ring-slate-900 focus:outline-none"
                >
                    <Plus class="w-4 h-4" />
                    <span>Tambah Program Studi</span>
                </button>
            </div>

            <!-- Kartu Statistik Singkat -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs">
                    <div class="text-xs font-bold text-slate-800 uppercase tracking-wider">Total Jurusan</div>
                    <div class="text-2xl font-black font-mono text-slate-950 mt-1">
                        {{ stats.total_faculties }}
                    </div>
                    <div class="text-[11px] text-slate-700 font-medium mt-0.5">Departemen/Fakultas Terdaftar</div>
                </div>

                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs">
                    <div class="text-xs font-bold text-slate-800 uppercase tracking-wider">Total Program Studi</div>
                    <div class="text-2xl font-black font-mono text-slate-950 mt-1">
                        {{ stats.total_programs }}
                    </div>
                    <div class="text-[11px] text-slate-700 font-medium mt-0.5">Jenjang D3 & Sarjana Terapan</div>
                </div>

                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs">
                    <div class="text-xs font-bold text-slate-800 uppercase tracking-wider">Status Aktif</div>
                    <div class="text-2xl font-black font-mono text-emerald-800 mt-1">
                        {{ stats.active_count }}
                    </div>
                    <div class="text-[11px] text-slate-700 font-medium mt-0.5">Dapat dipilih pada registrasi pemilih</div>
                </div>
            </div>

            <!-- Filter & Tabel -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                <!-- Baris Filter & Pencarian -->
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="w-full sm:w-72 relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari jurusan atau prodi..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs rounded-md border-slate-300 text-slate-950 font-medium focus:border-slate-900 focus:ring-slate-900"
                        />
                        <Search class="w-4 h-4 text-slate-600 absolute left-2.5 top-2" />
                    </div>

                    <div class="w-full sm:w-auto flex items-center gap-2">
                        <label class="text-xs text-slate-800 shrink-0 font-bold">Filter Jurusan:</label>
                        <select
                            v-model="selectedFacultyFilter"
                            class="text-xs rounded-md border-slate-300 text-slate-950 font-semibold py-1.5 focus:border-slate-900 focus:ring-slate-900"
                        >
                            <option value="all">Semua Jurusan</option>
                            <option v-for="fac in existingFaculties" :key="fac" :value="fac">
                                {{ fac }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-900 font-bold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3 px-4">Nama Jurusan</th>
                                <th class="py-3 px-4">Program Studi</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            <tr
                                v-for="dept in filteredDepartments"
                                :key="dept.id"
                                class="hover:bg-slate-50/50 transition-colors"
                            >
                                <td class="py-3.5 px-4 font-bold text-slate-950">
                                    {{ dept.faculty_name }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    {{ dept.study_program }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        class="inline-block px-2 py-0.5 rounded text-[11px] font-bold"
                                        :class="dept.is_active ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-slate-200 text-slate-800 border border-slate-300'"
                                    >
                                        {{ dept.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="toggleStatus(dept)"
                                            class="p-1.5 rounded transition-colors"
                                            :class="dept.is_active ? 'text-emerald-700 hover:bg-emerald-50' : 'text-slate-600 hover:bg-slate-100'"
                                            :title="dept.is_active ? 'Nonaktifkan Program Studi' : 'Aktifkan Program Studi'"
                                        >
                                            <Power class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openEditModal(dept)"
                                            class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded transition-colors"
                                            title="Edit Program Studi"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(dept)"
                                            class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded transition-colors"
                                            title="Hapus Program Studi"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredDepartments.length === 0">
                                <td colspan="4" class="py-12 text-center text-slate-500">
                                    Tidak ada data Jurusan atau Program Studi yang cocok dengan pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Jurusan & Prodi -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-xs"
            @keydown.esc="isModalOpen = false"
        >
            <div class="bg-white rounded-xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-950">
                        {{ isEditing ? 'Edit Program Studi' : 'Tambah Jurusan & Program Studi' }}
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
                    <!-- Pilihan Jurusan -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-semibold text-slate-700">
                                Nama Jurusan <span class="text-rose-500">*</span>
                            </label>
                            <button
                                v-if="!isEditing"
                                type="button"
                                @click="useNewFaculty = !useNewFaculty"
                                class="text-[11px] text-slate-600 hover:text-slate-900 underline"
                            >
                                {{ useNewFaculty ? 'Pilih Jurusan yang Ada' : '+ Ketik Jurusan Baru' }}
                            </button>
                        </div>

                        <!-- Dropdown Jurusan yang Ada -->
                        <select
                            v-if="!useNewFaculty"
                            v-model="form.faculty_name"
                            required
                            class="w-full rounded-md border-slate-300 text-xs focus:border-slate-900 focus:ring-slate-900"
                        >
                            <option value="" disabled>Pilih Jurusan</option>
                            <option v-for="fac in existingFaculties" :key="fac" :value="fac">
                                {{ fac }}
                            </option>
                        </select>

                        <!-- Input Teks Jurusan Baru -->
                        <input
                            v-else
                            v-model="form.faculty_name"
                            type="text"
                            required
                            placeholder="Contoh: Jurusan Rekam Medis"
                            class="w-full rounded-md border-slate-300 text-xs focus:border-slate-900 focus:ring-slate-900"
                        />
                        <div v-if="form.errors.faculty_name" class="text-rose-600 mt-1">{{ form.errors.faculty_name }}</div>
                    </div>

                    <!-- Input Nama Program Studi -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Nama Program Studi <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.study_program"
                            type="text"
                            required
                            placeholder="Contoh: D3 Keperawatan Semarang"
                            class="w-full rounded-md border-slate-300 text-xs focus:border-slate-900 focus:ring-slate-900"
                        />
                        <div v-if="form.errors.study_program" class="text-rose-600 mt-1">{{ form.errors.study_program }}</div>
                    </div>

                    <!-- Checkbox Status Aktif -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            v-model="form.is_active"
                            id="dept_active_checkbox"
                            type="checkbox"
                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                        />
                        <label for="dept_active_checkbox" class="text-xs text-slate-700 font-medium">
                            Aktifkan program studi ini untuk pendaftaran pemilih
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
                            {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Perbarui Prodi' : 'Simpan Prodi') }}
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
                        <h3 class="text-sm font-bold text-slate-950">Hapus Program Studi?</h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Apakah Anda yakin ingin menghapus <strong>{{ deleteTarget.study_program }}</strong> ({{ deleteTarget.faculty_name }})?
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
