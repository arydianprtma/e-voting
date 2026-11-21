<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\ElectionSetting;
use App\Models\RegistrationAudit;
use App\Models\VoteReceipt;
use App\Models\Voter;
use App\Models\VotingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VotingController extends Controller
{
    public static array $polkesmarFaculties = [
        'Jurusan Keperawatan',
        'Jurusan Kebidanan',
        'Jurusan Gizi',
        'Jurusan Teknik Radiodiagnostik dan Radioterapi (TRR)',
        'Jurusan Teknologi Laboratorium Medis (TLM)',
        'Jurusan Kesehatan Lingkungan',
        'Jurusan Kesehatan Gigi',
    ];
    /**
     * Tampilkan halaman utama / portal pemilih.
     */
    public function showLanding(): Response
    {
        $setting = ElectionSetting::current();
        $totalCandidates = Candidate::count();
        $totalVoters = Voter::count();
        $totalVoted = Voter::where('has_voted', true)->count();

        $activeSession = VotingSession::getCurrentRunningSession();
        $sessions = VotingSession::orderBy('start_time', 'asc')->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'target_faculty' => $s->target_faculty,
                'start_time' => $s->start_time ? $s->start_time->format('d/m/Y H:i') : null,
                'end_time' => $s->end_time ? $s->end_time->format('H:i') : null,
                'status_label' => $s->status_label,
                'is_open_now' => $s->is_open_now,
            ];
        });

        return Inertia::render('Portal/Beranda', [
            'setting' => $setting,
            'activeSession' => $activeSession ? [
                'id' => $activeSession->id,
                'name' => $activeSession->name,
                'target_faculty' => $activeSession->target_faculty,
                'start_time' => $activeSession->start_time->format('H:i'),
                'end_time' => $activeSession->end_time->format('H:i'),
            ] : null,
            'sessions' => $sessions,
            'summary' => [
                'total_candidates' => $totalCandidates,
                'total_voters' => $totalVoters,
                'total_voted' => $totalVoted,
                'participation_rate' => $totalVoters > 0 ? round(($totalVoted / $totalVoters) * 100, 1) : 0,
            ],
        ]);
    }

    /**
     * Tampilkan halaman registrasi akun pemilih.
     */
    public function showRegister(): Response
    {
        $setting = ElectionSetting::current();
        $activeSession = VotingSession::getCurrentRunningSession();

        // Jurusan dan Prodi yang wajib diisi/dikelola terlebih dahulu oleh admin
        $voterFaculties = Voter::whereNotNull('faculty')->where('faculty', '!=', '')->distinct()->pluck('faculty')->toArray();
        $deptFaculties = Department::getActiveFaculties();
        $activeFaculties = array_values(array_unique(array_filter(array_merge($voterFaculties, $deptFaculties))));
        sort($activeFaculties);

        $departmentsGrouped = Department::getGroupedByFaculty();
        $voterGrouped = Voter::whereNotNull('faculty')
            ->whereNotNull('study_program')
            ->where('faculty', '!=', '')
            ->where('study_program', '!=', '')
            ->select('faculty', 'study_program')
            ->distinct()
            ->get()
            ->groupBy('faculty')
            ->map(fn ($group) => $group->pluck('study_program')->unique()->values()->toArray())
            ->toArray();

        foreach ($voterGrouped as $fac => $prodis) {
            if (!isset($departmentsGrouped[$fac])) {
                $departmentsGrouped[$fac] = $prodis;
            } else {
                $departmentsGrouped[$fac] = array_values(array_unique(array_merge($departmentsGrouped[$fac], $prodis)));
            }
        }

        return Inertia::render('Portal/Register', [
            'setting' => $setting,
            'faculties' => $activeFaculties,
            'departmentsGrouped' => $departmentsGrouped,
            'activeSession' => $activeSession ? [
                'name' => $activeSession->name,
                'target_faculty' => $activeSession->target_faculty,
                'time_range' => $activeSession->start_time->format('H:i') . ' - ' . $activeSession->end_time->format('H:i') . ' WIB',
            ] : null,
        ]);
    }

    /**
     * Endpoint AJAX / API untuk pengecekan NIM secara real-time.
     */
    public function checkNim(Request $request): JsonResponse
    {
        $rawNim = (string) $request->input('student_id', '');
        $nim = strtoupper(trim(str_replace(' ', '', $rawNim)));

        if (empty($nim)) {
            return response()->json([
                'status' => 'empty',
                'valid' => true,
                'message' => '',
            ]);
        }

        $voter = Voter::where('student_id', $nim)->first();

        if (! $voter) {
            return response()->json([
                'status' => 'available',
                'valid' => true,
                'is_dpt' => false,
                'message' => 'NIM tersedia untuk pendaftaran baru.',
            ]);
        }

        if ($voter->has_password) {
            return response()->json([
                'status' => 'registered',
                'valid' => false,
                'is_dpt' => true,
                'name' => $voter->name,
                'message' => "NIM sudah terdaftar atas nama {$voter->name}. Silakan langsung login.",
            ]);
        }

        // NIM ada di DPT tapi belum membuat password (Aktivasi Akun)
        return response()->json([
            'status' => 'dpt_found',
            'valid' => true,
            'is_dpt' => true,
            'name' => $voter->name,
            'faculty' => $voter->faculty,
            'study_program' => $voter->study_program,
            'message' => "NIM terverifikasi di DPT atas nama {$voter->name}. Buat password untuk mengaktifkan akun Anda.",
        ]);
    }

    /**
     * Proses pendaftaran / aktivasi akun pemilih.
     */
    public function submitRegister(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'faculty' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'student_id.required' => 'Nomor Induk Mahasiswa (NIM) wajib diisi.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'faculty.required' => 'Jurusan / Program Studi wajib dipilih.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $nim = strtoupper(trim(str_replace(' ', '', $request->student_id)));

        $voter = Voter::where('student_id', $nim)->first();

        // Kasus 1: Sudah pernah daftar dan punya password
        if ($voter && $voter->has_password) {
            RegistrationAudit::log($nim, $request->name, $request->faculty, 'rejected', 'Duplicate Registration: Akun sudah memiliki password');
            return back()->withErrors([
                'student_id' => 'NIM ini sudah memiliki akun terdaftar. Silakan langsung masuk menggunakan password Anda.',
            ]);
        }

        // Kasus 2: Ada di DPT tapi belum aktivasi password
        if ($voter && ! $voter->has_password) {
            $voter->update([
                'name' => $voter->name ?: trim($request->name),
                'faculty' => $voter->faculty ?: trim($request->faculty),
                'study_program' => $voter->study_program ?: ($request->study_program ? trim($request->study_program) : null),
                'birth_date' => $request->birth_date ?: $voter->birth_date,
                'password' => $request->password,
            ]);

            RegistrationAudit::log($nim, $voter->name, $voter->faculty, 'accepted', 'Aktivasi akun DPT dengan password berhasil');

            // Cek sesi aktif untuk jurusan ini
            $activeSession = VotingSession::getActiveSessionForFaculty($voter->faculty);
            session([
                'authenticated_voter_id' => $voter->id,
                'authenticated_voter_nim' => $voter->student_id,
                'authenticated_voter_name' => $voter->name,
                'authenticated_voter_faculty' => $voter->faculty,
                'authenticated_session_id' => $activeSession?->id,
                'authenticated_session_name' => $activeSession?->name,
            ]);

            return redirect()->route('booth.show')->with('success', 'Aktivasi akun berhasil! Anda langsung diarahkan ke bilik suara.');
        }

        // Kasus 3: Pendaftaran mahasiswa baru (Open Registration)
        $newVoter = Voter::create([
            'student_id' => $nim,
            'name' => trim($request->name),
            'faculty' => trim($request->faculty),
            'study_program' => $request->study_program ? trim($request->study_program) : null,
            'birth_date' => $request->birth_date,
            'password' => $request->password,
            'token' => null,
            'has_voted' => false,
        ]);

        RegistrationAudit::log($nim, $newVoter->name, $newVoter->faculty, 'accepted', 'Registrasi mandiri mahasiswa baru berhasil');

        // Cek sesi aktif untuk jurusan ini
        $activeSession = VotingSession::getActiveSessionForFaculty($newVoter->faculty);
        session([
            'authenticated_voter_id' => $newVoter->id,
            'authenticated_voter_nim' => $newVoter->student_id,
            'authenticated_voter_name' => $newVoter->name,
            'authenticated_voter_faculty' => $newVoter->faculty,
            'authenticated_session_id' => $activeSession?->id,
            'authenticated_session_name' => $activeSession?->name,
        ]);

        return redirect()->route('booth.show')->with('success', 'Pendaftaran akun berhasil! Anda langsung diarahkan ke bilik suara.');
    }

    /**
     * Proses masuk pemilih menggunakan NIM dan Password.
     */
    public function loginVoter(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'student_id.required' => 'Nomor Induk Mahasiswa (NIM) wajib diisi.',
            'password.required' => 'Password akun wajib diisi.',
        ]);

        $setting = ElectionSetting::current();

        if (! $setting->is_active) {
            return back()->withErrors([
                'student_id' => 'Sistem bilik suara sedang dinonaktifkan oleh panitia pemilihan.',
            ]);
        }

        $nim = strtoupper(trim(str_replace(' ', '', $request->student_id)));
        $voter = Voter::where('student_id', $nim)->first();

        if (! $voter) {
            return back()->withErrors([
                'student_id' => 'NIM tidak ditemukan. Silakan registrasi akun terlebih dahulu.',
            ]);
        }

        // Wajibkan mahasiswa telah mengaktifkan password akun terlebih dahulu
        if (! $voter->password) {
            return back()->withErrors([
                'password' => 'Akun Anda belum memiliki password aktif. Silakan lakukan aktivasi akun dan verifikasi identitas di menu Registrasi Akun.',
            ]);
        }

        if (! Hash::check($request->password, $voter->password)) {
            return back()->withErrors([
                'password' => 'Password yang Anda masukkan salah. Periksa kembali atau hubungi panitia.',
            ]);
        }

        if ($voter->has_voted) {
            return back()->withErrors([
                'student_id' => 'Hak suara untuk NIM ini telah digunakan pada ' . ($voter->voted_at ? $voter->voted_at->format('d/m/Y H:i') : '') . ' WIB.',
            ]);
        }

        // Cek sesi pemilihan aktif untuk jurusan pemilih
        $sessionError = $this->validateVotingSession($voter);
        if ($sessionError) {
            return back()->withErrors([
                'student_id' => $sessionError,
            ]);
        }

        $activeSession = VotingSession::getActiveSessionForFaculty($voter->faculty);

        // Simpan sesi autentikasi pemilih
        session([
            'authenticated_voter_id' => $voter->id,
            'authenticated_voter_nim' => $voter->student_id,
            'authenticated_voter_name' => $voter->name,
            'authenticated_voter_faculty' => $voter->faculty,
            'authenticated_session_id' => $activeSession?->id,
            'authenticated_session_name' => $activeSession?->name,
        ]);

        return redirect()->route('booth.show');
    }

    /**
     * Tampilkan halaman klaim token mandiri.
     */
    public function showClaimToken(): Response
    {
        $setting = ElectionSetting::current();
        $activeSession = VotingSession::getCurrentRunningSession();

        return Inertia::render('Portal/KlaimToken', [
            'setting' => $setting,
            'activeSession' => $activeSession ? [
                'name' => $activeSession->name,
                'target_faculty' => $activeSession->target_faculty,
                'time_range' => $activeSession->start_time->format('H:i') . ' - ' . $activeSession->end_time->format('H:i') . ' WIB',
            ] : null,
        ]);
    }

    /**
     * Helper validasi jadwal sesi pemilihan berdasarkan data pemilih.
     */
    private function validateVotingSession(?Voter $voter): ?string
    {
        if (VotingSession::count() === 0) {
            return null;
        }

        $activeSession = VotingSession::getActiveSessionForFaculty($voter?->faculty);

        if ($activeSession) {
            return null;
        }

        $upcoming = VotingSession::active()
            ->where('start_time', '>', now())
            ->where(function ($q) use ($voter) {
                $q->whereNull('target_faculty')
                    ->orWhere('target_faculty', '')
                    ->orWhere('target_faculty', $voter?->faculty);
            })
            ->orderBy('start_time', 'asc')
            ->first();

        if ($upcoming) {
            $facultyNote = $upcoming->target_faculty ? " (khusus {$upcoming->target_faculty})" : "";
            return "Sesi pemilihan Anda belum dibuka{$facultyNote}. Jadwal sesi Anda: {$upcoming->name} ({$upcoming->start_time->format('d/m/Y H:i')} - {$upcoming->end_time->format('H:i')} WIB).";
        }

        return 'Saat ini tidak ada sesi pemilihan aktif untuk kategori atau jurusan Anda.';
    }

    /**
     * Proses verifikasi data rahasia mahasiswa untuk klaim token mandiri.
     */
    public function submitClaimToken(Request $request): Response|RedirectResponse
    {
        $request->validate([
            'student_id' => ['required', 'string'],
            'birth_date' => ['required', 'date'],
        ], [
            'student_id.required' => 'Nomor Induk Mahasiswa (NIM) wajib diisi.',
            'birth_date.required' => 'Tanggal lahir wajib diisi sebagai data verifikasi rahasia.',
            'birth_date.date' => 'Format tanggal lahir tidak valid.',
        ]);

        $voter = Voter::where('student_id', trim($request->student_id))
            ->whereDate('birth_date', $request->birth_date)
            ->first();

        if (! $voter) {
            return back()->withErrors([
                'claim_error' => 'Data NIM atau Tanggal Lahir tidak cocok dengan DPT. Pastikan data sesuai data administrasi kampus.',
            ]);
        }

        if ($voter->has_voted) {
            return back()->withErrors([
                'claim_error' => 'Hak suara untuk NIM ini telah digunakan pada ' . ($voter->voted_at ? $voter->voted_at->format('d/m/Y H:i') : '') . ' WIB. Token tidak dapat diklaim kembali.',
            ]);
        }

        // Cek sesi pemilihan aktif untuk jurusan pemilih
        $sessionError = $this->validateVotingSession($voter);
        if ($sessionError) {
            return back()->withErrors([
                'claim_error' => $sessionError,
            ]);
        }

        $activeSession = VotingSession::getActiveSessionForFaculty($voter->faculty);

        // Catat timestamp klaim token jika belum pernah diklaim
        if (! $voter->token_claimed_at) {
            $voter->update(['token_claimed_at' => now()]);
        }

        // Simpan sesi autentikasi pemilih agar bisa langsung masuk bilik suara sekali klik
        session([
            'authenticated_voter_id' => $voter->id,
            'authenticated_voter_nim' => $voter->student_id,
            'authenticated_voter_name' => $voter->name,
            'authenticated_voter_faculty' => $voter->faculty,
            'authenticated_session_id' => $activeSession?->id,
            'authenticated_session_name' => $activeSession?->name,
        ]);

        return Inertia::render('Portal/KlaimToken', [
            'setting' => ElectionSetting::current(),
            'activeSession' => $activeSession ? [
                'name' => $activeSession->name,
                'target_faculty' => $activeSession->target_faculty,
                'time_range' => $activeSession->start_time->format('H:i') . ' - ' . $activeSession->end_time->format('H:i') . ' WIB',
            ] : null,
            'claimed' => [
                'name' => $voter->name,
                'student_id' => $voter->student_id,
                'faculty' => $voter->faculty,
                'token' => $voter->token,
            ],
        ]);
    }

    /**
     * Verifikasi identitas pemilih (NIM dan Token).
     */
    public function verifyToken(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => ['required', 'string'],
            'token' => ['required', 'string'],
        ], [
            'student_id.required' => 'Nomor Induk Mahasiswa (NIM) wajib diisi.',
            'token.required' => 'Token pemilihan wajib diisi.',
        ]);

        $setting = ElectionSetting::current();

        if (! $setting->is_active) {
            return back()->withErrors([
                'token' => 'Sistem bilik suara sedang dinonaktifkan oleh panitia pemilihan.',
            ]);
        }

        if ($setting->start_time && now()->lt($setting->start_time)) {
            return back()->withErrors([
                'token' => 'Bilik suara belum dibuka. Jadwal pemilihan: ' . $setting->start_time->format('d/m/Y H:i') . ' WIB.',
            ]);
        }

        if ($setting->end_time && now()->gt($setting->end_time)) {
            return back()->withErrors([
                'token' => 'Bilik suara telah ditutup pada ' . $setting->end_time->format('d/m/Y H:i') . ' WIB.',
            ]);
        }

        $voter = Voter::where('student_id', trim($request->student_id))
            ->where('token', trim($request->token))
            ->first();

        if (! $voter) {
            return back()->withErrors([
                'token' => 'Kombinasi NIM dan Token tidak valid. Periksa kembali kartu token Anda.',
            ]);
        }

        if ($voter->has_voted) {
            return back()->withErrors([
                'token' => 'Hak suara untuk NIM ini telah digunakan pada ' . ($voter->voted_at ? $voter->voted_at->format('d/m/Y H:i') : '') . ' WIB.',
            ]);
        }

        // Cek sesi pemilihan aktif untuk jurusan pemilih
        $sessionError = $this->validateVotingSession($voter);
        if ($sessionError) {
            return back()->withErrors([
                'token' => $sessionError,
            ]);
        }

        $activeSession = VotingSession::getActiveSessionForFaculty($voter->faculty);

        // Simpan sesi verifikasi pemilih
        session([
            'authenticated_voter_id' => $voter->id,
            'authenticated_voter_nim' => $voter->student_id,
            'authenticated_voter_name' => $voter->name,
            'authenticated_voter_faculty' => $voter->faculty,
            'authenticated_session_id' => $activeSession?->id,
            'authenticated_session_name' => $activeSession?->name,
        ]);

        return redirect()->route('booth.show');
    }

    /**
     * Tampilkan antarmuka bilik suara digital.
     */
    public function showBooth(): Response|RedirectResponse
    {
        $voterId = session('authenticated_voter_id');

        if (! $voterId) {
            return redirect()->route('home')->withErrors([
                'student_id' => 'Sesi Anda telah kedaluwarsa. Silakan masukkan NIM dan password kembali.',
            ]);
        }

        $voter = Voter::find($voterId);

        if (! $voter || $voter->has_voted) {
            session()->forget(['authenticated_voter_id', 'authenticated_voter_nim', 'authenticated_voter_name', 'authenticated_voter_faculty']);
            return redirect()->route('home')->withErrors([
                'student_id' => 'Hak suara untuk akun ini telah tercatat dan tidak dapat memilih kembali.',
            ]);
        }

        $candidates = Candidate::orderBy('candidate_number', 'asc')->get();
        $setting = ElectionSetting::current();
        $activeSession = VotingSession::getActiveSessionForFaculty($voter->faculty);

        return Inertia::render('Portal/BilikSuara', [
            'voter' => [
                'name' => $voter->name,
                'student_id' => $voter->student_id,
                'faculty' => $voter->faculty,
            ],
            'candidates' => $candidates,
            'setting' => $setting,
            'currentSession' => $activeSession ? [
                'name' => $activeSession->name,
                'target_faculty' => $activeSession->target_faculty,
                'time_range' => $activeSession->start_time->format('H:i') . ' - ' . $activeSession->end_time->format('H:i') . ' WIB',
            ] : null,
        ]);
    }

    /**
     * Kirimkan suara pemilih ke database secara aman (Atomic Transaction).
     */
    public function submitVote(Request $request): RedirectResponse
    {
        $request->validate([
            'candidate_id' => ['required', 'exists:candidates,id'],
        ], [
            'candidate_id.required' => 'Silakan pilih salah satu pasangan calon sebelum mengonfirmasi.',
            'candidate_id.exists' => 'Pasangan calon yang dipilih tidak valid.',
        ]);

        $voterId = session('authenticated_voter_id');
        $sessionId = session('authenticated_session_id');

        if (! $voterId) {
            return redirect()->route('home')->withErrors([
                'student_id' => 'Sesi pemilihan berakhir. Silakan masuk kembali.',
            ]);
        }

        // Validasi status sistem dan jadwal pemilihan aktif
        $setting = ElectionSetting::current();
        if (! $setting->is_active) {
            session()->forget([
                'authenticated_voter_id',
                'authenticated_voter_nim',
                'authenticated_voter_name',
                'authenticated_voter_faculty',
                'authenticated_session_id',
                'authenticated_session_name',
            ]);
            return redirect()->route('home')->withErrors([
                'student_id' => 'Sistem pemilihan sedang dinonaktifkan oleh panitia.',
            ]);
        }

        if ($setting->end_time && now()->gt($setting->end_time)) {
            session()->forget([
                'authenticated_voter_id',
                'authenticated_voter_nim',
                'authenticated_voter_name',
                'authenticated_voter_faculty',
                'authenticated_session_id',
                'authenticated_session_name',
            ]);
            return redirect()->route('home')->withErrors([
                'student_id' => 'Bilik suara telah ditutup pada ' . $setting->end_time->format('d/m/Y H:i') . ' WIB.',
            ]);
        }

        $voter = Voter::find($voterId);
        if (! $voter) {
            return redirect()->route('home')->withErrors([
                'student_id' => 'Data pemilih tidak ditemukan.',
            ]);
        }

        $sessionError = $this->validateVotingSession($voter);
        if ($sessionError) {
            session()->forget([
                'authenticated_voter_id',
                'authenticated_voter_nim',
                'authenticated_voter_name',
                'authenticated_voter_faculty',
                'authenticated_session_id',
                'authenticated_session_name',
            ]);
            return redirect()->route('home')->withErrors([
                'student_id' => $sessionError,
            ]);
        }

        $receiptCode = null;
        $votedTime = now();

        DB::transaction(function () use ($voterId, $sessionId, $request, $votedTime, &$receiptCode) {
            /** @var Voter $voter */
            $voter = Voter::where('id', $voterId)->lockForUpdate()->firstOrFail();

            if ($voter->has_voted) {
                throw new \Exception('Hak suara Anda telah digunakan sebelumnya.');
            }

            // 1. Perbarui status pemilih
            $voter->has_voted = true;
            $voter->voted_at = $votedTime;
            $voter->save();

            // 2. Tambah perolehan suara kandidat secara terpisah (tanpa relasi ke pemilih)
            Candidate::where('id', $request->candidate_id)
                ->lockForUpdate()
                ->increment('votes_count');

            // 3. Terbitkan tanda terima digital anonim dengan relasi sesi pemilihan
            $receiptCode = 'KPU-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
            $voterHash = hash('sha256', $voter->student_id . config('app.key'));

            VoteReceipt::create([
                'receipt_code' => $receiptCode,
                'session_id' => $sessionId,
                'voter_hash' => $voterHash,
                'voted_at' => $votedTime,
            ]);
        });

        // Bersihkan sesi pemilih
        session()->forget([
            'authenticated_voter_id',
            'authenticated_voter_nim',
            'authenticated_voter_name',
            'authenticated_voter_faculty',
            'authenticated_session_id',
            'authenticated_session_name',
        ]);

        // Simpan data receipt ke flash session
        session()->flash('vote_receipt', [
            'code' => $receiptCode,
            'voted_at' => $votedTime->format('d/m/Y H:i:s') . ' WIB',
        ]);

        return redirect()->route('receipt.show');
    }

    /**
     * Tampilkan tanda terima pemilihan (receipt).
     */
    public function showReceipt(): Response|RedirectResponse
    {
        $receipt = session('vote_receipt');

        if (! $receipt) {
            return redirect()->route('home');
        }

        $setting = ElectionSetting::current();

        return Inertia::render('Portal/BuktiSuara', [
            'receipt' => $receipt,
            'setting' => $setting,
        ]);
    }

    /**
     * Tampilkan halaman Quick Count hasil perolehan suara sementara.
     */
    public function showQuickCount(): Response
    {
        $setting = ElectionSetting::current();
        $candidates = Candidate::orderBy('candidate_number', 'asc')->get();
        $totalVoters = Voter::count();
        $totalVotes = Candidate::sum('votes_count');

        $chartData = $candidates->map(function ($c) use ($totalVotes) {
            $percentage = $totalVotes > 0 ? round(($c->votes_count / $totalVotes) * 100, 1) : 0;
            return [
                'id' => $c->id,
                'number' => $c->candidate_number,
                'label' => sprintf('Paslon %02d: %s', $c->candidate_number, $c->chairman_name),
                'chairman' => $c->chairman_name,
                'deputy' => $c->deputy_name,
                'faculty_info' => $c->faculty_info,
                'photo_path' => $c->photo_path,
                'votes' => (int) $c->votes_count,
                'percentage' => $percentage,
            ];
        });

        $remainingVotes = max(0, $totalVoters - (int) $totalVotes);

        $sessionsBreakdown = VotingSession::withCount('receipts')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'target_faculty' => $s->target_faculty,
                    'votes_count' => $s->receipts_count,
                    'status_label' => $s->status_label,
                    'time_range' => $s->start_time->format('d/m H:i') . ' - ' . $s->end_time->format('H:i') . ' WIB',
                ];
            });

        return Inertia::render('Portal/QuickCount', [
            'setting' => $setting,
            'candidates' => $chartData,
            'sessions' => $sessionsBreakdown,
            'summary' => [
                'total_voters' => $totalVoters,
                'total_votes' => (int) $totalVotes,
                'remaining_votes' => $remainingVotes,
                'participation_percentage' => $totalVoters > 0 ? round(($totalVotes / $totalVoters) * 100, 1) : 0,
                'last_updated' => now()->format('d/m/Y H:i:s'),
            ],
        ]);
    }
}
