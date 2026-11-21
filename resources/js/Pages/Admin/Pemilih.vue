<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FolderPaperAnimation from '@/Components/FolderPaperAnimation.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import {
    Plus,
    Search,
    CheckCircle2,
    Clock,
    X,
    Filter,
    RotateCw,
    UserCheck,
    AlertTriangle,
    ShieldAlert,
    Trash2,
    Pencil,
    FileUp,
    FileSpreadsheet,
    FileText,
    Upload,
    ChevronLeft,
    ChevronRight,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    RotateCcw,
    Loader2
} from 'lucide-vue-next';

const props = defineProps({
    voters: Object,
    filters: Object,
    stats: Object,
    faculties: Array,
    studyPrograms: Array,
    departmentsGrouped: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const faculty = ref(props.filters.faculty || 'all');
const studyProgram = ref(props.filters.study_program || 'all');
const sortBy = ref(props.filters.sort_by || 'student_id');
const sortOrder = ref(props.filters.sort_order || 'asc');
const isAddModalOpen = ref(false);

// State untuk Edit Pemilih
const isEditModalOpen = ref(false);
const editingVoter = ref(null);
const editForm = useForm({
    student_id: '',
    name: '',
    faculty: '',
    study_program: '',
    birth_date: '',
});

// State untuk Modal Hapus Satuan
const isDeleteModalOpen = ref(false);
const deletingVoter = ref(null);
const isDeleting = ref(false);

// State untuk Seleksi dan Hapus Massal
const selectedVoterIds = ref([]);
const isSelectAllAcrossPages = ref(false);
const isBulkDeleteModalOpen = ref(false);
const isBulkDeleting = ref(false);

// State untuk Modal Impor / Upload Berkas DPT
const isImportModalOpen = ref(false);
const isPreviewAnimation = ref(false);
const importMode = ref('file'); // 'file' atau 'paste'
const importFile = ref(null);
const fileInputRef = ref(null);
const importForm = useForm({
    file: null,
    raw_text: '',
    faculty: '',
    study_program: '',
});

const availableImportPrograms = computed(() => {
    if (!importForm.faculty || !props.departmentsGrouped) return [];
    return props.departmentsGrouped[importForm.faculty] || [];
});

const onFileSelected = (event) => {
    const file = event.target.files[0];
    if (file) {
        importFile.value = file;
        importForm.file = file;
    }
};

const triggerFileInput = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const removeSelectedFile = () => {
    importFile.value = null;
    importForm.file = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const submitImport = () => {
    importForm.post(route('admin.voters.import'), {
        forceFormData: true,
        onSuccess: () => {
            isImportModalOpen.value = false;
            removeSelectedFile();
            importForm.reset();
        },
    });
};

// State untuk Modal Hapus Semua DPT
const isDestroyAllModalOpen = ref(false);
const isDestroyingAll = ref(false);
const destroyAllForm = useForm({
    confirm_text: '',
    include_voted: false,
});

const openDestroyAllModal = () => {
    destroyAllForm.reset();
    isDestroyAllModalOpen.value = true;
};

const confirmDestroyAll = () => {
    if (destroyAllForm.confirm_text.trim().toUpperCase() !== 'HAPUS') return;
    isDestroyingAll.value = true;
    destroyAllForm.post(route('admin.voters.destroy_all'), {
        onFinish: () => {
            isDestroyingAll.value = false;
            isDestroyAllModalOpen.value = false;
            selectedVoterIds.value = [];
        },
    });
};

const addForm = useForm({
    student_id: '',
    name: '',
    faculty: '',
    study_program: '',
    birth_date: '',
});

const availableAddPrograms = computed(() => {
    if (!addForm.faculty || !props.departmentsGrouped) return [];
    return props.departmentsGrouped[addForm.faculty] || [];
});

const availableEditPrograms = computed(() => {
    if (!editForm.faculty || !props.departmentsGrouped) return [];
    return props.departmentsGrouped[editForm.faculty] || [];
});

// Hitung total data pemilih yang dapat diseleksi (berdasarkan status filter)
const totalSelectable = computed(() => {
    if (status.value === 'unvoted') return props.stats.unvoted;
    if (status.value === 'voted') return props.stats.voted;
    return props.stats.unvoted; // Hapus massal hanya untuk pemilih yang belum memilih
});

// Checkbox select all logic (hanya pemilih yang belum memilih)
const unvotedPageVoterIds = computed(() => {
    return (props.voters.data || [])
        .filter(v => !v.has_voted)
        .map(v => v.id);
});

const isAllSelected = computed(() => {
    const selectable = unvotedPageVoterIds.value;
    if (selectable.length === 0) return false;
    return isSelectAllAcrossPages.value || selectable.every(id => selectedVoterIds.value.includes(id));
});

const toggleSelectAll = () => {
    const selectable = unvotedPageVoterIds.value;
    if (isAllSelected.value) {
        selectedVoterIds.value = selectedVoterIds.value.filter(id => !selectable.includes(id));
        isSelectAllAcrossPages.value = false;
    } else {
        const merged = new Set([...selectedVoterIds.value, ...selectable]);
        selectedVoterIds.value = Array.from(merged);
    }
};

const cancelSelection = () => {
    selectedVoterIds.value = [];
    isSelectAllAcrossPages.value = false;
};

const selectAllVoters = () => {
    const selectable = unvotedPageVoterIds.value;
    const merged = new Set([...selectedVoterIds.value, ...selectable]);
    selectedVoterIds.value = Array.from(merged);
    isSelectAllAcrossPages.value = true;
};

const isSearching = ref(false);
let searchDebounceTimer = null;

const applyFilters = (immediate = false) => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }

    const execute = () => {
        isSearching.value = true;
        selectedVoterIds.value = [];
        isSelectAllAcrossPages.value = false;
        router.get(
            route('admin.voters'),
            {
                search: search.value,
                status: status.value,
                faculty: faculty.value,
                study_program: studyProgram.value,
                sort_by: sortBy.value,
                sort_order: sortOrder.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => {
                    isSearching.value = false;
                },
            }
        );
    };

    if (immediate) {
        execute();
    } else {
        isSearching.value = true;
        searchDebounceTimer = setTimeout(execute, 300);
    }
};

// Pantau perubahan kata kunci pencarian secara langsung (live search tanpa klik tombol)
watch(search, (newVal) => {
    if (newVal === (props.filters.search || '')) return;
    if (!newVal || newVal.trim() === '') {
        applyFilters(true);
    } else {
        applyFilters(false);
    }
});

// Sinkronisasi jika filter diperbarui dari server atau navigasi
watch(
    () => props.filters,
    (newFilters) => {
        if (!newFilters) return;
        if (newFilters.search !== undefined && newFilters.search !== search.value) {
            search.value = newFilters.search || '';
        }
        if (newFilters.status !== undefined && newFilters.status !== status.value) {
            status.value = newFilters.status || 'all';
        }
        if (newFilters.faculty !== undefined && newFilters.faculty !== faculty.value) {
            faculty.value = newFilters.faculty || 'all';
        }
        if (newFilters.study_program !== undefined && newFilters.study_program !== studyProgram.value) {
            studyProgram.value = newFilters.study_program || 'all';
        }
        if (newFilters.sort_by !== undefined && newFilters.sort_by !== sortBy.value) {
            sortBy.value = newFilters.sort_by || 'student_id';
        }
        if (newFilters.sort_order !== undefined && newFilters.sort_order !== sortOrder.value) {
            sortOrder.value = newFilters.sort_order || 'asc';
        }
    },
    { deep: true }
);

const toggleSort = (col) => {
    if (sortBy.value === col) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = col;
        sortOrder.value = 'asc';
    }
    applyFilters(true);
};

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    faculty.value = 'all';
    studyProgram.value = 'all';
    sortBy.value = 'student_id';
    sortOrder.value = 'asc';
    applyFilters(true);
};

const hasActiveFilters = computed(() => {
    return (
        search.value !== '' ||
        status.value !== 'all' ||
        faculty.value !== 'all' ||
        studyProgram.value !== 'all' ||
        sortBy.value !== 'student_id' ||
        sortOrder.value !== 'asc'
    );
});

const storeVoter = () => {
    addForm.post(route('admin.voters.store'), {
        onSuccess: () => {
            isAddModalOpen.value = false;
            addForm.reset();
        },
    });
};

const openEditModal = (voter) => {
    editingVoter.value = voter;
    const bDate = voter.birth_date ? String(voter.birth_date).substring(0, 10) : '';
    editForm.student_id = voter.student_id;
    editForm.name = voter.name;
    editForm.faculty = voter.faculty;
    editForm.study_program = voter.study_program || '';
    editForm.birth_date = bDate;
    isEditModalOpen.value = true;
};

const updateVoter = () => {
    if (!editingVoter.value) return;
    editForm.put(route('admin.voters.update', editingVoter.value.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingVoter.value = null;
            editForm.reset();
        },
    });
};

const openDeleteModal = (voter) => {
    deletingVoter.value = voter;
    isDeleteModalOpen.value = true;
};

const confirmDeleteVoter = () => {
    if (!deletingVoter.value) return;
    isDeleting.value = true;
    router.delete(route('admin.voters.destroy', deletingVoter.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            isDeleteModalOpen.value = false;
            selectedVoterIds.value = selectedVoterIds.value.filter(id => id !== deletingVoter.value.id);
            deletingVoter.value = null;
        },
    });
};

const openBulkDeleteModal = () => {
    if (selectedVoterIds.value.length === 0 && !isSelectAllAcrossPages.value) return;
    isBulkDeleteModalOpen.value = true;
};

const confirmBulkDelete = () => {
    if (selectedVoterIds.value.length === 0 && !isSelectAllAcrossPages.value) return;
    isBulkDeleting.value = true;
    router.post(
        route('admin.voters.bulk_destroy'),
        {
            select_all: isSelectAllAcrossPages.value,
            ids: isSelectAllAcrossPages.value ? [] : selectedVoterIds.value,
            search: search.value,
            status: status.value,
            faculty: faculty.value,
            study_program: studyProgram.value,
        },
        {
            onFinish: () => {
                isBulkDeleting.value = false;
                isBulkDeleteModalOpen.value = false;
                selectedVoterIds.value = [];
                isSelectAllAcrossPages.value = false;
            },
        }
    );
};
</script>

<template>
    <Head title="Manajemen DPT (Daftar Pemilih Tetap)" />

    <AuthenticatedLayout>
        <template #title>
            Daftar Pemilih Tetap (DPT)
        </template>

        <template #actions>
            <div class="flex items-center gap-2 print:hidden">
                <button
                    type="button"
                    @click="isImportModalOpen = true"
                    class="bg-white border border-slate-300 hover:border-slate-400 text-slate-800 text-xs font-semibold py-1.5 px-3 rounded-lg flex items-center gap-1.5 transition-colors shadow-2xs"
                >
                    <FileUp class="w-3.5 h-3.5 text-slate-700" />
                    <span>Upload Berkas DPT</span>
                </button>

                <button
                    v-if="stats.total > 0"
                    type="button"
                    @click="openDestroyAllModal"
                    class="border border-rose-200 hover:border-rose-300 hover:bg-rose-50 text-rose-700 text-xs font-semibold py-1.5 px-3 rounded-lg flex items-center gap-1.5 transition-colors bg-white shadow-2xs"
                    title="Kosongkan seluruh data pemilih tetap"
                >
                    <Trash2 class="w-3.5 h-3.5 text-rose-600" />
                    <span>Hapus Semua DPT</span>
                </button>

                <button
                    type="button"
                    @click="isAddModalOpen = true"
                    class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold py-1.5 px-3.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-xs"
                >
                    <Plus class="w-3.5 h-3.5" />
                    <span>Tambah Pemilih</span>
                </button>
            </div>
        </template>

        <div class="space-y-4">
            <!-- 3 Ringkasan Status Pemilih -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 print:hidden">
                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs flex items-center justify-between">
                    <div>
                        <div class="text-xs text-slate-800 font-bold">Total Terdaftar (DPT)</div>
                        <div class="text-xl font-black font-mono text-slate-950 mt-0.5">{{ stats.total }}</div>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                        <UserCheck class="w-4 h-4 stroke-[2.5]" />
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs flex items-center justify-between">
                    <div>
                        <div class="text-xs text-slate-800 font-bold">Sudah Memilih</div>
                        <div class="text-xl font-black font-mono text-emerald-800 mt-0.5">{{ stats.voted }}</div>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-800">
                        <CheckCircle2 class="w-4 h-4 stroke-[2.5]" />
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs flex items-center justify-between">
                    <div>
                        <div class="text-xs text-slate-800 font-bold">Belum Memilih</div>
                        <div class="text-xl font-black font-mono text-amber-800 mt-0.5">{{ stats.unvoted }}</div>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-800">
                        <Clock class="w-4 h-4 stroke-[2.5]" />
                    </div>
                </div>
            </div>

            <!-- Filter & Bar Pencarian Cerdas -->
            <div class="bg-white border border-slate-200 rounded-xl p-3.5 sm:p-4 shadow-xs space-y-3 print:hidden">
                <div class="flex flex-col lg:flex-row gap-2.5 items-stretch lg:items-center justify-between">
                    <!-- Pencarian Teks Otomatis (Live Search) -->
                    <div class="relative flex-1 min-w-[240px]">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 flex items-center pointer-events-none">
                            <Loader2 v-if="isSearching" class="w-4 h-4 text-blue-600 animate-spin" />
                            <Search v-else class="w-4 h-4 text-slate-400" />
                        </div>
                        <input
                            type="text"
                            v-model="search"
                            @keyup.enter="applyFilters(true)"
                            placeholder="Cari langsung NIM, Nama, Jurusan, atau Prodi..."
                            class="w-full text-xs pl-9 pr-8 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none transition-colors"
                        />
                        <button
                            v-if="search"
                            type="button"
                            @click="search = ''; applyFilters(true)"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer p-0.5"
                            title="Hapus kata kunci"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Dropdowns Filter: Jurusan, Program Studi, Status, Sorting -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Filter Jurusan / Fakultas -->
                        <select
                            v-model="faculty"
                            @change="applyFilters(true)"
                            class="text-xs px-3 py-2 border border-slate-300 rounded-lg bg-white focus:border-slate-900 focus:outline-none max-w-[180px] truncate cursor-pointer"
                            title="Filter Berdasarkan Jurusan / Fakultas"
                        >
                            <option value="all">Semua Jurusan</option>
                            <option v-for="f in faculties" :key="f" :value="f">
                                {{ f }}
                            </option>
                        </select>

                        <!-- Filter Program Studi jika tersedia -->
                        <select
                            v-if="studyPrograms && studyPrograms.length > 1"
                            v-model="studyProgram"
                            @change="applyFilters(true)"
                            class="text-xs px-3 py-2 border border-slate-300 rounded-lg bg-white focus:border-slate-900 focus:outline-none max-w-[200px] truncate cursor-pointer"
                            title="Filter Berdasarkan Program Studi"
                        >
                            <option value="all">Semua Program Studi</option>
                            <option v-for="sp in studyPrograms" :key="sp" :value="sp">
                                {{ sp }}
                            </option>
                        </select>

                        <!-- Filter Status Suara -->
                        <select
                            v-model="status"
                            @change="applyFilters(true)"
                            class="text-xs px-3 py-2 border border-slate-300 rounded-lg bg-white focus:border-slate-900 focus:outline-none cursor-pointer"
                        >
                            <option value="all">Semua Status DPT</option>
                            <option value="unvoted">Belum Memilih</option>
                            <option value="voted">Sudah Memilih</option>
                        </select>

                        <!-- Urutan Tampilan (Sorting) -->
                        <select
                            v-model="sortBy"
                            @change="applyFilters(true)"
                            class="text-xs px-3 py-2 border border-slate-300 rounded-lg bg-white focus:border-slate-900 focus:outline-none font-medium text-slate-800 cursor-pointer"
                            title="Pilih kolom pengurutan data"
                        >
                            <option value="student_id">Urutkan: NIM Mahasiswa</option>
                            <option value="name">Urutkan: Nama (A - Z)</option>
                            <option value="faculty">Urutkan: Jurusan</option>
                        </select>

                        <!-- Toggle Arah Urutan Asc / Desc -->
                        <button
                            type="button"
                            @click="sortOrder = (sortOrder === 'asc' ? 'desc' : 'asc'); applyFilters(true)"
                            class="px-2.5 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg transition-colors flex items-center gap-1 cursor-pointer"
                            :title="sortOrder === 'asc' ? 'Urutan Berurutan (Menaik / ASC)' : 'Urutan Terbalik (Menurun / DESC)'"
                        >
                            <ArrowUp v-if="sortOrder === 'asc'" class="w-3.5 h-3.5 text-blue-600" />
                            <ArrowDown v-else class="w-3.5 h-3.5 text-blue-600" />
                            <span class="text-[11px] font-mono">{{ sortOrder.toUpperCase() }}</span>
                        </button>

                        <!-- Tombol Reset Filter jika ada filter aktif -->
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="resetFilters"
                            class="px-2.5 py-2 border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-medium rounded-lg transition-colors flex items-center gap-1 cursor-pointer"
                            title="Reset semua filter ke susunan default"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            <span class="hidden sm:inline">Reset</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Selection / Bulk Action Bar -->
            <div
                v-if="selectedVoterIds.length > 0 || isSelectAllAcrossPages"
                class="bg-slate-900 text-white rounded-xl p-3 px-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs animate-in fade-in slide-in-from-top-1 print:hidden"
            >
                <div class="flex items-center gap-2 text-xs flex-wrap">
                    <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1 rounded-full bg-white text-slate-950 font-bold text-[11px]">
                        {{ isSelectAllAcrossPages ? totalSelectable : selectedVoterIds.length }}
                    </span>
                    <span v-if="!isSelectAllAcrossPages" class="font-medium text-slate-200">
                        data pemilih di halaman ini dipilih.
                    </span>
                    <span v-else class="font-medium text-slate-200">
                        seluruh data pemilih (semua halaman) telah dipilih.
                    </span>

                    <!-- Tombol Pilih Seluruh Data Jika Total > Data Halaman Ini -->
                    <button
                        v-if="!isSelectAllAcrossPages && totalSelectable > selectedVoterIds.length"
                        type="button"
                        @click="selectAllVoters"
                        class="text-amber-300 hover:text-amber-200 font-semibold underline underline-offset-2 transition-colors ml-1 cursor-pointer"
                    >
                        Pilih seluruh {{ totalSelectable }} data pemilih
                    </button>
                    <button
                        v-else-if="isSelectAllAcrossPages"
                        type="button"
                        @click="isSelectAllAcrossPages = false"
                        class="text-slate-400 hover:text-white underline underline-offset-2 transition-colors ml-1 cursor-pointer"
                    >
                        Kembali ke pilihan halaman ini saja
                    </button>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        @click="cancelSelection"
                        class="text-xs text-slate-300 hover:text-white px-2.5 py-1 rounded-lg border border-slate-700 hover:bg-slate-800 transition-colors"
                    >
                        Batal Pilih
                    </button>
                    <button
                        type="button"
                        @click="openBulkDeleteModal"
                        class="text-xs bg-red-600 hover:bg-red-500 text-white font-semibold px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 shadow-xs"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                        <span>Hapus Terpilih ({{ isSelectAllAcrossPages ? totalSelectable : selectedVoterIds.length }})</span>
                    </button>
                </div>
            </div>

            <!-- Tabel Data Pemilih Modern -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 border-b border-slate-300 text-slate-950 font-bold select-none text-xs">
                            <tr>
                                <th class="py-3.5 px-3 w-10 text-center print:hidden">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        @change="toggleSelectAll"
                                        :disabled="unvotedPageVoterIds.length === 0"
                                        class="rounded border-slate-400 text-slate-950 focus:ring-slate-950 cursor-pointer disabled:opacity-40"
                                        title="Pilih semua pemilih yang belum memilih di halaman ini"
                                    />
                                </th>
                                <th
                                    @click="toggleSort('student_id')"
                                    class="py-3.5 px-4 cursor-pointer hover:bg-slate-200 transition-colors group"
                                    title="Klik untuk mengurutkan berdasarkan NIM"
                                >
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="text-slate-950 font-bold">NIM</span>
                                        <ArrowUpDown v-if="sortBy !== 'student_id'" class="w-3.5 h-3.5 text-slate-500 opacity-70 group-hover:opacity-100" />
                                        <ArrowUp v-else-if="sortOrder === 'asc'" class="w-3.5 h-3.5 text-blue-700 stroke-[2.5]" />
                                        <ArrowDown v-else class="w-3.5 h-3.5 text-blue-700 stroke-[2.5]" />
                                    </div>
                                </th>
                                <th
                                    @click="toggleSort('name')"
                                    class="py-3.5 px-4 cursor-pointer hover:bg-slate-200 transition-colors group"
                                    title="Klik untuk mengurutkan berdasarkan Nama"
                                >
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="text-slate-950 font-bold">Nama Mahasiswa</span>
                                        <ArrowUpDown v-if="sortBy !== 'name'" class="w-3.5 h-3.5 text-slate-500 opacity-70 group-hover:opacity-100" />
                                        <ArrowUp v-else-if="sortOrder === 'asc'" class="w-3.5 h-3.5 text-blue-700 stroke-[2.5]" />
                                        <ArrowDown v-else class="w-3.5 h-3.5 text-blue-700 stroke-[2.5]" />
                                    </div>
                                </th>
                                <th
                                    @click="toggleSort('faculty')"
                                    class="py-3.5 px-4 cursor-pointer hover:bg-slate-200 transition-colors group"
                                    title="Klik untuk mengurutkan berdasarkan Fakultas / Jurusan"
                                >
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="text-slate-950 font-bold">Fakultas / Jurusan</span>
                                        <ArrowUpDown v-if="sortBy !== 'faculty'" class="w-3.5 h-3.5 text-slate-500 opacity-70 group-hover:opacity-100" />
                                        <ArrowUp v-else-if="sortOrder === 'asc'" class="w-3.5 h-3.5 text-blue-700 stroke-[2.5]" />
                                        <ArrowDown v-else class="w-3.5 h-3.5 text-blue-700 stroke-[2.5]" />
                                    </div>
                                </th>
                                <th class="py-3.5 px-4 text-slate-950 font-bold">Status Suara</th>
                                <th class="py-3.5 px-4 text-right print:hidden text-slate-950 font-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-200 text-slate-900 transition-opacity duration-150"
                            :class="{ 'opacity-50 pointer-events-none': isSearching }"
                        >
                            <tr
                                v-for="v in voters.data"
                                :key="v.id"
                                class="hover:bg-slate-50 transition-colors"
                                :class="{ 'bg-slate-100/80': selectedVoterIds.includes(v.id) }"
                            >
                                <td class="py-3 px-3 text-center print:hidden">
                                    <input
                                        v-if="!v.has_voted"
                                        type="checkbox"
                                        :value="v.id"
                                        v-model="selectedVoterIds"
                                        class="rounded border-slate-400 text-slate-950 focus:ring-slate-950 cursor-pointer"
                                    />
                                    <span v-else class="text-slate-400 text-xs font-bold">-</span>
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-slate-950">
                                    {{ v.student_id }}
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-950">
                                    {{ v.name }}
                                </td>
                                <td class="py-3 px-4 text-slate-900">
                                    <div class="font-semibold text-slate-900">{{ v.faculty }}</div>
                                    <div v-if="v.study_program" class="text-slate-700 text-xs font-medium">{{ v.study_program }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        v-if="v.has_voted"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-950 border border-emerald-300"
                                    >
                                        <CheckCircle2 class="w-3.5 h-3.5 text-emerald-800 stroke-[2.5]" />
                                        <span>Sudah Memilih</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-800 border border-slate-300"
                                    >
                                        <Clock class="w-3.5 h-3.5 text-slate-600 stroke-[2.5]" />
                                        <span>Belum Memilih</span>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right print:hidden">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <span v-if="v.has_voted" class="text-[11px] text-slate-400 italic mr-1">
                                            Hak Suara Dipakai
                                        </span>

                                        <button
                                            type="button"
                                            @click="openEditModal(v)"
                                            class="p-1.5 text-slate-600 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 rounded-md transition-colors bg-white shadow-2xs"
                                            title="Edit data mahasiswa"
                                        >
                                            <Pencil class="w-3.5 h-3.5" />
                                        </button>

                                        <button
                                            v-if="!v.has_voted"
                                            type="button"
                                            @click="openDeleteModal(v)"
                                            class="p-1.5 text-red-600 hover:text-red-700 hover:bg-red-50 border border-slate-200 hover:border-red-200 rounded-md transition-colors bg-white shadow-2xs"
                                            title="Hapus pemilih ini dari DPT"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="voters.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400 text-xs">
                                    Tidak ada data mahasiswa yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="voters.links && voters.links.length > 3" class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs print:hidden">
                    <span class="text-slate-500">
                        Menampilkan <span class="font-semibold text-slate-800">{{ voters.from || 0 }}</span> sampai <span class="font-semibold text-slate-800">{{ voters.to || 0 }}</span> dari <span class="font-semibold text-slate-800">{{ voters.total }}</span> pemilih
                    </span>

                    <div class="flex items-center gap-1.5 flex-wrap justify-center">
                        <template v-for="(link, i) in voters.links" :key="i">
                            <!-- Tombol Sebelumnya -->
                            <Component
                                :is="link.url ? Link : 'span'"
                                v-if="i === 0"
                                :href="link.url || undefined"
                                class="px-2.5 py-1.5 border rounded-lg text-slate-700 flex items-center gap-1 transition-colors select-none text-xs"
                                :class="link.url ? 'border-slate-200 hover:border-slate-300 hover:bg-slate-50 cursor-pointer bg-white' : 'border-slate-200/60 text-slate-300 cursor-not-allowed bg-slate-50'"
                            >
                                <ChevronLeft class="w-3.5 h-3.5" />
                                <span class="hidden sm:inline">Sebelumnya</span>
                            </Component>

                            <!-- Tombol Berikutnya -->
                            <Component
                                :is="link.url ? Link : 'span'"
                                v-else-if="i === voters.links.length - 1"
                                :href="link.url || undefined"
                                class="px-2.5 py-1.5 border rounded-lg text-slate-700 flex items-center gap-1 transition-colors select-none text-xs"
                                :class="link.url ? 'border-slate-200 hover:border-slate-300 hover:bg-slate-50 cursor-pointer bg-white' : 'border-slate-200/60 text-slate-300 cursor-not-allowed bg-slate-50'"
                            >
                                <span class="hidden sm:inline">Berikutnya</span>
                                <ChevronRight class="w-3.5 h-3.5" />
                            </Component>

                            <!-- Pemisah Halaman / Ellipsis (...) -->
                            <span
                                v-else-if="link.label === '...'"
                                class="px-2 py-1 text-slate-400 select-none text-xs font-semibold"
                            >
                                ...
                            </span>

                            <!-- Tombol Nomor Halaman -->
                            <Component
                                :is="link.url ? Link : 'span'"
                                v-else
                                :href="link.url || undefined"
                                class="min-w-[32px] h-8 flex items-center justify-center border rounded-lg text-xs font-medium transition-colors select-none px-2"
                                :class="{
                                    'bg-slate-900 text-white border-slate-900 font-bold shadow-2xs': link.active,
                                    'border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700 bg-white': !link.active && link.url,
                                    'border-transparent text-slate-300': !link.url
                                }"
                            >
                                {{ link.label }}
                            </Component>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah Pemilih -->
        <div
            v-if="isAddModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
            @keydown.esc="isAddModalOpen = false"
        >
            <div class="bg-white rounded-xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-4 animate-in fade-in zoom-in-95">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-950">Daftarkan Mahasiswa ke DPT</h3>
                    <button type="button" @click="isAddModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="storeVoter" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nomor Induk Mahasiswa (NIM)</label>
                        <input
                            type="text"
                            v-model="addForm.student_id"
                            required
                            placeholder="Contoh: 20240199"
                            class="w-full font-mono px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap Mahasiswa</label>
                        <input
                            type="text"
                            v-model="addForm.name"
                            required
                            placeholder="Nama sesuai KTM"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Jurusan</label>
                        <select
                            v-model="addForm.faculty"
                            required
                            class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                        >
                            <option value="" disabled>Pilih Jurusan Resmi</option>
                            <option v-for="fac in faculties" :key="fac" :value="fac">
                                {{ fac }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Program Studi</label>
                        <select
                            v-model="addForm.study_program"
                            class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                            :disabled="!addForm.faculty"
                        >
                            <option value="">Pilih Program Studi (Opsional)</option>
                            <option v-for="prog in availableAddPrograms" :key="prog" :value="prog">
                                {{ prog }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tanggal Lahir (Untuk Verifikasi Mandiri)</label>
                        <input
                            type="date"
                            v-model="addForm.birth_date"
                            required
                            class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isAddModalOpen = false"
                            class="px-3.5 py-2 border border-slate-300 rounded-lg text-slate-700 font-medium hover:border-slate-400"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="addForm.processing"
                            class="px-4 py-2 bg-slate-900 text-white rounded-lg font-medium hover:bg-slate-800"
                        >
                            {{ addForm.processing ? 'Menyimpan...' : 'Simpan Data Pemilih' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Pemilih -->
        <div
            v-if="isEditModalOpen && editingVoter"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
            @keydown.esc="isEditModalOpen = false"
        >
            <div class="bg-white rounded-xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-4 animate-in fade-in zoom-in-95">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-950">Perbarui Data Mahasiswa</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Koreksi kesalahan data identitas atau jurusan mahasiswa.</p>
                    </div>
                    <button type="button" @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="updateVoter" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nomor Induk Mahasiswa (NIM)</label>
                        <input
                            type="text"
                            v-model="editForm.student_id"
                            required
                            placeholder="Contoh: 20240199"
                            class="w-full font-mono px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                        />
                        <p v-if="editForm.errors.student_id" class="text-red-600 text-[11px] mt-1">{{ editForm.errors.student_id }}</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap Mahasiswa</label>
                        <input
                            type="text"
                            v-model="editForm.name"
                            required
                            placeholder="Nama sesuai KTM"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                        />
                        <p v-if="editForm.errors.name" class="text-red-600 text-[11px] mt-1">{{ editForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Jurusan</label>
                        <select
                            v-model="editForm.faculty"
                            required
                            class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                        >
                            <option value="" disabled>Pilih Jurusan Resmi</option>
                            <option v-for="fac in faculties" :key="fac" :value="fac">
                                {{ fac }}
                            </option>
                        </select>
                        <p v-if="editForm.errors.faculty" class="text-red-600 text-[11px] mt-1">{{ editForm.errors.faculty }}</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Program Studi</label>
                        <select
                            v-model="editForm.study_program"
                            class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                            :disabled="!editForm.faculty"
                        >
                            <option value="">Pilih Program Studi (Opsional)</option>
                            <option v-for="prog in availableEditPrograms" :key="prog" :value="prog">
                                {{ prog }}
                            </option>
                        </select>
                        <p v-if="editForm.errors.study_program" class="text-red-600 text-[11px] mt-1">{{ editForm.errors.study_program }}</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tanggal Lahir</label>
                        <input
                            type="date"
                            v-model="editForm.birth_date"
                            required
                            class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                        />
                        <p v-if="editForm.errors.birth_date" class="text-red-600 text-[11px] mt-1">{{ editForm.errors.birth_date }}</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isEditModalOpen = false"
                            class="px-3.5 py-2 border border-slate-300 rounded-lg text-slate-700 font-medium hover:border-slate-400"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-4 py-2 bg-slate-900 text-white rounded-lg font-medium hover:bg-slate-800 disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Satuan -->
        <div
            v-if="isDeleteModalOpen && deletingVoter"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
            @keydown.esc="isDeleteModalOpen = false"
        >
            <div class="bg-white rounded-xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-5 animate-in fade-in zoom-in-95">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center text-red-700 shrink-0">
                        <Trash2 class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-950">Hapus Data Pemilih</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Data mahasiswa ini akan dihapus dari Daftar Pemilih Tetap (DPT).
                        </p>
                    </div>
                </div>

                <!-- Info Mahasiswa yang akan dihapus -->
                <div class="border border-slate-200/90 rounded-xl p-4 bg-slate-50 space-y-2.5 text-xs">
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-2">
                        <span class="text-slate-500">Nama Mahasiswa:</span>
                        <span class="font-bold text-slate-900">{{ deletingVoter.name }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-2">
                        <span class="text-slate-500">NIM:</span>
                        <span class="font-mono font-bold text-slate-900 bg-white border border-slate-200 px-2 py-0.5 rounded">{{ deletingVoter.student_id }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Fakultas / Jurusan:</span>
                        <span class="text-slate-700 font-medium">{{ deletingVoter.faculty }}</span>
                    </div>
                </div>

                <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-900 text-xs flex items-start gap-2.5">
                    <AlertTriangle class="w-4 h-4 text-red-600 mt-0.5 shrink-0" />
                    <div class="leading-relaxed">
                        Tindakan ini tidak dapat dibatalkan. Akun pemilih mahasiswa ini akan dihapus secara permanen dari DPT.
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="isDeleteModalOpen = false"
                        :disabled="isDeleting"
                        class="px-4 py-2 border border-slate-300 hover:border-slate-400 text-slate-700 font-medium text-xs rounded-lg transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmDeleteVoter"
                        :disabled="isDeleting"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-300 text-white font-semibold text-xs rounded-lg transition-colors flex items-center gap-1.5 shadow-xs"
                    >
                        <Trash2 class="w-3.5 h-3.5" :class="{ 'animate-spin': isDeleting }" />
                        <span>{{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Data' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Terpilih (Bulk Delete) -->
        <div
            v-if="isBulkDeleteModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
            @keydown.esc="isBulkDeleteModalOpen = false"
        >
            <div class="bg-white rounded-xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-5 animate-in fade-in zoom-in-95">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center text-red-700 shrink-0">
                        <Trash2 class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-950">
                            Hapus {{ isSelectAllAcrossPages ? totalSelectable : selectedVoterIds.length }} Pemilih Terpilih
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Konfirmasi penghapusan massal data pemilih yang telah Anda tandai.
                        </p>
                    </div>
                </div>

                <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-900 text-xs flex items-start gap-2.5">
                    <AlertTriangle class="w-4 h-4 text-red-600 mt-0.5 shrink-0" />
                    <div class="leading-relaxed">
                        Total <strong>{{ isSelectAllAcrossPages ? totalSelectable : selectedVoterIds.length }} data pemilih</strong> yang belum menggunakan hak suara akan dihapus secara permanen dari DPT. Tindakan ini tidak dapat dibatalkan.
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="isBulkDeleteModalOpen = false"
                        :disabled="isBulkDeleting"
                        class="px-4 py-2 border border-slate-300 hover:border-slate-400 text-slate-700 font-medium text-xs rounded-lg transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmBulkDelete"
                        :disabled="isBulkDeleting"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-300 text-white font-semibold text-xs rounded-lg transition-colors flex items-center gap-1.5 shadow-xs"
                    >
                        <Trash2 class="w-3.5 h-3.5" :class="{ 'animate-spin': isBulkDeleting }" />
                        <span>{{ isBulkDeleting ? 'Menghapus...' : ('Ya, Hapus ' + (isSelectAllAcrossPages ? totalSelectable : selectedVoterIds.length) + ' Data') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Unggah / Impor Berkas DPT (Excel, PDF, CSV, TXT, Paste) -->
        <div
            v-if="isImportModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs overflow-y-auto"
            @keydown.esc="isImportModalOpen = false; isPreviewAnimation = false"
        >
            <div class="relative bg-white rounded-2xl border border-slate-200 max-w-xl w-full p-6 shadow-xl space-y-5 animate-in fade-in zoom-in-95 my-8 overflow-hidden">
                <!-- Overlay Animasi Kertas Masuk ke Map saat Ekstraksi atau Pratinjau -->
                <div
                    v-if="importForm.processing || isPreviewAnimation"
                    class="absolute inset-0 bg-white/95 backdrop-blur-xs z-30 flex flex-col items-center justify-center p-6 text-center animate-in fade-in duration-200"
                >
                    <FolderPaperAnimation
                        :title="importForm.processing ? 'Sedang Memasukkan Data ke Dalam DPT...' : 'Animasi Kertas Masuk ke Dalam Map'"
                        :subtitle="importForm.processing ? 'Sistem sedang membaca berkas dan mengekstrak data NIM & Nama mahasiswa secara otomatis ke database.' : 'Ilustrasi berkas dokumen DPT yang meluncur masuk dan tersimpan rapi di dalam map arsip pemilu.'"
                        :fileName="importFile ? importFile.name : (importMode === 'paste' ? 'Salinan Teks DPT' : 'DIII Teknologi Bank Darah_Jurusan Analis Kesehatan 2024.pdf')"
                        :fileSize="importFile ? ((importFile.size / 1024).toFixed(1) + ' KB') : '423.3 KB'"
                    />

                    <button
                        v-if="isPreviewAnimation && !importForm.processing"
                        type="button"
                        @click="isPreviewAnimation = false"
                        class="mt-3 px-4 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition-colors shadow-2xs"
                    >
                        Tutup Animasi
                    </button>
                </div>

                <!-- Header Modal -->
                <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-800 shrink-0">
                            <FileUp class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-950">
                                Upload & Impor Berkas DPT
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Ekstrak data NIM dan Nama mahasiswa secara otomatis dari dokumen lama.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="isPreviewAnimation = !isPreviewAnimation"
                            class="text-[11px] text-slate-600 hover:text-slate-900 font-medium px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors hidden sm:inline-flex items-center gap-1.5"
                            title="Lihat contoh animasi kertas masuk map"
                        >
                            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                            <span>{{ isPreviewAnimation ? 'Tutup Animasi' : 'Lihat Animasi' }}</span>
                        </button>
                        <button
                            type="button"
                            @click="isImportModalOpen = false; isPreviewAnimation = false"
                            class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Info Cerdas Ekstraksi -->
                <div class="p-3 rounded-lg bg-blue-50/70 border border-blue-200 text-blue-900 text-xs flex items-start gap-2.5">
                    <CheckCircle2 class="w-4 h-4 text-blue-600 mt-0.5 shrink-0" />
                    <div class="leading-relaxed">
                        Sistem secara cerdas membaca kolom <strong>NIM</strong> dan <strong>Nama</strong> secara otomatis tanpa perlu mengubah susunan berkas. Data NIM yang sudah terdaftar akan otomatis dilewati agar tidak terjadi duplikasi.
                    </div>
                </div>

                <!-- Tab Mode Input: Berkas vs Teks Salin-Tempel -->
                <div class="flex items-center gap-2 border-b border-slate-200 pb-1 text-xs">
                    <button
                        type="button"
                        @click="importMode = 'file'"
                        :class="[
                            'pb-2 px-3 font-semibold transition-colors border-b-2 -mb-1',
                            importMode === 'file'
                                ? 'border-slate-900 text-slate-900'
                                : 'border-transparent text-slate-500 hover:text-slate-800'
                        ]"
                    >
                        Unggah Berkas Dokumen
                    </button>
                    <button
                        type="button"
                        @click="importMode = 'paste'"
                        :class="[
                            'pb-2 px-3 font-semibold transition-colors border-b-2 -mb-1',
                            importMode === 'paste'
                                ? 'border-slate-900 text-slate-900'
                                : 'border-transparent text-slate-500 hover:text-slate-800'
                        ]"
                    >
                        Salin & Tempel Teks (Paste)
                    </button>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4 text-xs">
                    <!-- Mode Unggah Berkas -->
                    <div v-if="importMode === 'file'" class="space-y-3">
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept=".xlsx,.xls,.csv,.pdf,.txt"
                            @change="onFileSelected"
                            class="hidden"
                        />

                        <!-- Area Drag & Drop Berkas dengan Animasi Map -->
                        <div
                            v-if="!importFile"
                            @click="triggerFileInput"
                            class="border-2 border-dashed border-slate-300 hover:border-slate-400 bg-slate-50/60 hover:bg-slate-50 rounded-xl p-5 text-center cursor-pointer transition-all group"
                        >
                            <div class="transform group-hover:scale-105 transition-transform duration-300">
                                <FolderPaperAnimation compact />
                            </div>
                            <div class="text-xs font-semibold text-slate-800 mt-1">
                                Klik untuk memilih berkas atau seret berkas ke sini
                            </div>
                            <div class="text-[11px] text-slate-500 mt-1">
                                Mendukung berkas <strong>Excel (.xlsx, .xls)</strong>, <strong>PDF (.pdf)</strong>, <strong>CSV (.csv)</strong>, dan <strong>TXT (.txt)</strong> hingga 15MB
                            </div>
                        </div>

                        <!-- Pratinjau Berkas Terpilih -->
                        <div
                            v-else
                            class="border border-slate-200 rounded-xl p-3.5 bg-slate-50 flex items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
                                    <FileSpreadsheet v-if="importFile.name.endsWith('.xlsx') || importFile.name.endsWith('.xls') || importFile.name.endsWith('.csv')" class="w-5 h-5" />
                                    <FileText v-else class="w-5 h-5" />
                                </div>
                                <div class="overflow-hidden">
                                    <div class="font-semibold text-slate-900 truncate">
                                        {{ importFile.name }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        {{ (importFile.size / 1024).toFixed(1) }} KB &bull; Siap diekstrak ke map DPT
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="removeSelectedFile"
                                class="text-xs text-rose-600 hover:text-rose-800 font-medium px-2 py-1 hover:bg-rose-50 rounded-md transition-colors shrink-0"
                            >
                                Ganti Berkas
                            </button>
                        </div>

                        <div v-if="importForm.errors.file" class="text-rose-600 text-xs">
                            {{ importForm.errors.file }}
                        </div>
                    </div>

                    <!-- Mode Salin & Tempel Teks -->
                    <div v-else class="space-y-2">
                        <label class="block font-semibold text-slate-700">
                            Tempelkan Data Mahasiswa (Tabel / Teks)
                        </label>
                        <textarea
                            v-model="importForm.raw_text"
                            rows="6"
                            placeholder="Contoh:&#10;1. Anis Rifanabila P1337434324001&#10;2. Haza Saadah P1337434324002&#10;&#10;Atau format tabel markdown / salinan dari Word & Excel..."
                            class="w-full text-xs font-mono p-3 border border-slate-300 rounded-xl text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none"
                        ></textarea>
                        <div v-if="importForm.errors.raw_text" class="text-rose-600 text-xs">
                            {{ importForm.errors.raw_text }}
                        </div>
                    </div>

                    <!-- Opsi Tambahan: Jurusan & Program Studi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-slate-100">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">
                                Jurusan (Opsional)
                            </label>
                            <input
                                type="text"
                                v-model="importForm.faculty"
                                placeholder="Misal: Jurusan Analis Kesehatan"
                                class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">
                                Program Studi (Opsional)
                            </label>
                            <input
                                type="text"
                                v-model="importForm.study_program"
                                placeholder="Misal: Sarjana Terapan TLM"
                                class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Tombol Aksi Modal -->
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isImportModalOpen = false"
                            :disabled="importForm.processing"
                            class="px-4 py-2 border border-slate-300 hover:border-slate-400 text-slate-700 font-medium text-xs rounded-lg transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="importForm.processing || (importMode === 'file' && !importFile) || (importMode === 'paste' && !importForm.raw_text.trim())"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 disabled:bg-slate-400 text-white font-semibold text-xs rounded-lg transition-colors flex items-center gap-1.5 shadow-xs"
                        >
                            <span v-if="importForm.processing">Mengekstrak Data...</span>
                            <span v-else>Mulai Ekstraksi & Impor ke DPT</span>
                            <FileUp v-if="!importForm.processing" class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Semua Data DPT -->
        <div
            v-if="isDestroyAllModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
            @keydown.esc="isDestroyAllModalOpen = false"
        >
            <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-4 animate-in fade-in zoom-in-95">
                <div class="flex items-start gap-3.5 border-b border-slate-100 pb-4">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-600 shrink-0">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-950">Kosongkan Seluruh Data DPT</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Peringatan: Tindakan ini akan menghapus data pemilih secara permanen.
                        </p>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-rose-50/70 border border-rose-200 text-rose-900 text-xs space-y-2">
                    <div class="font-semibold flex items-center gap-1.5">
                        <ShieldAlert class="w-4 h-4 text-rose-600 shrink-0" />
                        <span>Total {{ stats.total }} mahasiswa terdaftar di DPT</span>
                    </div>
                    <p class="leading-relaxed text-rose-800">
                        Seluruh akun pemilih yang telah diimpor atau didaftarkan akan dihapus dari sistem dan tidak dapat dipulihkan kembali.
                    </p>
                </div>

                <!-- Opsi jika ada pemilih yang sudah memilih -->
                <div v-if="stats.voted > 0" class="pt-1">
                    <label class="flex items-start gap-2.5 p-3 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer text-xs">
                        <input
                            type="checkbox"
                            v-model="destroyAllForm.include_voted"
                            class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 mt-0.5"
                        />
                        <div class="leading-relaxed text-slate-700">
                            <strong>Hapus juga {{ stats.voted }} pemilih yang telah menggunakan hak suara</strong> (Reset total pemilu)
                        </div>
                    </label>
                </div>

                <!-- Input Konfirmasi Kata HAPUS -->
                <div class="space-y-1.5 pt-1">
                    <label class="block text-xs font-semibold text-slate-700">
                        Ketik kata <span class="font-mono text-rose-600 font-bold bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">HAPUS</span> untuk membuka kunci tombol:
                    </label>
                    <input
                        type="text"
                        v-model="destroyAllForm.confirm_text"
                        placeholder="Ketik HAPUS di sini"
                        class="w-full text-xs font-mono px-3 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:border-rose-600 focus:ring-1 focus:ring-rose-600 focus:outline-none"
                    />
                    <div v-if="destroyAllForm.errors.confirm_text" class="text-rose-600 text-xs">
                        {{ destroyAllForm.errors.confirm_text }}
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button
                        type="button"
                        @click="isDestroyAllModalOpen = false"
                        :disabled="isDestroyingAll"
                        class="px-4 py-2 border border-slate-300 hover:border-slate-400 text-slate-700 font-medium text-xs rounded-lg transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmDestroyAll"
                        :disabled="isDestroyingAll || destroyAllForm.confirm_text.trim().toUpperCase() !== 'HAPUS'"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:bg-rose-300 text-white font-semibold text-xs rounded-lg transition-colors flex items-center gap-1.5 shadow-xs"
                    >
                        <Trash2 class="w-3.5 h-3.5" :class="{ 'animate-spin': isDestroyingAll }" />
                        <span>{{ isDestroyingAll ? 'Menghapus Semua...' : 'Ya, Hapus Semua Data DPT' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
