<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $fillable = [
        'user_id',
        'tanggal',
        'jam',
        'tipe',
        'foto',
        'latitude',
        'longitude',
        'accuracy',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: absen hari ini untuk user tertentu.
     */
    public function scopeTodayForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->where('tanggal', now()->toDateString());
    }
}
