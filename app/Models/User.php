<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'role', 'daily_target', 'toko_id', 'validated_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'daily_target' => 'integer',
            'validated_at' => 'datetime',
        ];
    }

    /**
     * Sinkronkan otomatis kolom `role` (string) ke Spatie role setiap kali
     * user disimpan. Dengan ini, kolom `role` tetap jadi sumber kebenaran
     * utama (dipakai di canAccessPanel, canLogin, dst), sementara Filament
     * Shield tetap bisa memeriksa permission lewat sistem Spatie di belakang layar.
     */
    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->wasChanged('role') || $user->wasRecentlyCreated) {
                $role = Role::firstOrCreate([
                    'name' => $user->role,
                    'guard_name' => 'web',
                ]);

                $user->syncRoles([$role]);
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'visitor'], true);
    }

    /**
     * Cek apakah user adalah visitor (atasan) — akses Filament, biasanya
     * read-only sesuai permission yang diatur lewat Filament Shield.
     */
    public function isVisitor(): bool
    {
        return $this->role === 'visitor';
    }

    /**
     * Cek apakah user boleh login sekarang.
     * - validated_at NULL           -> belum diverifikasi admin, tidak boleh login.
     * - validated_at <= sekarang    -> normal, boleh login.
     * - validated_at > sekarang     -> sedang dibanned sampai tanggal tsb, tidak boleh login.
     */
    public function canLogin(): bool
    {
        return $this->validated_at !== null
            && $this->validated_at->lessThanOrEqualTo(now());
    }

    /**
     * Cek apakah user sedang dalam status banned
     * (validated_at diset ke masa depan).
     */
    public function isBanned(): bool
    {
        return $this->validated_at !== null
            && $this->validated_at->isFuture();
    }

    /**
     * Cek apakah user sama sekali belum pernah diverifikasi admin.
     */
    public function isPending(): bool
    {
        return $this->validated_at === null;
    }

    /**
     * Verifikasi / aktifkan user sekarang juga.
     */
    public function verify(): void
    {
        $this->forceFill(['validated_at' => now()])->save();
    }

    /**
     * Ban user sampai tanggal tertentu (default: 7 hari).
     * Set validated_at ke masa depan sehingga canLogin() jadi false.
     */
    public function ban(?\DateTimeInterface $until = null): void
    {
        $this->forceFill([
            'validated_at' => $until ?? now()->addDays(7),
        ])->save();
    }

    /**
     * Cabut verifikasi user (kembali ke status belum diverifikasi).
     */
    public function revoke(): void
    {
        $this->forceFill(['validated_at' => null])->save();
    }

    /**
     * Scope hanya user yang boleh login saat ini.
     */
    public function scopeCanLogin($query)
    {
        return $query->whereNotNull('validated_at')
            ->where('validated_at', '<=', now());
    }

    /**
     * Scope user yang sedang dibanned.
     */
    public function scopeBanned($query)
    {
        return $query->whereNotNull('validated_at')
            ->where('validated_at', '>', now());
    }

    public function toko()
    {
        return $this->belongsTo(Toko::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
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
