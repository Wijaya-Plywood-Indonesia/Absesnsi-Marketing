<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayahs', function (Blueprint $table) {
            $table->id();
            $table->string('kota');
            $table->string('kecamatan');
            $table->timestamps();

            $table->unique(['kota', 'kecamatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayahs');
    }
};
