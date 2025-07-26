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
        Schema::create('dokumanlar', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic relationship - Hangi modele ait döküman
            $table->uuid('documentable_id');
            $table->string('documentable_type');

            $table->string('url'); // Dosya yolu
            $table->enum('dokuman_tipi', [
                'tapu',
                'autocad',
                'proje_resmi',
                'ruhsat',
                'imar_plani',
                'yapi_kullanim',
                'isyeri_acma',
                'cevre_izni',
                'yangin_raporu',
                'diger'
            ]);

            $table->string('baslik')->nullable();
            $table->text('aciklama')->nullable();
            $table->string('dosya_adi');
            $table->string('orijinal_dosya_adi')->nullable();
            $table->bigInteger('dosya_boyutu')->nullable()->comment('Byte cinsinden');
            $table->string('mime_type', 100)->nullable();
            $table->string('dosya_uzantisi', 10)->nullable();

            // Dosya hash'i - Duplicate kontrolü için
            $table->string('dosya_hash', 64)->nullable();

            // Versiyonlama
            $table->integer('versiyon')->default(1);
            $table->uuid('ana_dokuman_id')->nullable()->comment('Ana dökümanın ID\'si');

            // Güvenlik
            $table->boolean('gizli_mi')->default(false);
            $table->json('erisim_izinleri')->nullable()->comment('Kimler erişebilir');

            // Metadata
            $table->json('metadata')->nullable()->comment('Ek dosya bilgileri');
            $table->timestamp('son_erisim_tarihi')->nullable();
            $table->integer('erisim_sayisi')->default(0);

            // Audit trail
            $table->foreignUuid('olusturan_id')->nullable();
            $table->foreignUuid('guncelleyen_id')->nullable();

            $table->boolean('aktif_mi')->default(true);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();

            // İndeksler
            $table->index(['documentable_id', 'documentable_type']);
            $table->index(['dokuman_tipi', 'aktif_mi']);
            $table->index('dosya_hash');
            $table->index(['ana_dokuman_id', 'versiyon']);
            $table->index(['gizli_mi', 'aktif_mi']);
            $table->index('son_erisim_tarihi');

            // Full-text search index
            $table->fullText(['baslik', 'aciklama', 'dosya_adi'], 'dokumanlar_fulltext');

            // Foreign key for versioning
            $table->foreign('ana_dokuman_id')->references('id')->on('dokumanlar')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumanlar');
    }
};
