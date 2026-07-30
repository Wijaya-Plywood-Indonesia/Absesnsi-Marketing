<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address',
        'jalan',
        'desa',
        'kecamatan',
        'kota',
        'latitude',
        'longitude',
        'pola',
        'jenis',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
