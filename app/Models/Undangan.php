<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Undangan extends Model
{
    protected $fillable = [
        'client_token',
        'slug',
        'tema',

        'foto_wanita',
        'nama_lengkap_wanita',
        'nama_panggilan_wanita',
        'nama_ayah_wanita',
        'nama_ibu_wanita',
        'alamat_wanita',
        'instagram_wanita',
        'whatsapp_wanita',

        'foto_pria',
        'nama_lengkap_pria',
        'nama_panggilan_pria',
        'nama_ayah_pria',
        'nama_ibu_pria',
        'alamat_pria',
        'instagram_pria',
        'whatsapp_pria',

        'galeri',
        'love_stories',

        'alamat_akad',
        'tanggal_akad',
        'jam_mulai_akad',
        'jam_selesai_akad',

        'alamat_resepsi',
        'tanggal_resepsi',
        'jam_mulai_resepsi',
        'jam_selesai_resepsi',

        'link_google_maps',
        'link_google_maps_resepsi',

        'dresscodes',

        'nomor_rekening_pria',
        'nama_bank_pria',
        'atas_nama_pria',

        'nomor_rekening_wanita',
        'nama_bank_wanita',
        'atas_nama_wanita',

        'qr_code',
        'musik',
    ];

    protected $casts = [
        'galeri' => 'array',
        'love_stories' => 'array',
        'dresscodes' => 'array',
        'tanggal_akad' => 'datetime',
        'tanggal_resepsi' => 'datetime',
    ];

    protected $appends = [
        'foto_wanita_url',
        'foto_pria_url',
        'musik_url',
        'qr_code_url',
        'galeri_url',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function rsvps()
    {
        return $this->hasMany(Rsvp::class);
    }

    public function guests()
    {
        return $this->hasMany(Guest::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFotoWanitaUrlAttribute()
    {
        return $this->foto_wanita
            ? asset('storage/' . $this->foto_wanita)
            : null;
    }

    public function getFotoPriaUrlAttribute()
    {
        return $this->foto_pria
            ? asset('storage/' . $this->foto_pria)
            : null;
    }

    public function getMusikUrlAttribute()
    {
        return $this->musik
            ? asset('storage/' . $this->musik)
            : null;
    }

    public function getQrCodeUrlAttribute()
    {
        return $this->qr_code
            ? asset('storage/' . $this->qr_code)
            : null;
    }

    public function getGaleriUrlAttribute()
    {
        if (!$this->galeri || !is_array($this->galeri)) {
            return [];
        }

        return collect($this->galeri)
            ->map(function ($file) {
                return asset('storage/' . $file);
            })
            ->toArray();
    }
}
