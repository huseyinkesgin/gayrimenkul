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
        Schema::create('musteri_mulk_iliskileri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('musteri_id')->constrained('musteri')->onDelete('cascade');
            $table->foreignUuid('mulk_id')->constrained('mulkler')->onDelete('cascade');
            $table->string('mulk_type', 50); // Hangi mülk tipine ait

            $table->enum('iliski_tipi', [
                'ilgileniyor',
                'teklif_verdi',
                'gorustu',
                'satin_aldi',
                'kiraya_verdi',
                'kiraya_aldi'
            ])->default('ilgileniyor');

            $table->timestamp('baslangic_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->enum('durum', ['aktif', 'pasif', 'tamamlandi'])->default('aktif');
            $table->tinyInteger('ilgi_seviyesi')->default(5)->comment('1-10 arası ilgi seviyesi');
            $table->text('notlar')->nullable();

            // Audit trail
            $table->uuid('olusturan_id')->nullable();
            $table->uuid('guncelleyen_id')->nullable();

            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));

            // İndeksler
            $table->index(['musteri_id', 'durum']);
            $table->index(['mulk_id', 'durum']);
            $table->index(['iliski_tipi', 'durum']);
            $table->index('ilgi_seviyesi');
            $table->index('baslangic_tarihi');

            // Unique constraint - Aynı müşteri aynı mülk için aynı anda sadece bir aktif ilişki
            $table->unique(['musteri_id', 'mulk_id', 'durum'], 'unique_aktif_iliski');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musteri_mulk_iliskileri');
    }
};
