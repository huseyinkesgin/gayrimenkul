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
        Schema::create('mulk_ozellikleri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mulk_id')->constrained('mulkler')->onDelete('cascade');
            $table->string('mulk_type', 50); // Hangi mülk tipine ait
            $table->string('ozellik_adi', 100);
            $table->json('ozellik_degeri'); // Esnek veri saklama
            $table->enum('ozellik_tipi', ['sayi', 'metin', 'boolean', 'liste'])->default('metin');
            $table->string('birim', 20)->nullable(); // m2, adet, vs.

            // Audit trail
            $table->uuid('olusturan_id')->nullable();
            $table->uuid('guncelleyen_id')->nullable();

            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();

            // İndeksler
            $table->index(['mulk_id', 'aktif_mi']);
            $table->index(['mulk_type', 'ozellik_adi']);
            $table->index('ozellik_tipi');

            // Unique constraint - Aynı mülk için aynı özellik adı tekrar edemez
            $table->unique(['mulk_id', 'ozellik_adi'], 'unique_mulk_ozellik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mulk_ozellikleri');
    }
};
