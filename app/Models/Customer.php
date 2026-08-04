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
        'kecamatan',
        'kota',
        'latitude',
        'longitude',
        'pola',
        'jenis',
        'status',
        'foto',
    ];

    // kalau pakai kolom json untuk multi foto, tambahkan casts:
    // protected $casts = [
    //     'foto' => 'array',
    // ];

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
