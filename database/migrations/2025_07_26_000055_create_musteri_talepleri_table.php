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
            $table->foreignUuid('sorumlu_personel_id')->nullable()->constrained('personel')->onDelete('set null');
            
            $table->string('baslik');
            $table->text('aciklama')->nullable();
            
            // Mülk bilgileri
            $table->string('mulk_kategorisi', 50); // arsa, isyeri, konut, turistik_tesis
            $table->string('mulk_alt_tipi', 100)->nullable(); // Daire, Villa, Ofis, vb.
            
            // Talep durumu
            $table->string('durum', 50)->default('aktif'); // aktif, beklemede, eslesti, tamamlandi, iptal_edildi, arsivlendi
            $table->integer('oncelik')->nullable()->comment('1=Çok Yüksek, 2=Yüksek, 3=Normal, 4=Düşük, 5=Çok Düşük');
            
            // Fiyat aralığı
            $table->decimal('min_fiyat', 15, 2)->nullable();
            $table->decimal('max_fiyat', 15, 2)->nullable();
            
            // M2 aralığı
            $table->integer('min_m2')->nullable();
            $table->integer('max_m2')->nullable();
            
            // JSON alanları
            $table->json('lokasyon_tercihleri')->nullable()->comment('Şehir, ilçe, semt tercihleri');
            $table->json('ozellik_kriterleri')->nullable()->comment('Mülk özellik kriterleri');
            $table->json('ozel_gereksinimler')->nullable()->comment('Özel gereksinimler listesi');
            $table->json('notlar')->nullable()->comment('Talep notları');
            $table->json('metadata')->nullable()->comment('Ek bilgiler');
            
            // Tarihler
            $table->timestamp('son_aktivite_tarihi')->nullable();
            $table->timestamp('hedef_tarih')->nullable();
            $table->timestamp('tamamlanma_tarihi')->nullable();
            
            // Audit trail
            $table->foreignUuid('olusturan_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('guncelleyen_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->boolean('aktif_mi')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // İndeksler
            $table->index(['musteri_id', 'durum']);
            $table->index(['sorumlu_personel_id', 'durum']);
            $table->index(['mulk_kategorisi', 'durum']);
            $table->index(['durum', 'oncelik']);
            $table->index(['min_fiyat', 'max_fiyat']);
            $table->index(['min_m2', 'max_m2']);
            $table->index('son_aktivite_tarihi');
            $table->index('hedef_tarih');
            $table->index(['aktif_mi', 'durum']);
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