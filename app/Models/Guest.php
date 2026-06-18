<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
protected $fillable = [
        'undangan_id',
        'nama_tamu',
    ];

    public function undangan()
    {
        return $this->belongsTo(Undangan::class);
    }
}
