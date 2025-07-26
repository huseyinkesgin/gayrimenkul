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
        Schema::create('notlar', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic relationship - Hangi modele ait not
            $table->uuid('notable_id');
            $table->string('notable_type');

            $table->foreignUuid('personel_id')->constrained('personel')->onDelete('cascade');
            $table->string('baslik')->nullable();
            $table->text('icerik');

            $table->enum('kategori', [
                'genel',
                'gorusme',
                'takip',
                'uyari',
                'bilgi',
                'karar'
            ])->default('genel');

            $table->tinyInteger('oncelik')->default(5)->comment('1-10 arası öncelik');
            $table->boolean('gizli_mi')->default(false)->comment('Sadece yazan görebilir');
            $table->boolean('sabitlenmis_mi')->default(false)->comment('Üstte gösterilir');

            // Etiketler
            $table->json('etiketler')->nullable()->comment('Not etiketleri');

            // Dosya ekleri
            $table->json('ekler')->nullable()->comment('Ek dosya bilgileri');

            // Audit trail
            $table->foreignUuid('olusturan_id')->nullable();
            $table->foreignUuid('guncelleyen_id')->nullable();

            $table->boolean('aktif_mi')->default(true);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();

            // İndeksler
            $table->index(['notable_id', 'notable_type']);
            $table->index(['personel_id', 'kategori']);
            $table->index(['kategori', 'oncelik']);
            $table->index(['gizli_mi', 'aktif_mi']);
            $table->index(['sabitlenmis_mi', 'oncelik']);
            $table->index('olusturma_tarihi');

            // Full-text search index for content
            $table->fullText(['baslik', 'icerik'], 'notlar_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notlar');
    }
};
