<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'tanggal',
        'jam',
        'hasil',
        'catatan',
        'foto',
        'latitude',
        'longitude',
        'accuracy',
        'is_outside_area',
    ];

    protected $casts = [
        'is_outside_area' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
