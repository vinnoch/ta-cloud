<?php

use App\Models\Periode;
use App\Models\ReviewerAssignment;
use App\Models\SidangRequest;
use App\Models\Skripsi;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->primaryAdvisor = User::factory()->dosen()->create();
    $this->secondaryAdvisor = User::factory()->dosen()->create();
    $this->reviewer = User::factory()->dosen()->create();
    $this->kaprodi = User::factory()->kaprodi()->create();
    $student = User::factory()->mahasiswa()->create();
    $tahun = TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    $periode = Periode::query()->create([
        'tahun_akademik_id' => $tahun->id,
        'kode_periode' => '20251',
        'semester' => 1,
        'sk_nomor' => 'SK-1',
        'tgl_mulai' => '2025-08-01',
        'tgl_selesai' => '2026-01-31',
        'is_aktif' => true,
        'status' => 'active',
    ]);
    $this->skripsi = Skripsi::query()->create([
        'student_id' => $student->id,
        'periode_id' => $periode->id,
        'title' => 'Ready for Sidang',
        'type' => 'skripsi',
        'current_phase' => 'bimbingan_skripsi',
    ]);
    ReviewerAssignment::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'lecturer_id' => $this->primaryAdvisor->id,
        'role_type' => 'pembimbing_1',
    ]);
    ReviewerAssignment::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'lecturer_id' => $this->secondaryAdvisor->id,
        'role_type' => 'pembimbing_2',
    ]);
    ReviewerAssignment::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'lecturer_id' => $this->reviewer->id,
        'role_type' => 'penguji_1',
    ]);
});

it('allows the primary advisor to submit a sidang request', function () {
    $this->actingAs($this->primaryAdvisor)
        ->post(route('dosen.sidang-request.store', $this->skripsi), ['note' => 'Ready'])
        ->assertRedirect(route('dosen.sidang-request.index', ['status' => 'submitted']));

    $this->assertDatabaseHas('sidang_requests', [
        'skripsi_id' => $this->skripsi->id,
        'lecturer_id' => $this->primaryAdvisor->id,
        'role_type' => 'pembimbing_1',
        'status' => 'submitted',
    ]);
});

it('allows both advisors to submit independently', function () {
    foreach ([$this->primaryAdvisor, $this->secondaryAdvisor] as $advisor) {
        $this->actingAs($advisor)
            ->post(route('dosen.sidang-request.store', $this->skripsi))
            ->assertRedirect(route('dosen.sidang-request.index', ['status' => 'submitted']));
    }

    $this->assertDatabaseHas('sidang_requests', [
        'lecturer_id' => $this->secondaryAdvisor->id,
        'role_type' => 'pembimbing_2',
        'status' => 'submitted',
    ]);
    expect($this->skripsi->sidangRequests()->count())->toBe(2);
});

it('blocks kaprodi approval until every advisor submits then advances after all approvals', function () {
    $this->actingAs($this->primaryAdvisor)
        ->post(route('dosen.sidang-request.store', $this->skripsi));
    $primaryRequest = $this->skripsi->sidangRequests()->where('lecturer_id', $this->primaryAdvisor->id)->firstOrFail();

    $this->actingAs($this->kaprodi)
        ->post(route('kaprodi.skripsi.sidang-request.approve', [$this->skripsi, $primaryRequest]))
        ->assertSessionHas('error');

    expect($primaryRequest->fresh()->status)->toBe('submitted')
        ->and($this->skripsi->fresh()->current_phase)->toBe('bimbingan_skripsi');

    $this->actingAs($this->secondaryAdvisor)
        ->post(route('dosen.sidang-request.store', $this->skripsi));
    $secondaryRequest = $this->skripsi->sidangRequests()->where('lecturer_id', $this->secondaryAdvisor->id)->firstOrFail();

    $this->actingAs($this->kaprodi)
        ->post(route('kaprodi.skripsi.sidang-request.approve', [$this->skripsi, $primaryRequest]))
        ->assertSessionHas('success');

    expect($this->skripsi->fresh()->current_phase)->toBe('bimbingan_skripsi');

    $this->actingAs($this->kaprodi)
        ->post(route('kaprodi.skripsi.sidang-request.approve', [$this->skripsi, $secondaryRequest]))
        ->assertSessionHas('success');

    expect($this->skripsi->fresh()->current_phase)->toBe('sidang_skripsi');
});

it('does not show kaprodi approval for stale requests from former advisors', function () {
    $formerAdvisor = User::factory()->dosen()->create();

    foreach ([$this->primaryAdvisor, $formerAdvisor] as $advisor) {
        SidangRequest::query()->create([
            'skripsi_id' => $this->skripsi->id,
            'lecturer_id' => $advisor->id,
            'role_type' => 'pembimbing_1',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.skripsi.show', $this->skripsi))
        ->assertOk()
        ->assertDontSee('acss-sidang-approve-button', false)
        ->assertSee('Menunggu semua pembimbing mengajukan sidang.');
});

it('blocks an assigned non-advisor from submitting a sidang request', function () {
    $this->actingAs($this->reviewer)
        ->post(route('dosen.sidang-request.store', $this->skripsi))
        ->assertRedirect(route('dosen.skripsi.show', $this->skripsi))
        ->assertSessionHas('error');

    $this->assertDatabaseCount('sidang_requests', 0);
});

it('shows every request status in the dashboard total', function () {
    SidangRequest::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'lecturer_id' => $this->primaryAdvisor->id,
        'role_type' => 'pembimbing_1',
        'status' => 'approved',
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->primaryAdvisor)
        ->get(route('dosen.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Pengajuan Sidang', '1', 'Total pengajuan Anda']);
});

it('allows only the submitting advisor to cancel a pending sidang request', function () {
    $this->actingAs($this->primaryAdvisor)
        ->post(route('dosen.sidang-request.store', $this->skripsi), ['note' => 'Ready']);

    $sidangRequest = $this->skripsi->sidangRequests()->firstOrFail();

    $this->actingAs($this->primaryAdvisor)
        ->get(route('dosen.skripsi.show', $this->skripsi))
        ->assertOk()
        ->assertSee('Batalkan pengajuan sidang')
        ->assertSee('Alasan Pembatalan');

    $this->actingAs($this->reviewer)
        ->delete(route('dosen.sidang-request.destroy', [$this->skripsi, $sidangRequest]))
        ->assertNotFound();

    $this->actingAs($this->primaryAdvisor)
        ->delete(route('dosen.sidang-request.destroy', [$this->skripsi, $sidangRequest]), ['reason' => 'Mahasiswa belum siap sidang.'])
        ->assertRedirect(route('dosen.skripsi.show', $this->skripsi))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('sidang_requests', ['id' => $sidangRequest->id]);
});

it('hides and blocks cancellation after a sidang request is processed', function () {
    $this->actingAs($this->primaryAdvisor)
        ->post(route('dosen.sidang-request.store', $this->skripsi));

    $sidangRequest = $this->skripsi->sidangRequests()->firstOrFail();
    $sidangRequest->update(['status' => 'approved']);

    $this->actingAs($this->primaryAdvisor)
        ->get(route('dosen.skripsi.show', $this->skripsi))
        ->assertOk()
        ->assertDontSee('Batalkan pengajuan sidang');

    $this->actingAs($this->primaryAdvisor)
        ->delete(route('dosen.sidang-request.destroy', [$this->skripsi, $sidangRequest]))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('sidang_requests', ['id' => $sidangRequest->id]);
});
