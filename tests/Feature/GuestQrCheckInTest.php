<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\Undangan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuestQrCheckInTest extends TestCase
{
    use RefreshDatabase;

    private function createUndangan(array $overrides = []): Undangan
    {
        return Undangan::create(array_merge([
            'slug' => 'adelio-elina',
            'tema' => 'mahara-japan-v2',
            'client_token' => 'token-adelio-elina-123456',
            'nama_panggilan_pria' => 'Adelio',
            'nama_panggilan_wanita' => 'Elina',
        ], $overrides));
    }

    public function test_new_guests_receive_unique_non_guessable_qr_tokens(): void
    {
        $undangan = $this->createUndangan();

        $res1 = $this->postJson("/api/client/undangan/{$undangan->slug}/guests", [
            'nama_tamu' => 'Firman Agus',
        ], ['X-Client-Token' => $undangan->client_token]);

        $res2 = $this->postJson("/api/client/undangan/{$undangan->slug}/guests", [
            'nama_tamu' => 'Budi Santoso',
        ], ['X-Client-Token' => $undangan->client_token]);

        $res1->assertStatus(201)->assertJsonPath('success', true);
        $res2->assertStatus(201)->assertJsonPath('success', true);

        $token1 = $res1->json('data.qr_token');
        $token2 = $res2->json('data.qr_token');
        $id1 = (string) $res1->json('data.id');

        $this->assertNotEmpty($token1);
        $this->assertNotEmpty($token2);
        $this->assertNotEquals($token1, $token2);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{48}$/', $token1);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{48}$/', $token2);
        $this->assertStringNotContainsString('Firman', $token1);
        $this->assertNotEquals($id1, $token1);
        $this->assertNotEquals(md5($id1), $token1);
    }

    public function test_legacy_guests_without_qr_token_can_be_backfilled_without_losing_data(): void
    {
        $undangan = $this->createUndangan();

        // Simulate legacy guest row inserted prior to QR feature
        $legacyId = DB::table('guests')->insertGetId([
            'undangan_id' => $undangan->id,
            'nama_tamu' => 'Tamu Lama',
            'qr_token' => '',
            'is_checked_in' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run the backfill logic from migration
        $rows = DB::table('guests')->whereNull('qr_token')->orWhere('qr_token', '')->get();
        foreach ($rows as $row) {
            DB::table('guests')
                ->where('id', $row->id)
                ->update(['qr_token' => Guest::generateUniqueQrToken()]);
        }

        $legacyGuest = Guest::find($legacyId);
        $this->assertNotNull($legacyGuest);
        $this->assertEquals('Tamu Lama', $legacyGuest->nama_tamu);
        $this->assertNotEmpty($legacyGuest->qr_token);
        $this->assertFalse($legacyGuest->is_checked_in);
    }

    public function test_valid_qr_checks_in_once_and_duplicate_or_invalid_or_other_invitation_qr_is_rejected(): void
    {
        $undanganA = $this->createUndangan();
        $undanganB = $this->createUndangan([
            'slug' => 'rizky-aulia',
            'client_token' => 'token-rizky-aulia-654321',
        ]);

        $guestA = Guest::create([
            'undangan_id' => $undanganA->id,
            'nama_tamu' => 'Firman',
        ]);
        $guestB = Guest::create([
            'undangan_id' => $undanganB->id,
            'nama_tamu' => 'Siti',
        ]);

        // Unauthorized check-in without token must be rejected
        $this->postJson("/api/client/undangan/{$undanganA->slug}/check-in", [
            'qr_token' => $guestA->qr_token,
        ])->assertStatus(401);

        // Wrong client token must be rejected
        $this->postJson("/api/client/undangan/{$undanganA->slug}/check-in", [
            'qr_token' => $guestA->qr_token,
        ], ['X-Client-Token' => 'invalid-token'])->assertStatus(403);

        // Invalid / non-existent QR token must be rejected
        $this->postJson("/api/client/undangan/{$undanganA->slug}/check-in", [
            'qr_token' => 'non-existent-qr-token-xyz',
        ], ['X-Client-Token' => $undanganA->client_token])
            ->assertStatus(404)
            ->assertJsonPath('code', 'INVALID_QR');

        // QR belonging to another invitation must be rejected
        $this->postJson("/api/client/undangan/{$undanganA->slug}/check-in", [
            'qr_token' => $guestB->qr_token,
        ], ['X-Client-Token' => $undanganA->client_token])
            ->assertStatus(403)
            ->assertJsonPath('code', 'WRONG_INVITATION');

        // Valid QR succeeds
        $okRes = $this->postJson("/api/client/undangan/{$undanganA->slug}/check-in", [
            'qr_token' => $guestA->qr_token,
        ], ['X-Client-Token' => $undanganA->client_token]);

        $okRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 'CHECKIN_SUCCESS')
            ->assertJsonPath('data.nama_tamu', 'Firman')
            ->assertJsonPath('stats.actual_attendance', 1)
            ->assertJsonPath('stats.belum_checkin', 0);

        // Re-scanning the same QR must be rejected and not increment attendance
        $dupRes = $this->postJson("/api/client/undangan/{$undanganA->slug}/check-in", [
            'qr_token' => $guestA->qr_token,
        ], ['X-Client-Token' => $undanganA->client_token]);

        $dupRes->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ALREADY_CHECKED_IN')
            ->assertJsonPath('data.nama_tamu', 'Firman');

        $this->assertEquals(1, Guest::where('undangan_id', $undanganA->id)->where('is_checked_in', true)->count());
    }

    public function test_rsvp_does_not_auto_checkin_and_dashboard_stats_stay_accurate(): void
    {
        $undangan = $this->createUndangan();

        $guest1 = Guest::create(['undangan_id' => $undangan->id, 'nama_tamu' => 'Firman']);
        $guest2 = Guest::create(['undangan_id' => $undangan->id, 'nama_tamu' => 'Agus']);

        // Guest 1 submits RSVP "hadir"
        $this->postJson("/api/rsvps/{$undangan->slug}", [
            'nama' => 'Firman',
            'nomor_telepon' => '08123456789',
            'status' => 'hadir',
            'pesan' => 'Selamat menempuh hidup baru!',
        ])->assertStatus(201);

        $guest1->refresh();
        $this->assertFalse($guest1->is_checked_in);
        $this->assertNull($guest1->checked_in_at);

        // Dashboard before check-in: total_guests=2, rsvp_hadir=1, actual_attendance=0, belum_checkin=2
        $dashBefore = $this->getJson("/api/undangan/{$undangan->slug}/guests");
        $dashBefore->assertStatus(200)
            ->assertJsonPath('stats.total_guests', 2)
            ->assertJsonPath('stats.rsvp_hadir', 1)
            ->assertJsonPath('stats.actual_attendance', 0)
            ->assertJsonPath('stats.belum_checkin', 2);

        // Guest 2 (who did NOT RSVP) checks in via QR
        $this->postJson("/api/client/undangan/{$undangan->slug}/check-in", [
            'qr_token' => $guest2->qr_token,
        ], ['X-Client-Token' => $undangan->client_token])->assertStatus(200);

        // Dashboard after check-in: total_guests=2, rsvp_hadir=1, actual_attendance=1, belum_checkin=1
        $dashAfter = $this->getJson("/api/undangan/{$undangan->slug}/guests");
        $dashAfter->assertStatus(200)
            ->assertJsonPath('stats.total_guests', 2)
            ->assertJsonPath('stats.rsvp_hadir', 1)
            ->assertJsonPath('stats.actual_attendance', 1)
            ->assertJsonPath('stats.belum_checkin', 1);
    }

    public function test_personal_invitation_endpoint_returns_matching_guest_pass_and_preserves_preview(): void
    {
        $undangan = $this->createUndangan();
        $guest = Guest::create(['undangan_id' => $undangan->id, 'nama_tamu' => 'Firman']);

        // Personal link query returns matching guest QR pass
        $personalRes = $this->getJson("/api/invitation/{$undangan->slug}?guest=firman");
        $personalRes->assertStatus(200)
            ->assertJsonPath('data.slug', $undangan->slug)
            ->assertJsonPath('guest_pass.nama_tamu', 'Firman')
            ->assertJsonPath('guest_pass.qr_token', $guest->qr_token);

        // Preview / unregistered name still returns invitation without guest_pass
        $previewRes = $this->getJson("/api/invitation/{$undangan->slug}?guest=Nama");
        $previewRes->assertStatus(200)
            ->assertJsonPath('data.slug', $undangan->slug)
            ->assertJsonPath('guest_pass', null);
    }

    public function test_admin_officer_can_also_check_in_via_sanctum_token(): void
    {
        $undangan = $this->createUndangan();
        $guest = Guest::create(['undangan_id' => $undangan->id, 'nama_tamu' => 'Agus']);
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@youvitation.net',
            'password' => Hash::make('Admin123!'),
        ]);
        $token = $admin->createToken('admin-token')->plainTextToken;

        $res = $this->postJson("/api/undangan/{$undangan->slug}/check-in", [
            'qr_token' => $guest->qr_token,
        ], ['Authorization' => "Bearer {$token}"]);

        $res->assertStatus(200)
            ->assertJsonPath('data.checked_in_by', 'admin@youvitation.net');
    }
}
