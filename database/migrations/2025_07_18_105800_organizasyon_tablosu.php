<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Şubeler tablosu
        Schema::create('sube', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ad');
            $table->string('kod')->unique();
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->integer('siralama')->default(0);
            $table->boolean('aktif_mi')->default(true);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
        });

        // Departmanlar tablosu
        Schema::create('departman', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ad');
            $table->text('aciklama')->nullable();
            $table->uuid('yonetici_id')->nullable();
            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
        });

        Schema::create('pozisyon', function (Blueprint $table) {
            $table->uuid('id')->primary(); // DÜZELTİLDİ
            $table->string('ad');
            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
        });

        Schema::create('personel_rol', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ad');
            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
        });

        // Personel tablosu
        Schema::create('personel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kisi_id')->constrained('kisi')->onDelete('cascade');
            $table->foreignUuid('sube_id')->constrained('sube')->onDelete('cascade'); // DÜZELTİLDİ
            $table->foreignUuid('departman_id')->constrained('departman')->onDelete('cascade');
            $table->foreignUuid('pozisyon_id')->constrained('pozisyon')->onDelete('cascade');
            $table->date('ise_baslama_tarihi');
            $table->date('isten_ayrilma_tarihi')->nullable();
            $table->string('calisma_durumu')->default('Aktif');
            $table->string('calisma_sekli')->nullable();
            $table->string('personel_no')->unique();
            $table->integer('siralama')->default(0);
            $table->boolean('aktif_mi')->default(true);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
        });

        // Personel-Role ilişkisi tablosu
        Schema::create('personel_personel_rolu', function (Blueprint $table) {
            $table->foreignUuid('personel_id')->constrained('personel')->onDelete('cascade');
            $table->foreignUuid('personel_rol_id')->constrained('personel_rol')->onDelete('cascade');
            $table->primary(['personel_id', 'personel_rol_id']); // İYİLEŞTİRME
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personel_personel_rolu');
        Schema::dropIfExists('personel');
        Schema::dropIfExists('personel_rol');   // DÜZELTİLDİ
        Schema::dropIfExists('pozisyon');      // DÜZELTİLDİ
        Schema::dropIfExists('departman');     // DÜZELTİLDİ
        Schema::dropIfExists('sube');          // DÜZELTİLDİ
    }
};
