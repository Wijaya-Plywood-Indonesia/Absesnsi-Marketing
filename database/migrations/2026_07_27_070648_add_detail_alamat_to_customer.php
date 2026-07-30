<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('jalan')->nullable()->after('address');
            $table->string('desa')->nullable()->after('jalan');
            $table->string('kecamatan')->nullable()->after('desa');
            $table->string('kota')->nullable()->after('kecamatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['jalan', 'desa', 'kecamatan', 'kota']);
        });
    }
};
