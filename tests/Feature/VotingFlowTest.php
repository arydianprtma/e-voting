<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Voter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_landing_page_renders_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_voter_can_login_with_password_and_enter_booth(): void
    {
        $voter = Voter::where('has_voted', false)->first();
        $voter->update(['password' => 'password123']);

        $response = $this->post('/login-pemilih', [
            'student_id' => $voter->student_id,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/bilik-suara');
        $response->assertSessionHas('authenticated_voter_id', $voter->id);
    }

    public function test_voter_can_cast_vote_and_receives_receipt(): void
    {
        $voter = Voter::where('has_voted', false)->first();
        $candidate = Candidate::first();
        $initialVotes = $candidate->votes_count;

        // Masuk bilik suara
        $this->withSession([
            'authenticated_voter_id' => $voter->id,
            'authenticated_voter_nim' => $voter->student_id,
            'authenticated_voter_name' => $voter->name,
        ]);

        $response = $this->post('/kirim-suara', [
            'candidate_id' => $candidate->id,
        ]);

        $response->assertRedirect('/bukti-suara');
        $response->assertSessionHas('vote_receipt');

        // Pastikan status pemilih sudah tercatat
        $voter->refresh();
        $this->assertTrue($voter->has_voted);

        // Pastikan suara kandidat bertambah 1
        $candidate->refresh();
        $this->assertEquals($initialVotes + 1, $candidate->votes_count);
    }

    public function test_voter_cannot_vote_twice(): void
    {
        $voter = Voter::where('has_voted', false)->first();
        $voter->update(['password' => 'password123', 'has_voted' => true, 'voted_at' => now()]);

        $response = $this->post('/login-pemilih', [
            'student_id' => $voter->student_id,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('student_id');
    }

    public function test_quick_count_page_renders(): void
    {
        $response = $this->get('/quick-count');
        $response->assertStatus(200);
    }

    public function test_token_claim_route_redirects_to_registration(): void
    {
        $response = $this->get('/klaim-token');
        $response->assertRedirect('/register-pemilih');
    }

    public function test_voter_cannot_enter_booth_when_session_is_inactive(): void
    {
        \App\Models\VotingSession::query()->update(['is_active' => false]);

        $voter = Voter::where('has_voted', false)->first();
        $voter->update(['password' => 'password123']);

        $response = $this->post('/login-pemilih', [
            'student_id' => $voter->student_id,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('student_id');
    }

    public function test_vote_receipt_records_active_session_id(): void
    {
        $session = \App\Models\VotingSession::create([
            'name' => 'Sesi Uji Coba',
            'target_faculty' => null,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(2),
            'is_active' => true,
        ]);

        $voter = Voter::where('has_voted', false)->first();
        $candidate = Candidate::first();

        $this->withSession([
            'authenticated_voter_id' => $voter->id,
            'authenticated_voter_nim' => $voter->student_id,
            'authenticated_voter_name' => $voter->name,
            'authenticated_voter_faculty' => $voter->faculty,
            'authenticated_session_id' => $session->id,
            'authenticated_session_name' => $session->name,
        ])->post('/kirim-suara', [
            'candidate_id' => $candidate->id,
        ]);

        $this->assertDatabaseHas('vote_receipts', [
            'session_id' => $session->id,
        ]);
    }

    public function test_check_nim_endpoint_recognizes_dpt_voter(): void
    {
        $voter = Voter::first();

        $response = $this->postJson('/register-pemilih/check-nim', [
            'student_id' => $voter->student_id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'dpt_found',
            'valid' => true,
            'is_dpt' => true,
        ]);
    }

    public function test_voter_can_register_and_activate_account(): void
    {
        $voter = Voter::whereNull('password')->first();

        $response = $this->post('/register-pemilih', [
            'student_id' => $voter->student_id,
            'name' => $voter->name,
            'faculty' => $voter->faculty,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/bilik-suara');
        $this->assertDatabaseHas('voters', [
            'student_id' => $voter->student_id,
        ]);

        $this->assertDatabaseHas('registration_audits', [
            'student_id' => $voter->student_id,
            'status' => 'accepted',
        ]);
    }

    public function test_voter_can_login_with_nim_and_password(): void
    {
        $voter = Voter::whereNull('password')->first();
        $voter->update(['password' => 'rahasia123']);

        $response = $this->post('/login-pemilih', [
            'student_id' => $voter->student_id,
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect('/bilik-suara');
        $response->assertSessionHas('authenticated_voter_id', $voter->id);
    }

    public function test_admin_can_store_and_manage_department(): void
    {
        $admin = \App\Models\User::first();

        $response = $this->actingAs($admin)->post('/admin/jurusan', [
            'faculty_name' => 'Jurusan Rekam Medis',
            'study_program' => 'D3 Rekam Medis dan Informasi Kesehatan',
            'is_active' => true,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('departments', [
            'faculty_name' => 'Jurusan Rekam Medis',
            'study_program' => 'D3 Rekam Medis dan Informasi Kesehatan',
        ]);
    }

    public function test_admin_can_store_duo_and_solo_candidates(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $admin = \App\Models\User::first();

        // 1. Simpan kandidat Duo dengan foto
        $file = \Illuminate\Http\UploadedFile::fake()->image('paslon03.jpg', 600, 600);
        $responseDuo = $this->actingAs($admin)->post('/admin/kandidat', [
            'candidate_number' => 3,
            'candidate_type' => 'duo',
            'chairman_name' => 'Ahmad Fikri',
            'deputy_name' => 'Siti Nurhaliza',
            'faculty_info' => 'Keperawatan & Kebidanan',
            'vision' => 'Visi Inovatif',
            'missions' => ['Misi 1', 'Misi 2'],
            'photo' => $file,
        ]);

        $responseDuo->assertSessionHas('success');
        $this->assertDatabaseHas('candidates', [
            'candidate_number' => 3,
            'candidate_type' => 'duo',
            'chairman_name' => 'Ahmad Fikri',
            'deputy_name' => 'Siti Nurhaliza',
        ]);

        $candidate3 = Candidate::where('candidate_number', 3)->first();
        $this->assertNotNull($candidate3->photo_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists(str_replace('/storage/', '', $candidate3->photo_path));

        // 2. Simpan kandidat Solo (tanpa calon wakil)
        $responseSolo = $this->actingAs($admin)->post('/admin/kandidat', [
            'candidate_number' => 4,
            'candidate_type' => 'solo',
            'chairman_name' => 'Budi Santoso',
            'deputy_name' => '',
            'faculty_info' => 'Gizi',
            'vision' => 'Membangun Polkesmar Sehat',
            'missions' => ['Misi Utama'],
        ]);

        $responseSolo->assertSessionHas('success');
        $this->assertDatabaseHas('candidates', [
            'candidate_number' => 4,
            'candidate_type' => 'solo',
            'chairman_name' => 'Budi Santoso',
            'deputy_name' => null,
        ]);
    }

    public function test_admin_can_update_voter(): void
    {
        $admin = \App\Models\User::first();
        $voter = Voter::where('has_voted', false)->first();

        $response = $this->actingAs($admin)->put("/admin/pemilih/{$voter->id}", [
            'student_id' => 'P1337420123999',
            'name' => 'Nama Mahasiswa Diperbarui',
            'faculty' => 'Jurusan Keperawatan',
            'study_program' => 'D4 Keperawatan Semarang',
            'birth_date' => '2004-10-15',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('voters', [
            'id' => $voter->id,
            'student_id' => 'P1337420123999',
            'name' => 'Nama Mahasiswa Diperbarui',
        ]);
    }

    public function test_admin_can_delete_unvoted_voter(): void
    {
        $admin = \App\Models\User::first();
        $voter = Voter::where('has_voted', false)->first();

        $response = $this->actingAs($admin)->delete("/admin/pemilih/{$voter->id}");

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('voters', [
            'id' => $voter->id,
        ]);
    }

    public function test_admin_cannot_delete_voter_who_has_voted(): void
    {
        $admin = \App\Models\User::first();
        $voter = Voter::first();
        $voter->update(['has_voted' => true]);

        $response = $this->actingAs($admin)->delete("/admin/pemilih/{$voter->id}");

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('voters', [
            'id' => $voter->id,
        ]);
    }

    public function test_admin_can_bulk_delete_unvoted_voters(): void
    {
        $admin = \App\Models\User::first();
        $voters = Voter::where('has_voted', false)->take(2)->get();
        $ids = $voters->pluck('id')->toArray();

        $response = $this->actingAs($admin)->post('/admin/pemilih/bulk-delete', [
            'ids' => $ids,
        ]);

        $response->assertSessionHas('success');
        foreach ($ids as $id) {
            $this->assertDatabaseMissing('voters', ['id' => $id]);
        }
    }

    public function test_unactivated_voter_cannot_login_without_registered_password(): void
    {
        $voter = Voter::whereNull('password')->first();

        $response = $this->post('/login-pemilih', [
            'student_id' => $voter->student_id,
            'password' => $voter->student_id,
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertNull(session('authenticated_voter_id'));
    }

    public function test_vote_cannot_be_submitted_when_election_is_inactive(): void
    {
        $voter = Voter::where('has_voted', false)->first();
        $candidate = Candidate::first();

        // Nonaktifkan pemilihan
        \App\Models\ElectionSetting::first()->update(['is_active' => false]);

        $this->withSession([
            'authenticated_voter_id' => $voter->id,
            'authenticated_voter_nim' => $voter->student_id,
            'authenticated_voter_name' => $voter->name,
        ]);

        $response = $this->post('/kirim-suara', [
            'candidate_id' => $candidate->id,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['student_id']);
        $this->assertFalse($voter->fresh()->has_voted);
    }
}
