<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Undangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UndanganController extends Controller
{
    public function index()
    {
        $undangan = Undangan::withCount('rsvps')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data undangan berhasil diambil',
            'data' => $undangan
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required|string|unique:undangans,slug',
            'tema' => 'nullable|string',

            'nama_lengkap_wanita' => 'nullable|string',
            'nama_panggilan_wanita' => 'nullable|string',
            'nama_ayah_wanita' => 'nullable|string',
            'nama_ibu_wanita' => 'nullable|string',
            'alamat_wanita' => 'nullable|string',
            'instagram_wanita' => 'nullable|string',
            'whatsapp_wanita' => 'nullable|string',

            'nama_lengkap_pria' => 'nullable|string',
            'nama_panggilan_pria' => 'nullable|string',
            'nama_ayah_pria' => 'nullable|string',
            'nama_ibu_pria' => 'nullable|string',
            'alamat_pria' => 'nullable|string',
            'instagram_pria' => 'nullable|string',
            'whatsapp_pria' => 'nullable|string',

            'alamat_akad' => 'nullable|string',
            'tanggal_akad' => 'nullable|date',
            'jam_mulai_akad' => 'nullable|string',
            'jam_selesai_akad' => 'nullable|string',

            'alamat_resepsi' => 'nullable|string',
            'tanggal_resepsi' => 'nullable|date',
            'jam_mulai_resepsi' => 'nullable|string',
            'jam_selesai_resepsi' => 'nullable|string',

            'link_google_maps' => 'nullable|string',
            'link_google_maps_resepsi' => 'nullable|string',

            'nomor_rekening_pria' => 'nullable|string',
            'nama_bank_pria' => 'nullable|string',
            'atas_nama_pria' => 'nullable|string',

            'nomor_rekening_wanita' => 'nullable|string',
            'nama_bank_wanita' => 'nullable|string',
            'atas_nama_wanita' => 'nullable|string',

            'love_stories' => 'nullable',
            'dresscodes' => 'nullable',

            'foto_wanita' => 'nullable|image',
            'foto_pria' => 'nullable|image',
            'musik' => 'nullable|mimes:mp3,wav,ogg',
            'qr_code' => 'nullable|image',
            'galeri.*' => 'nullable|image',
        ]);

        if ($request->hasFile('foto_wanita')) {
            $path = $request->file('foto_wanita')->store('mempelai/wanita', 'public');
            $data['foto_wanita'] = $this->getFileUrl($path);
        }

        if ($request->hasFile('foto_pria')) {
            $path = $request->file('foto_pria')->store('mempelai/pria', 'public');
            $data['foto_pria'] = $this->getFileUrl($path);
        }

        if ($request->hasFile('musik')) {
            $path = $request->file('musik')->store('music', 'public');
            $data['musik'] = $this->getFileUrl($path);
        }

        if ($request->hasFile('qr_code')) {
            $path = $request->file('qr_code')->store('qrcode', 'public');
            $data['qr_code'] = $this->getFileUrl($path);
        }

        if ($request->hasFile('galeri')) {

            $gallery = [];

            foreach ($request->file('galeri') as $file) {
                $path = $file->store('gallery', 'public');
                $gallery[] = $this->getFileUrl($path);
            }

            $data['galeri'] = $gallery;
        }

        $data['client_token'] = \Illuminate\Support\Str::random(32);

        $undangan = Undangan::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Undangan berhasil dibuat',
            'data' => $undangan
        ], 201);
    }

    public function show(Undangan $undangan)
    {
        $undangan->loadCount('rsvps');

        return response()->json([
            'success' => true,
            'data' => $undangan
        ]);
    }

    public function update(Request $request, Undangan $undangan)
    {
        $data = $request->validate([
            'slug' => 'sometimes|string|unique:undangans,slug,' . $undangan->id,
            'tema' => 'nullable|string',

            'foto_wanita' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'foto_pria' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'musik' => 'nullable|mimes:mp3,wav,ogg|max:10240',

            'qr_code' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'galeri.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'link_google_maps_resepsi' => 'nullable|string',
        ]);

        if ($request->hasFile('foto_wanita')) {
            $this->deleteFile($undangan->foto_wanita);
            $path = $request->file('foto_wanita')->store('mempelai/wanita', 'public');
            $data['foto_wanita'] = $this->getFileUrl($path);
        }

        if ($request->hasFile('foto_pria')) {
            $this->deleteFile($undangan->foto_pria);
            $path = $request->file('foto_pria')->store('mempelai/pria', 'public');
            $data['foto_pria'] = $this->getFileUrl($path);
        }

        if ($request->hasFile('musik')) {
            $this->deleteFile($undangan->musik);
            $path = $request->file('musik')->store('music', 'public');
            $data['musik'] = $this->getFileUrl($path);
        }

        if ($request->hasFile('qr_code')) {
            $this->deleteFile($undangan->qr_code);
            $path = $request->file('qr_code')->store('qrcode', 'public');
            $data['qr_code'] = $this->getFileUrl($path);
        }

        if ($request->hasFile('galeri')) {
            if (!empty($undangan->galeri)) {
                foreach ($undangan->galeri as $image) {
                    $this->deleteFile($image);
                }
            }

            $gallery = [];
            foreach ($request->file('galeri') as $file) {
                $path = $file->store('gallery', 'public');
                $gallery[] = $this->getFileUrl($path);
            }
            $data['galeri'] = $gallery;
        }

        $undangan->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Undangan berhasil diperbarui',
            'data' => $undangan->fresh()
        ]);
    }

    public function destroy(Undangan $undangan)
    {
        $this->deleteFile($undangan->foto_wanita);
        $this->deleteFile($undangan->foto_pria);
        $this->deleteFile($undangan->musik);
        $this->deleteFile($undangan->qr_code);

        if (!empty($undangan->galeri)) {
            foreach ($undangan->galeri as $image) {
                $this->deleteFile($image);
            }
        }

        $undangan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Undangan berhasil dihapus'
        ]);
    }

    public function showBySlug($slug)
    {
        $undangan = Undangan::withCount('rsvps')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $undangan
        ]);
    }

    public function destroyBySlug($slug)
    {
        $undangan = Undangan::where('slug', $slug)
            ->firstOrFail();

        $this->deleteFile($undangan->foto_wanita);
        $this->deleteFile($undangan->foto_pria);
        $this->deleteFile($undangan->musik);
        $this->deleteFile($undangan->qr_code);

        if (!empty($undangan->galeri)) {
            foreach ($undangan->galeri as $image) {
                $this->deleteFile($image);
            }
        }

        $undangan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Undangan berhasil dihapus'
        ]);
    }

    private function getFileUrl($path)
    {
        return rtrim(env('APP_URL'), '/') . '/storage/' . $path;
    }

    private function deleteFile($url)
    {
        if ($url) {
            $path = explode('/storage/', $url);
            $filePath = isset($path[1]) ? $path[1] : $url;
            Storage::disk('public')->delete($filePath);
        }
    }
}