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
        Schema::create('talep_portfoy_eslestirmeleri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('talep_id')->constrained('musteri_talepleri')->onDelete('cascade');
            $table->foreignUuid('mulk_id')->constrained('mulkler')->onDelete('cascade');
            $table->string('mulk_type', 50); // Hangi mülk tipine ait

            $table->decimal('eslestirme_skoru', 3, 2)->nullable()->comment('0.00 - 1.00 arası eşleştirme skoru');
            $table->json('eslestirme_detaylari')->nullable()->comment('Eşleştirme kriterleri ve skorları');

            $table->enum('durum', ['yeni', 'incelendi', 'sunuldu', 'reddedildi', 'kabul_edildi'])->default('yeni');
            $table->text('personel_notu')->nullable();

            // Sunum bilgileri
            $table->timestamp('sunum_tarihi')->nullable();
            $table->foreignUuid('sunan_personel_id')->nullable()->constrained('personel')->onDelete('set null');
            $table->text('musteri_geri_bildirimi')->nullable();

            // Audit trail
            $table->foreignUuid('olusturan_id')->nullable();
            $table->foreignUuid('guncelleyen_id')->nullable();

            $table->boolean('aktif_mi')->default(true);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));

            // İndeksler
            $table->index(['talep_id', 'durum']);
            $table->index(['mulk_id', 'durum']);
            $table->index(['eslestirme_skoru', 'durum']);
            $table->index('sunum_tarihi');
            $table->index(['sunan_personel_id', 'sunum_tarihi'], 'eslestirme_sunan_sunum_idx');


            // Unique constraint - Aynı talep için aynı mülk sadece bir kez eşleştirilebilir
            $table->unique(['talep_id', 'mulk_id'], 'unique_talep_mulk_eslestirme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talep_portfoy_eslestirmeleri');
    }
};
