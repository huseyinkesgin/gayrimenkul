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
        Schema::create('musteri_talepleri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('musteri_id')->constrained('musteri')->onDelete('cascade');
            $table->foreignUuid('personel_id')->constrained('personel')->onDelete('cascade');

            $table->enum('mulk_kategorisi', ['arsa', 'isyeri', 'konut', 'turistik_tesis']);
            $table->string('alt_kategori', 50)->nullable();

            // Metrekare aralığı
            $table->decimal('min_metrekare', 10, 2)->nullable();
            $table->decimal('max_metrekare', 10, 2)->nullable();

            // Fiyat aralığı
            $table->decimal('min_fiyat', 15, 2)->nullable();
            $table->decimal('max_fiyat', 15, 2)->nullable();
            $table->string('para_birimi', 3)->default('TRY');

            // JSON fields for flexible data
            $table->json('lokasyon_tercihleri')->nullable()->comment('Şehir, ilçe, semt tercihleri');
            $table->json('ozel_gereksinimler')->nullable()->comment('Özel istekler ve gereksinimler');

            $table->enum('durum', ['aktif', 'beklemede', 'tamamlandi', 'iptal'])->default('aktif');
            $table->tinyInteger('oncelik_seviyesi')->default(5)->comment('1-10 arası öncelik');

            // Talep detayları
            $table->text('aciklama')->nullable();
            $table->timestamp('son_takip_tarihi')->nullable();
            $table->timestamp('hedef_tarih')->nullable()->comment('Müşterinin hedeflediği tarih');

            // Audit trail
            $table->foreignUuid('olusturan_id')->nullable();
            $table->foreignUuid('guncelleyen_id')->nullable();

            $table->boolean('aktif_mi')->default(true);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();

            // İndeksler
            $table->index(['musteri_id', 'durum']);
            $table->index(['personel_id', 'durum']);
            $table->index(['mulk_kategorisi', 'durum']);
            $table->index(['durum', 'oncelik_seviyesi']);
            $table->index(['min_fiyat', 'max_fiyat']);
            $table->index(['min_metrekare', 'max_metrekare']);
            $table->index('hedef_tarih');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musteri_talepleri');
    }
};
