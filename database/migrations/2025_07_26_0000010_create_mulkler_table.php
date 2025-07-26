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
        Schema::create('mulkler', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('mulk_type', 50); // STI için (Single Table Inheritance)
            $table->string('baslik');
            $table->text('aciklama')->nullable();
            $table->decimal('fiyat', 15, 2)->nullable();
            $table->string('para_birimi', 3)->default('TRY');
            $table->decimal('metrekare', 10, 2)->nullable();
            $table->enum('durum', ['aktif', 'pasif', 'satildi', 'kiralandi'])->default('aktif');
            $table->timestamp('yayinlanma_tarihi')->nullable();

            // Audit trail
            //$table->foreignUuid('olusturan_id')->nullable()->constrained('users')->onDelete('set null');
            //$table->foreignUuid('guncelleyen_id')->nullable()->constrained('users')->onDelete('set null');

            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();

            // İndeksler
            $table->index(['mulk_type', 'aktif_mi']);
            $table->index(['durum', 'aktif_mi']);
            $table->index(['fiyat', 'metrekare']);
            $table->index('yayinlanma_tarihi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mulkler');
    }
};
