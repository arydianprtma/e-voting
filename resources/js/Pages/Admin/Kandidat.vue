<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Trash2, Edit3, User, Users, X, Check, Upload, Image as ImageIcon } from 'lucide-vue-next';

const props = defineProps({
    candidates: Array,
});

const isModalOpen = ref(false);
const editingCandidate = ref(null);
const isDeleteModalOpen = ref(false);
const deletingCandidate = ref(null);

const photoInput = ref(null);
const photoPreview = ref(null);

const form = useForm({
    _method: 'POST',
    candidate_number: '',
    candidate_type: 'duo',
    chairman_name: '',
    deputy_name: '',
    faculty_info: '',
    vision: '',
    missions: [''],
    photo: null,
    remove_photo: false,
});

const openCreateModal = () => {
    editingCandidate.value = null;
    form.reset();
    form.clearErrors();
    form._method = 'POST';
    form.candidate_type = 'duo';
    form.missions = [''];
    form.photo = null;
    form.remove_photo = false;
    photoPreview.value = null;
    if (photoInput.value) {
        photoInput.value.value = '';
    }
    isModalOpen.value = true;
};

const openEditModal = (candidate) => {
    editingCandidate.value = candidate;
    form.clearErrors();
    form._method = 'PUT';
    form.candidate_number = candidate.candidate_number;
    form.candidate_type = candidate.candidate_type || (candidate.deputy_name ? 'duo' : 'solo');
    form.chairman_name = candidate.chairman_name;
    form.deputy_name = candidate.deputy_name || '';
    form.faculty_info = candidate.faculty_info;
    form.vision = candidate.vision;
    form.missions = candidate.missions && candidate.missions.length > 0 ? [...candidate.missions] : [''];
    form.photo = null;
    form.remove_photo = false;
    photoPreview.value = candidate.photo_path || null;
    if (photoInput.value) {
        photoInput.value.value = '';
    }
    isModalOpen.value = true;
};

const onPhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        form.remove_photo = false;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const triggerPhotoSelect = () => {
    if (photoInput.value) {
        photoInput.value.click();
    }
};

const clearSelectedPhoto = () => {
    form.photo = null;
    form.remove_photo = true;
    photoPreview.value = null;
    if (photoInput.value) {
        photoInput.value.value = '';
    }
};

const addMissionField = () => {
    form.missions.push('');
};

const removeMissionField = (index) => {
    if (form.missions.length > 1) {
        form.missions.splice(index, 1);
    }
};

const saveCandidate = () => {
    if (form.candidate_type === 'solo') {
        form.deputy_name = '';
    }

    if (editingCandidate.value) {
        form.post(route('admin.candidates.update', editingCandidate.value.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route('admin.candidates.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
};

const openDeleteModal = (candidate) => {
    deletingCandidate.value = candidate;
    isDeleteModalOpen.value = true;
};

const confirmDeleteCandidate = () => {
    if (!deletingCandidate.value) return;
    const deleteForm = useForm({});
    deleteForm.delete(route('admin.candidates.destroy', deletingCandidate.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            deletingCandidate.value = null;
        },
    });
};
</script>

<template>
    <Head title="Manajemen Calon & Paslon" />

    <AuthenticatedLayout>
        <template #title>
            Data Calon Pemilihan
        </template>

        <template #actions>
            <button
                type="button"
                @click="openCreateModal"
                class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold py-2 px-3.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-xs"
            >
                <Plus class="w-3.5 h-3.5" />
                <span>Tambah Calon</span>
            </button>
        </template>

        <div class="space-y-6">
            <!-- Header Ringkas -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-950">Daftar Pasangan Calon & Calon Tunggal</h2>
                    <p class="text-xs text-slate-700 font-medium mt-0.5">
                        Kelola data calon BEM/DEMA dengan foto resmi serta opsi format Solo (sendiri) atau Duo (berpasangan).
                    </p>
                </div>
                <div class="text-xs font-mono font-bold px-2.5 py-1 bg-slate-100 rounded-md text-slate-900 border border-slate-300">
                    Total: {{ candidates.length }} Calon Terdaftar
                </div>
            </div>

            <!-- Grid Kartu Paslon Modern -->
            <div class="grid md:grid-cols-2 gap-6">
                <div
                    v-for="c in candidates"
                    :key="c.id"
                    class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between space-y-5"
                >
                    <div class="space-y-4">
                        <!-- Header Kartu -->
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <!-- Nomor Urut -->
                                <div class="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center font-mono font-bold text-xl shadow-xs shrink-0">
                                    {{ String(c.candidate_number).padStart(2, '0') }}
                                </div>

                                <!-- Foto Calon Mini (Format Portrait 4x6) -->
                                <div class="w-12 h-16 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center shrink-0 shadow-2xs">
                                    <img
                                        v-if="c.photo_path"
                                        :src="c.photo_path"
                                        :alt="'Foto No. ' + c.candidate_number"
                                        class="w-full h-full object-cover object-top"
                                    />
                                    <User v-else-if="c.candidate_type === 'solo' || !c.deputy_name" class="w-6 h-6 text-slate-600" />
                                    <Users v-else class="w-6 h-6 text-slate-600" />
                                </div>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-700">
                                            No. Urut {{ c.candidate_number }}
                                        </span>
                                        <span
                                            class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                                            :class="c.candidate_type === 'solo' || !c.deputy_name ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-blue-100 text-blue-900 border border-blue-300'"
                                        >
                                            {{ c.candidate_type === 'solo' || !c.deputy_name ? 'Solo' : 'Duo' }}
                                        </span>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-950 mt-0.5">{{ c.chairman_name }}</h3>
                                    <div v-if="c.candidate_type !== 'solo' && c.deputy_name" class="text-xs text-slate-700 font-semibold">
                                        & {{ c.deputy_name }}
                                    </div>
                                    <div v-else class="text-[11px] text-amber-800 font-bold">
                                        Calon Tunggal
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="openEditModal(c)"
                                    class="p-2 text-slate-600 hover:text-slate-950 rounded-lg hover:bg-slate-100 transition-colors"
                                    title="Edit Calon"
                                >
                                    <Edit3 class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="openDeleteModal(c)"
                                    class="p-2 text-rose-600 hover:text-rose-800 rounded-lg hover:bg-rose-50 transition-colors"
                                    title="Hapus Calon"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Fakultas -->
                        <div class="text-xs text-slate-800 bg-slate-50 rounded-lg p-2.5 border border-slate-200 font-semibold">
                            {{ c.faculty_info }}
                        </div>

                        <!-- Visi -->
                        <div class="text-xs space-y-1">
                            <div class="font-bold text-slate-950">Visi Utama:</div>
                            <p class="text-slate-800 font-medium leading-relaxed italic bg-slate-50 p-2.5 rounded border border-slate-200">
                                "{{ c.vision }}"
                            </p>
                        </div>

                        <!-- Misi -->
                        <div class="text-xs space-y-1.5">
                            <div class="font-bold text-slate-950">Misi ({{ c.missions?.length || 0 }} Butir):</div>
                            <ol class="list-decimal list-inside space-y-1 text-slate-800 font-medium text-xs">
                                <li v-for="(m, idx) in c.missions" :key="idx" class="leading-relaxed">
                                    {{ m }}
                                </li>
                            </ol>
                        </div>
                    </div>

                    <!-- Footer Kartu: Jumlah Suara -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-700 font-bold">Total Perolehan Suara:</span>
                        <span class="font-mono font-bold text-base text-slate-950 bg-slate-100 px-3 py-1 rounded-md border border-slate-200">
                            {{ c.votes_count }} Suara
                        </span>
                    </div>
                </div>

                <div
                    v-if="candidates.length === 0"
                    class="md:col-span-2 bg-white border border-dashed border-slate-300 rounded-xl p-12 text-center text-xs text-slate-500"
                >
                    Belum ada calon yang terdaftar. Klik tombol "Tambah Calon" di kanan atas.
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Calon -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
            @keydown.esc="isModalOpen = false"
        >
            <div class="bg-white rounded-xl border border-slate-200 max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-950">
                        {{ editingCandidate ? 'Perbarui Data Calon' : 'Tambah Calon Baru' }}
                    </h3>
                    <button type="button" @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="saveCandidate" class="space-y-4 text-xs">
                    <!-- Pilihan Format Pencalonan (Solo vs Duo) -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Format Pencalonan <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label
                                class="flex items-center gap-2.5 p-3 rounded-lg border cursor-pointer transition-all"
                                :class="form.candidate_type === 'duo' ? 'border-slate-900 bg-slate-50 ring-1 ring-slate-900' : 'border-slate-200 hover:bg-slate-50/50'"
                            >
                                <input
                                    type="radio"
                                    value="duo"
                                    v-model="form.candidate_type"
                                    class="text-slate-900 focus:ring-slate-900 border-slate-300"
                                />
                                <div>
                                    <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                        <Users class="w-4 h-4 text-blue-600" />
                                        <span>Duo (Pasangan)</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500">Ketua & Wakil Ketua</div>
                                </div>
                            </label>

                            <label
                                class="flex items-center gap-2.5 p-3 rounded-lg border cursor-pointer transition-all"
                                :class="form.candidate_type === 'solo' ? 'border-slate-900 bg-slate-50 ring-1 ring-slate-900' : 'border-slate-200 hover:bg-slate-50/50'"
                            >
                                <input
                                    type="radio"
                                    value="solo"
                                    v-model="form.candidate_type"
                                    class="text-slate-900 focus:ring-slate-900 border-slate-300"
                                />
                                <div>
                                    <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                        <User class="w-4 h-4 text-amber-600" />
                                        <span>Solo (Tunggal)</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500">Sendiri tanpa wakil</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Upload Foto Calon -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-semibold text-slate-700">Foto Resmi Calon (Opsional)</label>
                            <span class="text-[11px] text-slate-500 font-medium">Format Portrait: 4x6 / 3x4 / 9:16</span>
                        </div>
                        <div class="flex items-center gap-4 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                            <!-- Preview Foto Portrait 4x6 -->
                            <div class="w-20 h-28 rounded-lg overflow-hidden border border-slate-300 bg-white flex items-center justify-center shrink-0 shadow-2xs">
                                <img
                                    v-if="photoPreview"
                                    :src="photoPreview"
                                    alt="Preview Foto"
                                    class="w-full h-full object-cover object-top"
                                />
                                <div v-else class="flex flex-col items-center justify-center text-slate-400 p-2 text-center">
                                    <ImageIcon class="w-7 h-7 text-slate-300 mb-1" />
                                    <span class="text-[10px] leading-tight">4x6 Pas Foto</span>
                                </div>
                            </div>

                            <div class="space-y-1.5 flex-1">
                                <input
                                    ref="photoInput"
                                    type="file"
                                    accept="image/png, image/jpeg, image/jpg, image/webp"
                                    @change="onPhotoChange"
                                    class="hidden"
                                />
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="triggerPhotoSelect"
                                        class="px-3 py-1.5 bg-white border border-slate-300 rounded-md font-semibold text-slate-700 hover:border-slate-400 flex items-center gap-1.5"
                                    >
                                        <Upload class="w-3.5 h-3.5" />
                                        <span>{{ photoPreview ? 'Ganti Foto' : 'Unggah Foto' }}</span>
                                    </button>
                                    <button
                                        v-if="photoPreview"
                                        type="button"
                                        @click="clearSelectedPhoto"
                                        class="px-2.5 py-1.5 text-rose-600 hover:bg-rose-50 rounded-md font-medium"
                                    >
                                        Hapus
                                    </button>
                                </div>
                                <div class="text-[11px] text-slate-500 leading-normal">
                                    Mendukung pas foto formal 4x6, 3x4, atau 9:16 portrait (JPG, PNG, WebP maks 2MB). Foto akan ditampilkan proporsional tanpa terpotong.
                                </div>
                                <div v-if="form.errors.photo" class="text-rose-600 text-[11px] mt-1">
                                    {{ form.errors.photo }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nomor Urut <span class="text-rose-500">*</span></label>
                            <input
                                type="number"
                                v-model="form.candidate_number"
                                required
                                min="1"
                                class="w-full font-mono px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                            />
                            <div v-if="form.errors.candidate_number" class="text-rose-600 mt-1">{{ form.errors.candidate_number }}</div>
                        </div>
                        <div class="col-span-2">
                            <label class="block font-semibold text-slate-700 mb-1">Fakultas / Asal Jurusan <span class="text-rose-500">*</span></label>
                            <input
                                type="text"
                                v-model="form.faculty_info"
                                required
                                placeholder="Contoh: Keperawatan & Gizi"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                            />
                            <div v-if="form.errors.faculty_info" class="text-rose-600 mt-1">{{ form.errors.faculty_info }}</div>
                        </div>
                    </div>

                    <div class="grid" :class="form.candidate_type === 'duo' ? 'grid-cols-2 gap-3' : 'grid-cols-1'">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">
                                {{ form.candidate_type === 'solo' ? 'Nama Calon Tunggal' : 'Nama Calon Ketua' }}
                                <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.chairman_name"
                                required
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                            />
                            <div v-if="form.errors.chairman_name" class="text-rose-600 mt-1">{{ form.errors.chairman_name }}</div>
                        </div>
                        <div v-if="form.candidate_type === 'duo'">
                            <label class="block font-semibold text-slate-700 mb-1">
                                Nama Calon Wakil <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.deputy_name"
                                :required="form.candidate_type === 'duo'"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                            />
                            <div v-if="form.errors.deputy_name" class="text-rose-600 mt-1">{{ form.errors.deputy_name }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Visi Utama <span class="text-rose-500">*</span></label>
                        <textarea
                            v-model="form.vision"
                            required
                            rows="2"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                        ></textarea>
                        <div v-if="form.errors.vision" class="text-rose-600 mt-1">{{ form.errors.vision }}</div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block font-semibold text-slate-700">Butir Misi <span class="text-rose-500">*</span></label>
                            <button
                                type="button"
                                @click="addMissionField"
                                class="text-slate-900 font-semibold text-[11px] hover:underline"
                            >
                                + Tambah Misi
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(m, idx) in form.missions" :key="idx" class="flex gap-2">
                                <input
                                    type="text"
                                    v-model="form.missions[idx]"
                                    required
                                    :placeholder="`Misi butir ke-${idx + 1}`"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-slate-900 focus:outline-none"
                                />
                                <button
                                    type="button"
                                    @click="removeMissionField(idx)"
                                    class="p-2 text-rose-500 hover:text-rose-700"
                                    title="Hapus baris misi"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                        <div v-if="form.errors.missions" class="text-rose-600 mt-1">{{ form.errors.missions }}</div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-3.5 py-2 border border-slate-300 rounded-lg text-slate-700 font-medium hover:border-slate-400"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-slate-900 text-white rounded-lg font-medium hover:bg-slate-800"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Calon' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Calon Modern -->
        <div
            v-if="isDeleteModalOpen && deletingCandidate"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
            @keydown.esc="isDeleteModalOpen = false"
        >
            <div class="bg-white rounded-xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-4 animate-in fade-in zoom-in-95">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center justify-center shrink-0">
                        <Trash2 class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-950">Konfirmasi Hapus Calon</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Data calon yang dihapus tidak dapat dipulihkan kembali.
                        </p>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50 text-xs space-y-1.5">
                    <div class="font-bold text-slate-950">
                        No. {{ deletingCandidate.candidate_number }}: {{ deletingCandidate.chairman_name }}
                        <span v-if="deletingCandidate.candidate_type !== 'solo' && deletingCandidate.deputy_name">
                            & {{ deletingCandidate.deputy_name }}
                        </span>
                    </div>
                    <div class="text-slate-500">{{ deletingCandidate.faculty_info }}</div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="isDeleteModalOpen = false"
                        class="px-3.5 py-2 border border-slate-300 hover:border-slate-400 text-slate-700 text-xs font-medium rounded-lg"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmDeleteCandidate"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:bg-rose-400 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5"
                    >
                        <span>Ya, Hapus Calon</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
