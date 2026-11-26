<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\Undangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuestController extends Controller
{
    public function index($slug)
    {
        $undangan = Undangan::where('slug', $slug)
            ->firstOrFail();

        $rsvpMap = Rsvp::where('undangan_id', $undangan->id)
            ->latest()
            ->get()
            ->reduce(function ($carry, $rsvp) {
                $key = mb_strtolower(trim($rsvp->nama));
                if ($key !== '' && !isset($carry[$key])) {
                    $carry[$key] = $rsvp->status;
                }
                return $carry;
            }, []);

        $guests = Guest::where('undangan_id', $undangan->id)
            ->latest()
            ->get()
            ->map(function ($guest) use ($undangan, $rsvpMap) {
                $guest->link =
                    config('app.frontend_url')
                    . '/'
                    . $undangan->slug
                    . '?to='
                    . urlencode($guest->nama_tamu);

                $key = mb_strtolower(trim($guest->nama_tamu));
                $guest->rsvp_status = $rsvpMap[$key] ?? null;

                return $guest;
            });

        $stats = $this->buildAttendanceStats($undangan);

        return response()->json([
            'success' => true,
            'undangan' => [
                'id' => $undangan->id,
                'slug' => $undangan->slug,
                'tema' => $undangan->tema,
            ],
            'total_tamu' => $guests->count(),
            'stats' => $stats,
            'data' => $guests
        ]);
    }

    public function store(Request $request, $slug)
    {
        $request->validate([
            'nama_tamu' => 'required|string|max:255',
        ]);

        $undangan = Undangan::where('slug', $slug)
            ->firstOrFail();

        $guest = Guest::create([
            'undangan_id' => $undangan->id,
            'nama_tamu' => trim($request->nama_tamu),
            'qr_token' => Guest::generateUniqueQrToken(),
            'is_checked_in' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tamu berhasil ditambahkan',
            'data' => $guest,
            'link' => config('app.frontend_url')
                . '/'
                . $undangan->slug
                . '?to='
                . urlencode($guest->nama_tamu)
        ], 201);
    }

    public function update(Request $request, $slug, $id)
    {
        $request->validate([
            'nama_tamu' => 'required|string|max:255',
        ]);

        $undangan = Undangan::where('slug', $slug)
            ->firstOrFail();

        $guest = Guest::where('id', $id)
            ->where('undangan_id', $undangan->id)
            ->firstOrFail();

        $guest->update([
            'nama_tamu' => trim($request->nama_tamu),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tamu berhasil diperbarui',
            'data' => $guest,
            'link' => config('app.frontend_url')
                . '/'
                . $undangan->slug
                . '?to='
                . urlencode($guest->nama_tamu)
        ]);
    }

    public function destroy($slug, $id)
    {
        $undangan = Undangan::where('slug', $slug)
            ->firstOrFail();

        $guest = Guest::where('id', $id)
            ->where('undangan_id', $undangan->id)
            ->firstOrFail();

        $guest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tamu berhasil dihapus'
        ]);
    }

    public function checkIn(Request $request, $slug)
    {
        $validated = $request->validate([
            'qr_token' => 'required|string|max:128',
        ]);

        $undangan = Undangan::where('slug', $slug)->firstOrFail();
        $token = trim($validated['qr_token']);
        $officer = $request->user()?->email ?? 'client_portal';

        $result = DB::transaction(function () use ($token, $undangan, $officer) {
            $guest = Guest::where('qr_token', $token)
                ->lockForUpdate()
                ->first();

            if (!$guest) {
                return [
                    'http_status' => 404,
                    'payload' => [
                        'success' => false,
                        'code' => 'INVALID_QR',
                        'message' => 'QR Code tidak ditemukan atau tidak valid.',
                    ],
                ];
            }

            if ((int) $guest->undangan_id !== (int) $undangan->id) {
                return [
                    'http_status' => 403,
                    'payload' => [
                        'success' => false,
                        'code' => 'WRONG_INVITATION',
                        'message' => 'QR Code bukan milik undangan ini.',
                    ],
                ];
            }

            if ($guest->is_checked_in) {
                return [
                    'http_status' => 409,
                    'payload' => [
                        'success' => false,
                        'code' => 'ALREADY_CHECKED_IN',
                        'message' => 'Tamu tersebut sudah melakukan check-in sebelumnya.',
                        'data' => [
                            'nama_tamu' => $guest->nama_tamu,
                            'is_checked_in' => true,
                            'checked_in_at' => $guest->checked_in_at?->toIso8601String(),
                            'checked_in_by' => $guest->checked_in_by,
                            'undangan_slug' => $undangan->slug,
                        ],
                    ],
                ];
            }

            $now = now();
            $updated = Guest::where('id', $guest->id)
                ->where('is_checked_in', false)
                ->update([
                    'is_checked_in' => true,
                    'checked_in_at' => $now,
                    'checked_in_by' => $officer,
                ]);

            if ($updated === 0) {
                $freshGuest = Guest::find($guest->id);
                return [
                    'http_status' => 409,
                    'payload' => [
                        'success' => false,
                        'code' => 'ALREADY_CHECKED_IN',
                        'message' => 'Tamu tersebut sudah melakukan check-in sebelumnya.',
                        'data' => [
                            'nama_tamu' => $freshGuest?->nama_tamu ?? $guest->nama_tamu,
                            'is_checked_in' => true,
                            'checked_in_at' => $freshGuest?->checked_in_at?->toIso8601String(),
                            'checked_in_by' => $freshGuest?->checked_in_by,
                            'undangan_slug' => $undangan->slug,
                        ],
                    ],
                ];
            }

            $guest->refresh();

            return [
                'http_status' => 200,
                'payload' => [
                    'success' => true,
                    'code' => 'CHECKIN_SUCCESS',
                    'message' => 'Check-in berhasil dicatat.',
                    'data' => [
                        'nama_tamu' => $guest->nama_tamu,
                        'is_checked_in' => true,
                        'checked_in_at' => $guest->checked_in_at?->toIso8601String(),
                        'checked_in_by' => $guest->checked_in_by,
                        'undangan' => [
                            'slug' => $undangan->slug,
                            'tema' => $undangan->tema,
                            'nama_panggilan_pria' => $undangan->nama_panggilan_pria,
                            'nama_panggilan_wanita' => $undangan->nama_panggilan_wanita,
                        ],
                    ],
                    'stats' => $this->buildAttendanceStats($undangan),
                ],
            ];
        });

        return response()->json($result['payload'], $result['http_status']);
    }

    private function buildAttendanceStats(Undangan $undangan): array
    {
        $totalGuests = Guest::where('undangan_id', $undangan->id)->count();
        $actualAttendance = Guest::where('undangan_id', $undangan->id)
            ->where('is_checked_in', true)
            ->count();
        $rsvpHadir = Rsvp::where('undangan_id', $undangan->id)
            ->where('status', 'hadir')
            ->count();
        $rsvpTidakHadir = Rsvp::where('undangan_id', $undangan->id)
            ->where('status', 'tidak_hadir')
            ->count();

        return [
            'total_guests' => $totalGuests,
            'rsvp_hadir' => $rsvpHadir,
            'rsvp_tidak_hadir' => $rsvpTidakHadir,
            'actual_attendance' => $actualAttendance,
            'belum_checkin' => max(0, $totalGuests - $actualAttendance),
        ];
    }
}
