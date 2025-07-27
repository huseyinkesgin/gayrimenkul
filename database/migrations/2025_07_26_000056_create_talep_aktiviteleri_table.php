<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talep_aktiviteleri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('talep_id')->constrained('musteri_talepleri')->onDelete('cascade');
            
            $table->string('tip', 50); // olusturuldu, durum_degisiklik, not_eklendi, vb.
            $table->json('detaylar')->nullable()->comment('Aktivite detayları');
            
            $table->foreignUuid('olusturan_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // İndeksler
            $table->index(['talep_id', 'created_at']);
            $table->index(['tip', 'created_at']);
            $table->index('olusturan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talep_aktiviteleri');
    }
};