<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    protected $fillable = [
        'undangan_id',
        'nama_tamu',
        'qr_token',
        'is_checked_in',
        'checked_in_at',
        'checked_in_by',
    ];

    protected $casts = [
        'is_checked_in' => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Guest $guest) {
            if (empty($guest->qr_token)) {
                $guest->qr_token = static::generateUniqueQrToken();
            }
            if ($guest->is_checked_in === null) {
                $guest->is_checked_in = false;
            }
        });
    }

    /**
     * Generate a cryptographically random, non-guessable QR token
     * with no personal data or internal IDs embedded.
     */
    public static function generateUniqueQrToken(): string
    {
        do {
            $token = bin2hex(random_bytes(24));
        } while (static::where('qr_token', $token)->exists());

        return $token;
    }

    public function undangan()
    {
        return $this->belongsTo(Undangan::class);
    }
}
