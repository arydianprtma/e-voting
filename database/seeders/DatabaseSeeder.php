<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Akun Administrator Panitia Pemilihan
        User::firstOrCreate(
            ['email' => 'admin@kampus.ac.id'],
            [
                'name' => 'Panitia KPU Mahasiswa',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Pengaturan Awal Pemilihan
        ElectionSetting::firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Pemilihan Raya Mahasiswa 2026/2027',
                'organization_name' => 'Komisi Pemilihan Umum Mahasiswa',
                'announcement' => 'Selamat datang di bilik suara digital resmi Pemira. Silakan masukkan NIM dan password akun Anda untuk menggunakan hak suara.',
                'start_time' => now()->subDay(),
                'end_time' => now()->addDays(2),
                'is_active' => true,
                'show_quick_count' => true,
            ]
        );

        // Kandidat Pasangan Calon (Paslon)
        if (Candidate::count() === 0) {
            Candidate::create([
                'candidate_number' => 1,
                'chairman_name' => 'Muhammad Fajar Pratama',
                'deputy_name' => 'Siti Nur Aisyah',
                'faculty_info' => 'Jurusan Keperawatan & Jurusan Kebidanan',
                'vision' => 'Mewujudkan DEMA Polkesmar yang kolaboratif, transparan, dan berdampak nyata bagi seluruh civitas akademika kesehatan.',
                'missions' => [
                    'Membangun kanal advokasi mahasiswa Polkesmar yang cepat tanggap, terbuka, dan berkeadilan.',
                    'Meningkatkan sinergi riset kesehatan, pengabdian masyarakat, dan pengembangan kompetensi klinis mahasiswa.',
                    'Menjamin keterbukaan informasi dan akuntabilitas program kerja DEMA secara berkala.',
                ],
                'photo_path' => null,
                'votes_count' => 0,
            ]);

            Candidate::create([
                'candidate_number' => 2,
                'chairman_name' => 'Dimas Arya Wicaksono',
                'deputy_name' => 'Zahra Amanda Putri',
                'faculty_info' => 'Jurusan Gizi & Jurusan Teknik Radiodiagnostik dan Radioterapi (TRR)',
                'vision' => 'Menjadikan DEMA Polkesmar sebagai ruang inovasi inklusif yang mandiri, adaptif, dan berorientasi karya untuk tenaga kesehatan masa depan.',
                'missions' => [
                    'Optimalisasi layanan digital kemahasiswaan berbasis kebutuhan riil civitas akademika Polkesmar.',
                    'Penguatan kemitraan strategis dengan fasilitas kesehatan, institusi pemerintah, dan organisasi profesi.',
                    'Pengembangan ruang kreasi minat bakat, riset kesehatan terapan, dan kepemimpinan berkarakter.',
                ],
                'photo_path' => null,
                'votes_count' => 0,
            ]);
        }

        // Data Contoh Pemilih Tetap (DPT)
        $sampleVoters = [
            ['student_id' => '20240101', 'name' => 'Ahmad Fauzi', 'faculty' => 'Fakultas Teknik', 'study_program' => 'Teknik Informatika'],
            ['student_id' => '20240102', 'name' => 'Bunga Citra Lestari', 'faculty' => 'Fakultas Ekonomi dan Bisnis', 'study_program' => 'Manajemen'],
            ['student_id' => '20240103', 'name' => 'Citra Dewi Permata', 'faculty' => 'Fakultas Ilmu Komputer', 'study_program' => 'Sistem Informasi'],
            ['student_id' => '20240104', 'name' => 'Dwi Handayani', 'faculty' => 'Fakultas Kedokteran', 'study_program' => 'Pendidikan Dokter'],
            ['student_id' => '20240105', 'name' => 'Eko Prasetyo', 'faculty' => 'Fakultas Hukum', 'study_program' => 'Ilmu Hukum'],
        ];

        foreach ($sampleVoters as $v) {
            Voter::firstOrCreate(
                ['student_id' => $v['student_id']],
                [
                    'name' => $v['name'],
                    'faculty' => $v['faculty'],
                    'study_program' => $v['study_program'],
                    'birth_date' => '2004-01-01',
                    'password' => null,
                    'token' => null,
                    'has_voted' => false,
                    'voted_at' => null,
                ]
            );
        }

        // Sesi Pemilihan Awal (Voting Sessions)
        if (\App\Models\VotingSession::count() === 0) {
            \App\Models\VotingSession::create([
                'name' => 'Sesi 1: Gelombang Pagi (Sesi Utama DPT)',
                'target_faculty' => null,
                'start_time' => now()->subHours(2),
                'end_time' => now()->addHours(8),
                'is_active' => true,
                'description' => 'Sesi pemilihan terbuka bagi seluruh mahasiswa terdaftar di DPT.',
            ]);

            \App\Models\VotingSession::create([
                'name' => 'Sesi 2: Gelombang Khusus Mahasiswa Klinis / RS',
                'target_faculty' => 'Jurusan Keperawatan',
                'start_time' => now()->addHours(8),
                'end_time' => now()->addHours(14),
                'is_active' => true,
                'description' => 'Sesi fleksibel bagi mahasiswa yang sedang bertugas praktik klinik di rumah sakit.',
            ]);
        }

        // Master Data Jurusan & Program Studi Polkesmar (Diatur oleh Admin)
        if (\App\Models\Department::count() === 0) {
            $initialDepartments = [
                ['faculty_name' => 'Jurusan Keperawatan', 'study_program' => 'D3 Keperawatan Semarang', 'code' => 'KPR-D3-SMG'],
                ['faculty_name' => 'Jurusan Keperawatan', 'study_program' => 'Sarjana Terapan Keperawatan Semarang', 'code' => 'KPR-STR-SMG'],
                ['faculty_name' => 'Jurusan Keperawatan', 'study_program' => 'D3 Keperawatan Purwokerto', 'code' => 'KPR-D3-PWT'],
                ['faculty_name' => 'Jurusan Keperawatan', 'study_program' => 'D3 Keperawatan Magelang', 'code' => 'KPR-D3-MGL'],
                ['faculty_name' => 'Jurusan Keperawatan', 'study_program' => 'D3 Keperawatan Blora', 'code' => 'KPR-D3-BLA'],
                ['faculty_name' => 'Jurusan Kebidanan', 'study_program' => 'D3 Kebidanan Semarang', 'code' => 'KBD-D3-SMG'],
                ['faculty_name' => 'Jurusan Kebidanan', 'study_program' => 'Sarjana Terapan Kebidanan Semarang', 'code' => 'KBD-STR-SMG'],
                ['faculty_name' => 'Jurusan Kebidanan', 'study_program' => 'D3 Kebidanan Magelang', 'code' => 'KBD-D3-MGL'],
                ['faculty_name' => 'Jurusan Kebidanan', 'study_program' => 'D3 Kebidanan Purwokerto', 'code' => 'KBD-D3-PWT'],
                ['faculty_name' => 'Jurusan Gizi', 'study_program' => 'D3 Gizi', 'code' => 'GZ-D3'],
                ['faculty_name' => 'Jurusan Gizi', 'study_program' => 'Sarjana Terapan Gizi dan Dietetika', 'code' => 'GZ-STR'],
                ['faculty_name' => 'Jurusan Teknik Radiodiagnostik dan Radioterapi (TRR)', 'study_program' => 'D3 TRR Semarang', 'code' => 'TRR-D3-SMG'],
                ['faculty_name' => 'Jurusan Teknik Radiodiagnostik dan Radioterapi (TRR)', 'study_program' => 'Sarjana Terapan TRR', 'code' => 'TRR-STR'],
                ['faculty_name' => 'Jurusan Teknik Radiodiagnostik dan Radioterapi (TRR)', 'study_program' => 'D3 TRR Purwokerto', 'code' => 'TRR-D3-PWT'],
                ['faculty_name' => 'Jurusan Teknologi Laboratorium Medis (TLM)', 'study_program' => 'D3 TLM', 'code' => 'TLM-D3'],
                ['faculty_name' => 'Jurusan Teknologi Laboratorium Medis (TLM)', 'study_program' => 'Sarjana Terapan TLM', 'code' => 'TLM-STR'],
                ['faculty_name' => 'Jurusan Kesehatan Lingkungan', 'study_program' => 'D3 Sanitasi Purwokerto', 'code' => 'KL-D3'],
                ['faculty_name' => 'Jurusan Kesehatan Lingkungan', 'study_program' => 'Sarjana Terapan Sanitasi Lingkungan', 'code' => 'KL-STR'],
                ['faculty_name' => 'Jurusan Kesehatan Gigi', 'study_program' => 'D3 Kesehatan Gigi', 'code' => 'KG-D3'],
                ['faculty_name' => 'Jurusan Kesehatan Gigi', 'study_program' => 'Sarjana Terapan Terapis Gigi dan Mulut', 'code' => 'KG-STR'],
            ];

            foreach ($initialDepartments as $dept) {
                \App\Models\Department::create($dept);
            }
        }
    }
}
