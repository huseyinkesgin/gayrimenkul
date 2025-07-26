<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Şehir tablosu
        Schema::create('sehir', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ad');
            $table->string('plaka_kodu', 2)->unique()->nullable();
            $table->string('telefon_kodu', 4)->nullable();
            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
        });

        // İlçe tablosu
        Schema::create('ilce', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sehir_id')->constrained('sehir')->onDelete('cascade');
            $table->string('ad');
            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
        });

        // Semt tablosu
        Schema::create('semt', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ilce_id')->constrained('ilce')->onDelete('cascade');
            $table->string('ad');
            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
        });

        // Mahalle tablosu
        Schema::create('mahalle', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('semt_id')->constrained('semt')->onDelete('cascade');
            $table->string('ad');
            $table->string('posta_kodu', 5)->nullable();
            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahalle');
        Schema::dropIfExists('semt');
        Schema::dropIfExists('ilce');
        Schema::dropIfExists('sehir');
    }
};
