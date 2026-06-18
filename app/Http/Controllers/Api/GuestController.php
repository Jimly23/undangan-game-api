<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Undangan;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index($slug)
    {
        $undangan = Undangan::where('slug', $slug)
            ->firstOrFail();

        $guests = Guest::where('undangan_id', $undangan->id)
            ->latest()
            ->get()
            ->map(function ($guest) use ($undangan) {

                $guest->link =
                    config('app.frontend_url')
                    . '/'
                    . $undangan->slug
                    . '?to='
                    . urlencode($guest->nama_tamu);

                return $guest;
            });

        return response()->json([
            'success' => true,
            'undangan' => [
                'id' => $undangan->id,
                'slug' => $undangan->slug,
                'tema' => $undangan->tema,
            ],
            'total_tamu' => $guests->count(),
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
            'nama_tamu' => $request->nama_tamu,
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
        ]);
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
            'nama_tamu' => $request->nama_tamu,
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
}
