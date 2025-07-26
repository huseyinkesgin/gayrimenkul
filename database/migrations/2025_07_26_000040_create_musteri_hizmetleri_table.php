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
        Schema::create('musteri_hizmetleri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('musteri_id')->constrained('musteri')->onDelete('cascade');
            $table->foreignUuid('personel_id')->constrained('personel')->onDelete('cascade');

            $table->string('hizmet_tipi', 50);
            $table->timestamp('hizmet_tarihi');
            $table->timestamp('bitis_tarihi')->nullable();
            $table->string('lokasyon')->nullable();
            $table->json('katilimcilar')->nullable();

            $table->text('aciklama')->nullable();
            $table->text('sonuc')->nullable();
            $table->string('sonuc_tipi', 50)->nullable();

            $table->json('degerlendirme')->nullable()->comment('{"tip": "olumlu/olumsuz", "puan": 1-10, "notlar": "..."}');
            $table->timestamp('takip_tarihi')->nullable();
            $table->text('takip_notu')->nullable();

            $table->integer('sure_dakika')->nullable()->comment('Hizmet süresi dakika cinsinden');
            $table->decimal('maliyet', 10, 2)->nullable();
            $table->string('para_birimi', 3)->default('TRY');

            $table->json('etiketler')->nullable();
            $table->json('dosyalar')->nullable();

            $table->foreignUuid('mulk_id')->nullable()->constrained('mulkler')->onDelete('set null');
            $table->string('mulk_type', 50)->nullable();
            $table->uuid('parent_hizmet_id')->nullable();

            // Audit trail
            $table->foreignUuid('olusturan_id')->nullable();
            $table->foreignUuid('guncelleyen_id')->nullable();

            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);

            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();

            // Foreign key
            $table->foreign('parent_hizmet_id')->references('id')->on('musteri_hizmetleri')->onDelete('set null');

            // Indexler
            $table->index(['musteri_id', 'hizmet_tarihi']);
            $table->index(['personel_id', 'hizmet_tarihi']);
            $table->index(['hizmet_tipi', 'hizmet_tarihi']);
            $table->index(['mulk_id', 'hizmet_tarihi']);
            $table->index(['sonuc_tipi']);
            $table->index(['takip_tarihi']);
            $table->index(['maliyet']);
            $table->index(['parent_hizmet_id']);
            $table->index(['bitis_tarihi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musteri_hizmetleri');
    }
};
