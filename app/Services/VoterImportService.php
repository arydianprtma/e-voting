<?php

namespace App\Services;

use App\Models\Voter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class VoterImportService
{
    /**
     * Parsing berkas yang diunggah (Excel, CSV, PDF, TXT)
     */
    public function importFromFile(
        UploadedFile $file,
        ?string $defaultFaculty = null,
        ?string $defaultStudyProgram = null
    ): array {
        $extension = strtolower($file->getClientOriginalExtension());
        $extractedRows = [];

        if (in_array($extension, ['xlsx', 'xls'])) {
            $extractedRows = $this->parseSpreadsheet($file->getRealPath(), $defaultFaculty, $defaultStudyProgram);
        } elseif ($extension === 'csv') {
            $extractedRows = $this->parseCsv($file->getRealPath(), $defaultFaculty, $defaultStudyProgram);
        } elseif ($extension === 'pdf') {
            $extractedRows = $this->parsePdf($file->getRealPath(), $defaultFaculty, $defaultStudyProgram);
        } elseif (in_array($extension, ['txt', 'text'])) {
            $extractedRows = $this->parseText(file_get_contents($file->getRealPath()), $defaultFaculty, $defaultStudyProgram);
        } else {
            // Coba deteksi konten jika ekstensi tidak standar
            $mime = $file->getMimeType();
            if (str_contains($mime, 'pdf')) {
                $extractedRows = $this->parsePdf($file->getRealPath(), $defaultFaculty, $defaultStudyProgram);
            } else {
                $extractedRows = $this->parseText(file_get_contents($file->getRealPath()), $defaultFaculty, $defaultStudyProgram);
            }
        }

        return $this->saveRowsToDatabase($extractedRows, $defaultFaculty, $defaultStudyProgram);
    }

    /**
     * Parsing teks mentah / hasil copy-paste tabel
     */
    public function importFromRawText(
        string $rawText,
        ?string $defaultFaculty = null,
        ?string $defaultStudyProgram = null
    ): array {
        $extractedRows = $this->parseText($rawText, $defaultFaculty, $defaultStudyProgram);
        return $this->saveRowsToDatabase($extractedRows, $defaultFaculty, $defaultStudyProgram);
    }

    /**
     * Parse Excel (.xlsx, .xls) menggunakan PhpSpreadsheet
     */
    public function parseSpreadsheet(
        string $filePath,
        ?string $defaultFaculty = null,
        ?string $defaultStudyProgram = null
    ): array {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        return $this->parseRows($rows, $defaultFaculty, $defaultStudyProgram);
    }

    /**
     * Parse CSV file
     */
    public function parseCsv(
        string $filePath,
        ?string $defaultFaculty = null,
        ?string $defaultStudyProgram = null
    ): array {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 2000, ',')) !== false) {
                if (count($data) === 1 && str_contains($data[0], ';')) {
                    $data = explode(';', $data[0]);
                } elseif (count($data) === 1 && str_contains($data[0], "\t")) {
                    $data = explode("\t", $data[0]);
                }
                $rows[] = $data;
            }
            fclose($handle);
        }

        return $this->parseRows($rows, $defaultFaculty, $defaultStudyProgram);
    }

    /**
     * Parse PDF menggunakan Smalot\PdfParser
     */
    public function parsePdf(
        string $filePath,
        ?string $defaultFaculty = null,
        ?string $defaultStudyProgram = null
    ): array {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        return $this->parseText($text, $defaultFaculty, $defaultStudyProgram);
    }

    /**
     * Parse teks baris-per-baris (mendukung format CSV, Tab-separated, Markdown table, teks OCR PDF)
     */
    public function parseText(
        string $text,
        ?string $defaultFaculty = null,
        ?string $defaultStudyProgram = null
    ): array {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $structuredRows = [];
        $unstructuredLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            // Jika baris dipisahkan Tab (hasil copy paste Excel / Google Sheets)
            if (str_contains($trimmed, "\t")) {
                $structuredRows[] = explode("\t", $trimmed);
            }
            // Jika baris berupa tabel Markdown / pipa
            elseif (str_contains($trimmed, '|')) {
                $cells = array_values(array_filter(array_map('trim', explode('|', $trimmed)), fn ($v) => $v !== ''));
                if (count($cells) >= 2) {
                    $structuredRows[] = $cells;
                }
            }
            // Jika baris berupa data CSV (dipisahkan koma atau titik-koma)
            elseif (str_contains($trimmed, ',') || str_contains($trimmed, ';')) {
                $delimiter = str_contains($trimmed, ';') ? ';' : ',';
                $cells = str_getcsv($trimmed, $delimiter, '"', '\\');
                if (count($cells) >= 2) {
                    $structuredRows[] = $cells;
                } else {
                    $unstructuredLines[] = $trimmed;
                }
            }
            // Teks biasa / OCR tanpa pembatas khusus
            else {
                $unstructuredLines[] = $trimmed;
            }
        }

        $results = [];

        // 1. Proses baris terstruktur (tabel / spreadsheet / CSV / Tab)
        if (!empty($structuredRows)) {
            $results = array_merge($results, $this->parseRows($structuredRows, $defaultFaculty, $defaultStudyProgram));
        }

        // 2. Proses baris tak terstruktur (spasi / teks naratif / OCR PDF)
        if (!empty($unstructuredLines)) {
            $headerFaculty = null;
            $headerProdi = null;

            foreach ($unstructuredLines as $line) {
                // Deteksi Jurusan / Prodi jika ada di sub-header teks
                $detected = $this->detectFacultyAndProdi($line);
                if ($detected['faculty']) $headerFaculty = $detected['faculty'];
                if ($detected['prodi']) $headerProdi = $detected['prodi'];

                $extracted = $this->extractNimAndNameFromLine($line);
                if ($extracted) {
                    $results[] = [
                        'student_id' => $extracted['nim'],
                        'name' => $extracted['name'],
                        'faculty' => $extracted['faculty'] ?? $headerFaculty ?? $defaultFaculty,
                        'study_program' => $extracted['study_program'] ?? $headerProdi ?? $defaultStudyProgram,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Memproses sekumpulan baris tabel dengan deteksi cerdas Header & Pemetaan Kolom
     */
    public function parseRows(
        array $rows,
        ?string $defaultFaculty = null,
        ?string $defaultStudyProgram = null
    ): array {
        $headerMap = null;
        $results = [];
        $headerFaculty = null;
        $headerProdi = null;

        foreach ($rows as $row) {
            // Normalisasi sel-sel dalam baris
            $cells = array_values(array_map(fn ($v) => trim(strval($v ?? '')), (array) $row));
            $nonEmpty = array_values(array_filter($cells, fn ($v) => $v !== ''));

            if (count($nonEmpty) < 2) {
                // Baris judul tunggal (misal: "Jurusan Analis Kesehatan", "Prodi Sarjana Terapan")
                $rowStr = implode(' ', $nonEmpty);
                $detected = $this->detectFacultyAndProdi($rowStr);
                if ($detected['faculty']) $headerFaculty = $detected['faculty'];
                if ($detected['prodi']) $headerProdi = $detected['prodi'];
                continue;
            }

            // Cek apakah baris ini adalah baris Header tabel
            if ($headerMap === null) {
                $map = $this->detectHeaderMap($cells);
                if ($map !== null) {
                    $headerMap = $map;
                    continue;
                }
            }

            // Jika header telah terdeteksi, ekstrak data menggunakan pemetaan kolom yang tepat
            if ($headerMap !== null) {
                $extracted = $this->extractFromMappedRow($cells, $headerMap, $headerFaculty, $headerProdi);
                if ($extracted) {
                    $results[] = $extracted;
                    continue;
                }
            }

            // Fallback baris per baris tanpa header
            $extracted = $this->extractNimAndNameFromCells($cells);
            if ($extracted) {
                $results[] = [
                    'student_id' => $extracted['nim'],
                    'name' => $extracted['name'],
                    'faculty' => $extracted['faculty'] ?? $headerFaculty,
                    'study_program' => $extracted['study_program'] ?? $headerProdi,
                ];
            }
        }

        return $results;
    }

    /**
     * Deteksi pemetaan kolom berdasarkan nama header (NIM, Nama, Fakultas, Prodi)
     */
    protected function detectHeaderMap(array $cells): ?array
    {
        $map = [];
        foreach ($cells as $colIndex => $cellVal) {
            $val = strtolower(trim($cellVal));
            if (empty($val)) continue;

            if (preg_match('/^(no|nomor|no\.|#|nomor\s*urut)$/i', $val)) {
                $map['no'] = $colIndex;
            } elseif (preg_match('/^(nim|npm|student.?id|no.?induk|stb|nomor\s*induk\s*mahasiswa|nim\/npm)$/i', $val)) {
                $map['nim'] = $colIndex;
            } elseif (preg_match('/^(nama|name|nama\s*lengkap|nama\s*mahasiswa|nama\s*peserta)$/i', $val) && !preg_match('/(jurusan|prodi|fakultas)/i', $val)) {
                $map['name'] = $colIndex;
            } elseif (preg_match('/^(fakultas|fakultas\/jurusan)$/i', $val)) {
                $map['faculty'] = $colIndex;
            } elseif (preg_match('/^(jurusan\/prodi|prodi|program\s*studi|jurusan|prodi\/jurusan)$/i', $val)) {
                $map['study_program'] = $colIndex;
            }
        }

        // Harus memiliki minimal kolom NIM dan kolom Nama
        if (isset($map['nim']) && isset($map['name'])) {
            return $map;
        }

        return null;
    }

    /**
     * Ekstrak satu baris data berdasarkan pemetaan kolom yang valid
     */
    protected function extractFromMappedRow(
        array $cells,
        array $headerMap,
        ?string $headerFaculty,
        ?string $headerProdi
    ): ?array {
        $nimRaw = $cells[$headerMap['nim']] ?? '';
        $nameRaw = $cells[$headerMap['name']] ?? '';
        $facultyRaw = isset($headerMap['faculty']) ? ($cells[$headerMap['faculty']] ?? null) : null;
        $prodiRaw = isset($headerMap['study_program']) ? ($cells[$headerMap['study_program']] ?? null) : null;

        if (empty($nimRaw) || empty($nameRaw)) {
            return null;
        }

        if (!$this->isNimFormat($nimRaw)) {
            return null;
        }

        $cleanNim = $this->cleanNim($nimRaw);
        $cleanName = $this->cleanName($nameRaw);

        if (empty($cleanNim) || empty($cleanName) || strlen($cleanName) < 2) {
            return null;
        }

        return [
            'student_id' => $cleanNim,
            'name' => $cleanName,
            'faculty' => !empty($facultyRaw) ? trim($facultyRaw) : $headerFaculty,
            'study_program' => !empty($prodiRaw) ? trim($prodiRaw) : $headerProdi,
        ];
    }

    /**
     * Ekstrak NIM dan Nama dari sel-sel array tanpa header
     */
    protected function extractNimAndNameFromCells(array $cells): ?array
    {
        $nim = null;
        $name = null;
        $faculty = null;
        $prodi = null;

        // Langkah 1: Cari sel yang sesuai dengan format NIM
        $nimIndex = null;
        foreach ($cells as $idx => $c) {
            $c = trim($c);
            if (empty($c)) continue;

            if ($this->isNimFormat($c)) {
                $nim = $this->cleanNim($c);
                $nimIndex = $idx;
                break;
            }
        }

        if (!$nim) {
            return null;
        }

        // Langkah 2: Cari Nama, Fakultas, dan Prodi dari sel lainnya
        foreach ($cells as $idx => $c) {
            if ($idx === $nimIndex) continue;
            $c = trim($c);
            if (empty($c)) continue;

            // Lewati nomor urut pendek (1-4 digit saja)
            if (preg_match('/^\d{1,4}[\.\)]?$/', $c)) {
                continue;
            }

            // Deteksi sel Fakultas
            if (preg_match('/^Fakultas\s+/i', $c)) {
                $faculty = $c;
                continue;
            }

            // Nama mahasiswa (terdiri dari huruf, minimal 2 karakter)
            if (!$name && preg_match('/[a-zA-Z]{2,}/', $c) && !preg_match('/^(no|nim|nama|nomor)$/i', $c)) {
                $name = $this->cleanName($c);
                continue;
            }

            // Sel sisa lainnya dijadikan nama Program Studi / Jurusan
            if (!$prodi && preg_match('/[a-zA-Z]{3,}/', $c)) {
                $prodi = $c;
            }
        }

        if ($nim && $name) {
            return [
                'nim' => $nim,
                'name' => $name,
                'faculty' => $faculty,
                'study_program' => $prodi,
            ];
        }

        return null;
    }

    /**
     * Ekstrak NIM dan Nama dari sebaris teks utuh (misal text hasil OCR PDF)
     */
    protected function extractNimAndNameFromLine(string $line): ?array
    {
        // Pola NIM: Alfanumerik 5-25 karakter yang wajib mengandung minimal 4 digit angka
        if (preg_match('/\b(?=[A-Za-z0-9]*\d{4,})[A-Za-z0-9]{5,25}\b/i', $line, $matches)) {
            $rawNim = $matches[0];
            if ($this->isNimFormat($rawNim)) {
                $nim = $this->cleanNim($rawNim);

                // Hilangkan NIM dari baris untuk mendapatkan Nama
                $remaining = str_replace($rawNim, '', $line);
                // Hilangkan nomor urut di awal baris (misal: "1.", "1 ", "104.")
                $remaining = preg_replace('/^\s*\d+[\.\)]?\s*/', '', $remaining);
                $name = $this->cleanName($remaining);

                if (!empty($name) && strlen($name) >= 2) {
                    return [
                        'nim' => $nim,
                        'name' => $name,
                        'faculty' => null,
                        'study_program' => null,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Cek apakah string adalah format NIM yang valid
     */
    public function isNimFormat(string $str): bool
    {
        $clean = strtoupper(trim(str_replace([' ', '-', '.', '/', '\''], '', $str)));
        if (strlen($clean) < 5 || strlen($clean) > 25) {
            return false;
        }

        // Wajib memiliki minimal 4 digit angka
        if (!preg_match('/\d{4,}/', $clean)) {
            return false;
        }

        // Hanya boleh alfanumerik
        if (!preg_match('/^[A-Z0-9]+$/', $clean)) {
            return false;
        }

        // Abaikan kata kunci umum tabel yang bukan NIM
        if (preg_match('/^(JUMLAH|TOTAL|FAKULTAS|JURUSAN|PRODI|SEMESTER|TAHUN|MAHASISWA)/i', $clean)) {
            return false;
        }

        return true;
    }

    /**
     * Bersihkan string NIM
     */
    public function cleanNim(string $nim): string
    {
        return strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $nim)));
    }

    /**
     * Bersihkan string Nama
     */
    public function cleanName(string $name): string
    {
        $cleaned = trim(preg_replace('/[|\t,;]+/', ' ', $name));
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = preg_replace('/^\d+[\.\)]?\s*/', '', $cleaned);
        return trim($cleaned);
    }

    /**
     * Deteksi nama Jurusan & Prodi dari teks judul / header
     */
    protected function detectFacultyAndProdi(string $line): array
    {
        $faculty = null;
        $prodi = null;

        if (preg_match('/Jurusan\s+([A-Za-z\s]+)/i', $line, $m)) {
            $faculty = trim(preg_replace('/\s*(Prodi|Tingkat|Tahun|Semester|Periode).*/i', '', $m[1]));
        }

        if (preg_match('/(?:Prodi|Program\s*Studi)\s+([A-Za-z0-9\.\s]+)/i', $line, $m)) {
            $prodi = trim(preg_replace('/\s*(Jurusan|Tingkat|Kelas|Periode|\().*/i', '', $m[1]));
        } elseif (preg_match('/(DIPLOMA\s*III|DIII|S\.\s*Tr|Sarjana\s*Terapan)\s+([A-Za-z\s]+)/i', $line, $m)) {
            $prodi = trim($m[0]);
        }

        return [
            'faculty' => $faculty,
            'prodi' => $prodi,
        ];
    }

    /**
     * Simpan baris pemilih yang diekstrak ke dalam database (tabel voters) dengan performa tinggi (batch query)
     */
    protected function saveRowsToDatabase(
        array $rows,
        ?string $defaultFaculty = null,
        ?string $defaultStudyProgram = null
    ): array {
        $totalFound = count($rows);
        $importedCount = 0;
        $skippedCount = 0;
        $updatedCount = 0;
        $errors = [];

        // Hapus duplikasi internal dalam kumpulan baris berdasarkan NIM
        $uniqueRows = [];
        foreach ($rows as $r) {
            $nim = $r['student_id'];
            if (!isset($uniqueRows[$nim])) {
                $uniqueRows[$nim] = $r;
            }
        }

        if (empty($uniqueRows)) {
            return [
                'total_found' => 0,
                'unique_found' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped_existing' => 0,
                'errors' => [],
            ];
        }

        // Ambil data pemilih yang sudah ada dalam satu query efisien
        $existingVoters = Voter::whereIn('student_id', array_keys($uniqueRows))
            ->get()
            ->keyBy('student_id');

        $now = now();
        $newVotersBatch = [];

        foreach ($uniqueRows as $nim => $row) {
            $name = $row['name'];
            $faculty = $row['faculty'] ?: $defaultFaculty;
            $studyProgram = $row['study_program'] ?: $defaultStudyProgram;

            if (empty($nim) || empty($name)) {
                continue;
            }

            try {
                if ($existingVoters->has($nim)) {
                    /** @var Voter $existing */
                    $existing = $existingVoters->get($nim);

                    // Jika nama belum lengkap atau masih sama dengan NIM, lengkapi datanya
                    if (empty($existing->name) || $existing->name === $nim || (empty($existing->faculty) && $faculty)) {
                        $existing->update([
                            'name' => $name,
                            'faculty' => $existing->faculty ?: $faculty,
                            'study_program' => $existing->study_program ?: $studyProgram,
                        ]);
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                } else {
                    $newVotersBatch[] = [
                        'student_id' => $nim,
                        'name' => $name,
                        'faculty' => $faculty,
                        'study_program' => $studyProgram,
                        'birth_date' => null,
                        'password' => null,
                        'token' => null,
                        'token_claimed_at' => null,
                        'has_voted' => false,
                        'voted_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $importedCount++;
                }
            } catch (\Throwable $e) {
                $errors[] = "NIM {$nim} ({$name}): " . $e->getMessage();
            }
        }

        // Masukkan data baru secara massal dalam chunk
        if (!empty($newVotersBatch)) {
            foreach (array_chunk($newVotersBatch, 250) as $chunk) {
                Voter::insert($chunk);
            }
        }

        return [
            'total_found' => $totalFound,
            'unique_found' => count($uniqueRows),
            'imported' => $importedCount,
            'updated' => $updatedCount,
            'skipped_existing' => $skippedCount,
            'errors' => $errors,
        ];
    }
}
