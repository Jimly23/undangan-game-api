<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rsvp extends Model
{
 protected $fillable = [
        'undangan_id',
        'nama',
        'nomor_telepon',
        'email',
        'status',
        'pesan',
        'foto',
    ];

    public function undangan()
    {
        return $this->belongsTo(Undangan::class);
    }
}
