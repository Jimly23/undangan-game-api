<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rsvp;
use App\Models\Undangan;
use Illuminate\Http\Request;

class RsvpController extends Controller
{
public function index(Undangan $undangan)
    {
        return response()->json(
            $undangan->rsvps()->latest()->paginate(20)
        );
    }

    public function storeBySlug(Request $request, $slug)
    {
        $undangan = Undangan::where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nomor_telepon' => 'required|string|max:20',
            'email' => 'nullable|email',
            'status' => 'required|in:hadir,tidak_hadir',
            'pesan' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
        ]);

        $validated['undangan_id'] = $undangan->id;

        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $fotoPath = $foto->store('rsvps', 'public');
            $validated['foto'] = $fotoPath;
        }

        $rsvp = Rsvp::create($validated);

        return response()->json([
            'message' => 'RSVP berhasil dikirim',
            'data' => $rsvp
        ], 201);
    }

    public function showBySlug($slug)
    {
        $undangan = Undangan::where('slug', $slug)->firstOrFail();

        $rsvps = Rsvp::where('undangan_id', $undangan->id)
            ->latest()
            ->get()
            ->map(function ($rsvp) {
                if ($rsvp->foto) {
                    $rsvp->foto_url = url('storage/' . $rsvp->foto);
                }
                return $rsvp;
            });

        return response()->json([
            'message' => 'Data RSVP berhasil diambil',
            'data' => $rsvps
        ]);
    }

    public function update(Request $request, Rsvp $rsvp)
    {
        $rsvp->update($request->all());

        return response()->json([
            'message' => 'RSVP berhasil diupdate',
            'data' => $rsvp
        ]);
    }

    public function destroy(Rsvp $rsvp)
    {
        $rsvp->delete();

        return response()->json([
            'message' => 'RSVP berhasil dihapus'
        ]);
    }
}
