<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VoterImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_voters_from_raw_text(): void
    {
        $admin = User::factory()->create();

        $rawText = <<<TXT
| NO | NAMA | NIM |
| 1. | Anis Rifanabila | P1337434324001 |
| 2. | Haza Saadah | P1337434324002 |
TXT;

        $response = $this->actingAs($admin)->post('/admin/pemilih/import', [
            'raw_text' => $rawText,
            'faculty' => 'Jurusan Analis Kesehatan',
            'study_program' => 'Sarjana Terapan TLM',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('voters', [
            'student_id' => 'P1337434324001',
            'name' => 'Anis Rifanabila',
        ]);
        $this->assertDatabaseHas('voters', [
            'student_id' => 'P1337434324002',
            'name' => 'Haza Saadah',
        ]);
    }

    public function test_admin_can_import_voters_from_csv_file(): void
    {
        $admin = User::factory()->create();

        $content = "NO,NAMA,NIM\n1,Syakila Dwi Sukma,P1337434224001\n2,Renata Oktarina,P1337434224002";
        $file = UploadedFile::fake()->createWithContent('dpt_sample.csv', $content);

        $response = $this->actingAs($admin)->post('/admin/pemilih/import', [
            'file' => $file,
            'faculty' => 'Jurusan Analis Kesehatan',
            'study_program' => 'DIII Teknologi Bank Darah',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('voters', [
            'student_id' => 'P1337434224001',
            'name' => 'Syakila Dwi Sukma',
        ]);
    }

    public function test_import_skips_duplicate_nims_cleanly(): void
    {
        $admin = User::factory()->create();
        Voter::create([
            'student_id' => 'P1337434324001',
            'name' => 'Anis Rifanabila',
            'faculty' => 'Jurusan Analis Kesehatan',
        ]);

        $rawText = "1. Anis Rifanabila P1337434324001\n2. Budi Santoso P1337434324099";

        $response = $this->actingAs($admin)->post('/admin/pemilih/import', [
            'raw_text' => $rawText,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('voters', [
            'student_id' => 'P1337434324099',
            'name' => 'Budi Santoso',
        ]);
    }

    public function test_admin_can_destroy_all_unvoted_voters(): void
    {
        $admin = User::factory()->create();
        Voter::create([
            'student_id' => 'P1337434324001',
            'name' => 'Pemilih 1',
            'faculty' => 'Jurusan Analis Kesehatan',
            'has_voted' => false,
        ]);
        Voter::create([
            'student_id' => 'P1337434324002',
            'name' => 'Pemilih 2',
            'faculty' => 'Jurusan Analis Kesehatan',
            'has_voted' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/pemilih/destroy-all', [
            'confirm_text' => 'HAPUS',
            'include_voted' => false,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('voters', ['student_id' => 'P1337434324001']);
        $this->assertDatabaseHas('voters', ['student_id' => 'P1337434324002']);
    }

    public function test_admin_can_destroy_all_voters_including_voted(): void
    {
        $admin = User::factory()->create();
        Voter::create([
            'student_id' => 'P1337434324001',
            'name' => 'Pemilih 1',
            'faculty' => 'Jurusan Analis Kesehatan',
            'has_voted' => false,
        ]);
        Voter::create([
            'student_id' => 'P1337434324002',
            'name' => 'Pemilih 2',
            'faculty' => 'Jurusan Analis Kesehatan',
            'has_voted' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/pemilih/destroy-all', [
            'confirm_text' => 'HAPUS',
            'include_voted' => true,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(0, Voter::count());
    }

    public function test_destroy_all_fails_with_invalid_confirmation(): void
    {
        $admin = User::factory()->create();
        Voter::create([
            'student_id' => 'P1337434324001',
            'name' => 'Pemilih 1',
            'faculty' => 'Jurusan Analis Kesehatan',
        ]);

        $response = $this->actingAs($admin)->post('/admin/pemilih/destroy-all', [
            'confirm_text' => 'SALAH',
        ]);

        $response->assertSessionHasErrors('confirm_text');
        $this->assertEquals(1, Voter::count());
    }
}
