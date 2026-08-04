<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah toko_id di users (satu marketer ditugaskan ke satu toko)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('toko_id')
                ->nullable()
                ->after('id')
                ->constrained('tokos')
                ->nullOnDelete();
        });

        // 2. Hapus marketer_id dari tokos (arah relasi yang salah sebelumnya)
        Schema::table('tokos', function (Blueprint $table) {
            $table->dropForeign(['marketer_id']);
            $table->dropColumn('marketer_id');
        });
    }

    public function down(): void
    {
        Schema::table('tokos', function (Blueprint $table) {
            $table->foreignId('marketer_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['toko_id']);
            $table->dropColumn('toko_id');
        });
    }
};
