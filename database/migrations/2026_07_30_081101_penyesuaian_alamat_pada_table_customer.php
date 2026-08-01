<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Gabungkan isi 'jalan' dan 'desa' lama ke 'address',
        //    supaya data lama tidak hilang saat kolomnya dihapus.
        DB::table('customers')->orderBy('id')->chunk(50, function ($customers) {
            foreach ($customers as $customer) {
                $parts = array_filter([
                    $customer->address,
                    $customer->jalan,
                    $customer->desa,
                ]);

                $merged = implode(', ', $parts);

                if ($merged !== ($customer->address ?? '')) {
                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update(['address' => $merged ?: null]);
                }
            }
        });

        // 2. Baru hapus kolom yang sudah tidak dipakai.
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['jalan', 'desa']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('jalan')->nullable()->after('address');
            $table->string('desa')->nullable()->after('jalan');
        });
    }
};
