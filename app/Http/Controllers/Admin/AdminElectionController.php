<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\ElectionSetting;
use App\Models\VoteReceipt;
use App\Models\Voter;
use App\Models\VotingSession;
use App\Services\VoterImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminElectionController extends Controller
{
    /**
     * Dashboard ringkasan panitia pemilihan.
     */
    public function dashboard(): Response
    {
        $setting = ElectionSetting::current();
        $candidates = Candidate::orderBy('candidate_number', 'asc')->get();
        $totalVoters = Voter::count();
        $votedCount = Voter::where('has_voted', true)->count();
        $unvotedCount = $totalVoters - $votedCount;
        $totalVotes = Candidate::sum('votes_count');

        return Inertia::render('Admin/Dashboard', [
            'setting' => $setting,
            'candidates' => $candidates,
            'stats' => [
                'total_voters' => $totalVoters,
                'voted_count' => $votedCount,
                'unvoted_count' => $unvotedCount,
                'total_votes' => (int) $totalVotes,
                'turnout_percentage' => $totalVoters > 0 ? round(($votedCount / $totalVoters) * 100, 1) : 0,
            ],
        ]);
    }

    /**
     * Halaman manajemen pasangan calon.
     */
    public function candidates(): Response
    {
        $candidates = Candidate::orderBy('candidate_number', 'asc')->get();

        return Inertia::render('Admin/Kandidat', [
            'candidates' => $candidates,
        ]);
    }

    /**
     * Simpan paslon / calon tunggal baru.
     */
    public function storeCandidate(Request $request): RedirectResponse
    {
        $request->validate([
            'candidate_number' => ['required', 'integer', 'unique:candidates,candidate_number'],
            'candidate_type' => ['required', 'in:solo,duo'],
            'chairman_name' => ['required', 'string', 'max:255'],
            'deputy_name' => ['nullable', 'required_if:candidate_type,duo', 'string', 'max:255'],
            'faculty_info' => ['required', 'string', 'max:255'],
            'vision' => ['required', 'string'],
            'missions' => ['required', 'array', 'min:1'],
            'missions.*' => ['required', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'candidate_number.unique' => 'Nomor urut paslon sudah terdaftar.',
            'deputy_name.required_if' => 'Nama calon wakil wajib diisi jika tipe pencalonan adalah Duo.',
            'missions.min' => 'Wajib menyertakan minimal satu butir misi.',
            'photo.image' => 'File foto harus berupa berkas gambar (JPG, PNG, atau WebP).',
            'photo.max' => 'Ukuran foto maksimal adalah 2MB.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $stored = $request->file('photo')->store('candidates', 'public');
            $photoPath = '/storage/' . $stored;
        }

        Candidate::create([
            'candidate_number' => $request->candidate_number,
            'candidate_type' => $request->candidate_type,
            'chairman_name' => $request->chairman_name,
            'deputy_name' => $request->candidate_type === 'solo' ? null : $request->deputy_name,
            'faculty_info' => $request->faculty_info,
            'vision' => $request->vision,
            'missions' => array_values(array_filter($request->missions)),
            'photo_path' => $photoPath,
        ]);

        return back()->with('success', 'Data calon berhasil ditambahkan.');
    }

    /**
     * Perbarui data paslon / calon tunggal.
     */
    public function updateCandidate(Request $request, Candidate $candidate): RedirectResponse
    {
        $request->validate([
            'candidate_number' => ['required', 'integer', 'unique:candidates,candidate_number,' . $candidate->id],
            'candidate_type' => ['required', 'in:solo,duo'],
            'chairman_name' => ['required', 'string', 'max:255'],
            'deputy_name' => ['nullable', 'required_if:candidate_type,duo', 'string', 'max:255'],
            'faculty_info' => ['required', 'string', 'max:255'],
            'vision' => ['required', 'string'],
            'missions' => ['required', 'array', 'min:1'],
            'missions.*' => ['required', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'deputy_name.required_if' => 'Nama calon wakil wajib diisi jika tipe pencalonan adalah Duo.',
            'photo.image' => 'File foto harus berupa berkas gambar (JPG, PNG, atau WebP).',
            'photo.max' => 'Ukuran foto maksimal adalah 2MB.',
        ]);

        $photoPath = $candidate->photo_path;
        if ($request->hasFile('photo')) {
            if ($candidate->photo_path) {
                $oldPath = str_replace('/storage/', '', $candidate->photo_path);
                Storage::disk('public')->delete($oldPath);
            }
            $stored = $request->file('photo')->store('candidates', 'public');
            $photoPath = '/storage/' . $stored;
        } elseif ($request->boolean('remove_photo')) {
            if ($candidate->photo_path) {
                $oldPath = str_replace('/storage/', '', $candidate->photo_path);
                Storage::disk('public')->delete($oldPath);
            }
            $photoPath = null;
        }

        $candidate->update([
            'candidate_number' => $request->candidate_number,
            'candidate_type' => $request->candidate_type,
            'chairman_name' => $request->chairman_name,
            'deputy_name' => $request->candidate_type === 'solo' ? null : $request->deputy_name,
            'faculty_info' => $request->faculty_info,
            'vision' => $request->vision,
            'missions' => array_values(array_filter($request->missions)),
            'photo_path' => $photoPath,
        ]);

        return back()->with('success', 'Data calon berhasil diperbarui.');
    }

    /**
     * Hapus paslon / calon.
     */
    public function destroyCandidate(Candidate $candidate): RedirectResponse
    {
        if ($candidate->photo_path) {
            $oldPath = str_replace('/storage/', '', $candidate->photo_path);
            Storage::disk('public')->delete($oldPath);
        }
        $candidate->delete();
        return back()->with('success', 'Data calon berhasil dihapus.');
    }

    /**
     * Halaman manajemen pemilih tetap (DPT).
     */
    public function voters(Request $request): Response
    {
        $query = Voter::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('faculty', 'like', "%{$search}%")
                  ->orWhere('study_program', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'voted') {
                $query->where('has_voted', true);
            } elseif ($request->status === 'unvoted') {
                $query->where('has_voted', false);
            }
        }

        if ($request->filled('faculty') && $request->faculty !== 'all') {
            $query->where('faculty', $request->faculty);
        }

        if ($request->filled('study_program') && $request->study_program !== 'all') {
            $query->where('study_program', $request->study_program);
        }

        $sortBy = in_array($request->sort_by, ['student_id', 'name', 'faculty', 'study_program', 'created_at'])
            ? $request->sort_by
            : 'student_id';

        $sortOrder = in_array(strtolower($request->sort_order), ['asc', 'desc'])
            ? strtolower($request->sort_order)
            : 'asc';

        if ($sortBy === 'student_id') {
            $query->orderBy('student_id', $sortOrder)->orderBy('name', 'asc');
        } elseif ($sortBy === 'name') {
            $query->orderBy('name', $sortOrder)->orderBy('student_id', 'asc');
        } elseif ($sortBy === 'faculty') {
            $query->orderBy('faculty', $sortOrder)->orderBy('student_id', 'asc');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $voters = $query->paginate(15)->onEachSide(1)->withQueryString();

        $voterFaculties = Voter::whereNotNull('faculty')
            ->where('faculty', '!=', '')
            ->distinct()
            ->pluck('faculty')
            ->toArray();
        $deptFaculties = Department::getActiveFaculties();
        $availableFaculties = array_values(array_unique(array_filter(array_merge($voterFaculties, $deptFaculties))));
        sort($availableFaculties);

        $availableStudyPrograms = Voter::whereNotNull('study_program')
            ->where('study_program', '!=', '')
            ->distinct()
            ->orderBy('study_program', 'asc')
            ->pluck('study_program')
            ->toArray();

        return Inertia::render('Admin/Pemilih', [
            'voters' => $voters,
            'departmentsGrouped' => Department::getGroupedByFaculty(),
            'faculties' => $availableFaculties,
            'studyPrograms' => $availableStudyPrograms,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
                'faculty' => $request->faculty ?? 'all',
                'study_program' => $request->study_program ?? 'all',
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
            'stats' => [
                'total' => Voter::count(),
                'voted' => Voter::where('has_voted', true)->count(),
                'unvoted' => Voter::where('has_voted', false)->count(),
            ],
        ]);
    }

    /**
     * Tambah data pemilih manual.
     */
    public function storeVoter(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => ['required', 'string', 'unique:voters,student_id'],
            'name' => ['required', 'string', 'max:255'],
            'faculty' => ['required', 'string', 'max:255'],
            'study_program' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
        ], [
            'student_id.unique' => 'NIM mahasiswa sudah terdaftar di DPT.',
            'birth_date.required' => 'Tanggal lahir wajib diisi untuk verifikasi akun mahasiswa.',
        ]);

        Voter::create([
            'student_id' => trim($request->student_id),
            'name' => trim($request->name),
            'faculty' => trim($request->faculty),
            'study_program' => $request->study_program ? trim($request->study_program) : null,
            'birth_date' => $request->birth_date,
            'token' => null,
            'has_voted' => false,
        ]);

        return back()->with('success', 'Pemilih baru berhasil didaftarkan ke DPT.');
    }

    /**
     * Impor data pemilih secara massal dari berkas (Excel, CSV, PDF, TXT) atau teks mentah.
     */
    public function importVoters(Request $request, VoterImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['nullable', 'file', 'mimes:xlsx,xls,csv,txt,pdf', 'max:15360'],
            'raw_text' => ['nullable', 'string'],
            'faculty' => ['nullable', 'string', 'max:255'],
            'study_program' => ['nullable', 'string', 'max:255'],
        ], [
            'file.mimes' => 'Format berkas harus berupa Excel (.xlsx, .xls), CSV (.csv), Dokumen Teks (.txt), atau Dokumen PDF (.pdf).',
            'file.max' => 'Ukuran berkas maksimal adalah 15MB.',
        ]);

        $hasFile = $request->hasFile('file');
        $rawText = trim((string) $request->raw_text);

        if (! $hasFile && empty($rawText)) {
            return back()->withErrors(['file' => 'Silakan pilih berkas dokumen yang akan diunggah atau tempel teks data pemilih.']);
        }

        $defaultFaculty = $request->faculty ? trim($request->faculty) : null;
        $defaultProdi = $request->study_program ? trim($request->study_program) : null;

        if ($hasFile) {
            $result = $importService->importFromFile(
                $request->file('file'),
                $defaultFaculty,
                $defaultProdi
            );
        } else {
            $result = $importService->importFromRawText(
                $rawText,
                $defaultFaculty,
                $defaultProdi
            );
        }

        $imported = $result['imported'];
        $updated = $result['updated'];
        $skipped = $result['skipped_existing'];
        $total = $result['total_found'];

        if ($total === 0) {
            return back()->withErrors(['file' => 'Tidak ditemukan data pemilih yang sesuai (format NIM & Nama) dari berkas yang diunggah.']);
        }

        $message = "Berhasil memproses {$total} baris data: {$imported} pemilih baru terdaftar ke DPT.";
        if ($updated > 0) {
            $message .= " {$updated} data dilengkapi.";
        }
        if ($skipped > 0) {
            $message .= " ({$skipped} data dilewati karena sudah ada di DPT).";
        }

        return back()->with('success', $message);
    }

    /**
     * Reset token pemilih yang belum memilih.
     */
    public function regenerateToken(Voter $voter): RedirectResponse
    {
        if ($voter->has_voted) {
            return back()->withErrors(['error' => 'Token pemilih yang sudah menggunakan hak suara tidak dapat diubah.']);
        }

        $voter->update([
            'token' => 'VT-' . strtoupper(Str::random(6)),
        ]);

        return back()->with('success', 'Token baru berhasil diterbitkan untuk mahasiswa terkait.');
    }

    /**
     * Perbarui data pemilih.
     */
    public function updateVoter(Request $request, Voter $voter): RedirectResponse
    {
        $request->validate([
            'student_id' => ['required', 'string', 'unique:voters,student_id,' . $voter->id],
            'name' => ['required', 'string', 'max:255'],
            'faculty' => ['required', 'string', 'max:255'],
            'study_program' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
        ], [
            'student_id.unique' => 'NIM mahasiswa sudah terdaftar di DPT.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
        ]);

        $voter->update([
            'student_id' => trim($request->student_id),
            'name' => trim($request->name),
            'faculty' => trim($request->faculty),
            'study_program' => $request->study_program ? trim($request->study_program) : null,
            'birth_date' => $request->birth_date,
        ]);

        return back()->with('success', 'Data pemilih berhasil diperbarui.');
    }

    /**
     * Hapus data pemilih individual.
     */
    public function destroyVoter(Voter $voter): RedirectResponse
    {
        if ($voter->has_voted) {
            return back()->withErrors(['error' => 'Pemilih yang sudah menggunakan hak suara tidak dapat dihapus untuk menjaga keabsahan pemilu.']);
        }

        $voter->delete();

        return back()->with('success', 'Data pemilih berhasil dihapus.');
    }

    /**
     * Hapus beberapa data pemilih sekaligus (bulk delete).
     */
    public function bulkDestroyVoters(Request $request): RedirectResponse
    {
        if ($request->boolean('select_all')) {
            $query = Voter::query()->where('has_voted', false);

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('student_id', 'like', "%{$search}%")
                      ->orWhere('faculty', 'like', "%{$search}%")
                      ->orWhere('study_program', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status') && $request->status === 'unvoted') {
                $query->where('has_voted', false);
            }

            if ($request->filled('faculty') && $request->faculty !== 'all') {
                $query->where('faculty', $request->faculty);
            }

            if ($request->filled('study_program') && $request->study_program !== 'all') {
                $query->where('study_program', $request->study_program);
            }

            $deletedCount = $query->delete();
            return back()->with('success', "{$deletedCount} data pemilih berhasil dihapus dari sistem.");
        }

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:voters,id'],
        ]);

        $voters = Voter::whereIn('id', $request->ids)->get();
        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($voters as $voter) {
            if ($voter->has_voted) {
                $skippedCount++;
                continue;
            }
            $voter->delete();
            $deletedCount++;
        }

        if ($skippedCount > 0) {
            return back()->with('success', "{$deletedCount} data pemilih berhasil dihapus ({$skippedCount} dilewati karena sudah memilih).");
        }

        return back()->with('success', "{$deletedCount} data pemilih berhasil dihapus.");
    }

    /**
     * Hapus seluruh data pemilih (DPT) sekaligus.
     */
    public function destroyAllVoters(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm_text' => ['required', 'string'],
        ]);

        if (strtoupper(trim($request->confirm_text)) !== 'HAPUS') {
            return back()->withErrors(['confirm_text' => 'Ketik kata HAPUS untuk mengonfirmasi penghapusan seluruh data DPT.']);
        }

        $includeVoted = (bool) $request->boolean('include_voted');

        if ($includeVoted) {
            $total = Voter::count();
            Voter::query()->delete();
            return back()->with('success', "Seluruh data DPT ({$total} pemilih) berhasil dihapus dari sistem.");
        }

        $votedCount = Voter::where('has_voted', true)->count();
        $deleted = Voter::where('has_voted', false)->delete();

        if ($votedCount > 0) {
            return back()->with('success', "{$deleted} pemilih yang belum memilih berhasil dihapus ({$votedCount} pemilih tetap dipertahankan karena sudah memilih).");
        }

        return back()->with('success', "Seluruh data DPT ({$deleted} pemilih) berhasil dihapus.");
    }

    /**
     * Halaman pengaturan pemilihan.
     */
    public function settings(): Response
    {
        $setting = ElectionSetting::current();

        return Inertia::render('Admin/Pengaturan', [
            'setting' => array_merge($setting->toArray(), [
                'start_time' => $setting->start_time ? $setting->start_time->format('Y-m-d\TH:i') : null,
                'end_time' => $setting->end_time ? $setting->end_time->format('Y-m-d\TH:i') : null,
            ]),
        ]);
    }

    /**
     * Perbarui pengaturan pemilihan.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'organization_name' => ['required', 'string', 'max:255'],
            'announcement' => ['nullable', 'string'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
            'show_quick_count' => ['required', 'boolean'],
            'helpdesk_contact' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'reset_logo' => ['nullable', 'boolean'],
        ], [
            'logo.image' => 'Berkas logo harus berupa gambar (PNG, JPG, WEBP, atau SVG).',
            'logo.max' => 'Ukuran berkas logo maksimal 2MB.',
        ]);

        $setting = ElectionSetting::current();
        $data = $request->only([
            'title',
            'organization_name',
            'announcement',
            'start_time',
            'end_time',
            'is_active',
            'show_quick_count',
            'helpdesk_contact',
        ]);

        if ($request->boolean('reset_logo')) {
            if ($setting->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($setting->logo_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($setting->logo_path);
            }
            $data['logo_path'] = null;
        } elseif ($request->hasFile('logo')) {
            if ($setting->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($setting->logo_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($setting->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('branding', 'public');
        }

        $setting->update($data);

        return back()->with('success', 'Pengaturan pemilihan dan identitas lembaga berhasil diperbarui.');
    }

    /**
     * Halaman manajemen sesi pemilihan.
     */
    public function sessions(): Response
    {
        $sessions = VotingSession::withCount('receipts')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'target_faculty' => $s->target_faculty,
                    'start_time' => $s->start_time ? $s->start_time->format('Y-m-d\TH:i') : null,
                    'start_time_formatted' => $s->start_time ? $s->start_time->format('d/m/Y H:i') : null,
                    'end_time' => $s->end_time ? $s->end_time->format('Y-m-d\TH:i') : null,
                    'end_time_formatted' => $s->end_time ? $s->end_time->format('d/m/Y H:i') : null,
                    'is_active' => (bool) $s->is_active,
                    'status_label' => $s->status_label,
                    'is_open_now' => $s->is_open_now,
                    'description' => $s->description,
                    'votes_count' => $s->receipts_count,
                ];
            });

        $faculties = Voter::distinct()->whereNotNull('faculty')->pluck('faculty')->filter()->values();

        return Inertia::render('Admin/Sesi', [
            'sessions' => $sessions,
            'faculties' => $faculties,
        ]);
    }

    /**
     * Simpan sesi pemilihan baru.
     */
    public function storeSession(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_faculty' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Nama sesi pemilihan wajib diisi.',
            'start_time.required' => 'Waktu mulai sesi wajib diisi.',
            'end_time.required' => 'Waktu selesai sesi wajib diisi.',
            'end_time.after' => 'Waktu selesai harus lebih lambat dari waktu mulai.',
        ]);

        VotingSession::create([
            'name' => trim($request->name),
            'target_faculty' => $request->target_faculty ? trim($request->target_faculty) : null,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_active' => $request->is_active,
            'description' => $request->description ? trim($request->description) : null,
        ]);

        return back()->with('success', 'Sesi pemilihan baru berhasil ditambahkan.');
    }

    /**
     * Perbarui data sesi pemilihan.
     */
    public function updateSession(Request $request, VotingSession $session): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_faculty' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Nama sesi pemilihan wajib diisi.',
            'start_time.required' => 'Waktu mulai sesi wajib diisi.',
            'end_time.required' => 'Waktu selesai sesi wajib diisi.',
            'end_time.after' => 'Waktu selesai harus lebih lambat dari waktu mulai.',
        ]);

        $session->update([
            'name' => trim($request->name),
            'target_faculty' => $request->target_faculty ? trim($request->target_faculty) : null,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_active' => $request->is_active,
            'description' => $request->description ? trim($request->description) : null,
        ]);

        return back()->with('success', 'Data sesi pemilihan berhasil diperbarui.');
    }

    /**
     * Toggle status aktif/nonaktif sesi pemilihan.
     */
    public function toggleSession(VotingSession $session): RedirectResponse
    {
        $session->update([
            'is_active' => ! $session->is_active,
        ]);

        $statusText = $session->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Sesi '{$session->name}' berhasil {$statusText}.");
    }

    /**
     * Hapus sesi pemilihan.
     */
    public function destroySession(VotingSession $session): RedirectResponse
    {
        $sessionName = $session->name;
        $session->delete();

        return back()->with('success', "Sesi '{$sessionName}' berhasil dihapus.");
    }

    /**
     * Halaman manajemen master data Jurusan & Program Studi.
     */
    public function departments(): Response
    {
        $departments = Department::orderBy('faculty_name', 'asc')
            ->orderBy('study_program', 'asc')
            ->get();

        $faculties = Department::distinct()->orderBy('faculty_name', 'asc')->pluck('faculty_name')->toArray();

        return Inertia::render('Admin/Jurusan', [
            'departments' => $departments,
            'existingFaculties' => $faculties,
            'stats' => [
                'total_programs' => $departments->count(),
                'total_faculties' => count($faculties),
                'active_count' => $departments->where('is_active', true)->count(),
            ],
        ]);
    }

    /**
     * Simpan Jurusan & Prodi baru.
     */
    public function storeDepartment(Request $request): RedirectResponse
    {
        $request->validate([
            'faculty_name' => ['required', 'string', 'max:255'],
            'study_program' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ], [
            'faculty_name.required' => 'Nama Jurusan wajib diisi.',
            'study_program.required' => 'Nama Program Studi wajib diisi.',
        ]);

        $exists = Department::where('faculty_name', trim($request->faculty_name))
            ->where('study_program', trim($request->study_program))
            ->exists();

        if ($exists) {
            return back()->withErrors(['study_program' => 'Program studi ini sudah terdaftar di bawah jurusan tersebut.']);
        }

        Department::create([
            'faculty_name' => trim($request->faculty_name),
            'study_program' => trim($request->study_program),
            'is_active' => $request->is_active,
        ]);

        return back()->with('success', 'Jurusan & Program Studi baru berhasil ditambahkan.');
    }

    /**
     * Perbarui data Jurusan & Prodi.
     */
    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $request->validate([
            'faculty_name' => ['required', 'string', 'max:255'],
            'study_program' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ], [
            'faculty_name.required' => 'Nama Jurusan wajib diisi.',
            'study_program.required' => 'Nama Program Studi wajib diisi.',
        ]);

        $exists = Department::where('faculty_name', trim($request->faculty_name))
            ->where('study_program', trim($request->study_program))
            ->where('id', '!=', $department->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['study_program' => 'Program studi ini sudah terdaftar di bawah jurusan tersebut.']);
        }

        $department->update([
            'faculty_name' => trim($request->faculty_name),
            'study_program' => trim($request->study_program),
            'is_active' => $request->is_active,
        ]);

        return back()->with('success', 'Data Jurusan & Program Studi berhasil diperbarui.');
    }

    /**
     * Toggle status aktif/nonaktif Jurusan & Prodi.
     */
    public function toggleDepartment(Department $department): RedirectResponse
    {
        $department->update([
            'is_active' => ! $department->is_active,
        ]);

        $statusText = $department->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Prodi '{$department->study_program}' berhasil {$statusText}.");
    }

    /**
     * Hapus Jurusan & Prodi.
     */
    public function destroyDepartment(Department $department): RedirectResponse
    {
        $name = $department->study_program;
        $department->delete();

        return back()->with('success', "Program studi '{$name}' berhasil dihapus.");
    }
}
