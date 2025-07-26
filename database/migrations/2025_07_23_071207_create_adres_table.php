<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('adres', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Morph ilişkisi için (UUID destekli)
            $table->uuid('addressable_id');
            $table->string('addressable_type');

            // Adres bilgileri
            $table->string('adres_adi')->nullable(); // Ev Adresi, İş Adresi, Fatura Adresi vb.
            $table->text('adres_detay'); // Detaylı adres bilgisi

            // Lokasyon ilişkileri
            $table->foreignUuid('sehir_id')->constrained('sehir')->onDelete('cascade');
            $table->foreignUuid('ilce_id')->constrained('ilce')->onDelete('cascade');
            $table->foreignUuid('semt_id')->constrained('semt')->onDelete('cascade');
            $table->foreignUuid('mahalle_id')->constrained('mahalle')->onDelete('cascade');

            // Ek bilgiler
            $table->boolean('varsayilan_mi')->default(false); // Varsayılan adres mi?
            $table->boolean('aktif_mi')->default(true);

            // Zaman damgaları
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();

            // İndeksler
            $table->index(['addressable_type', 'addressable_id']);
            $table->index(['sehir_id', 'ilce_id', 'semt_id', 'mahalle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adres');
    }
};
