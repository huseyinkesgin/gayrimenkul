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
        Schema::create('hatirlatmalar', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic relationship - Hangi modele ait hatırlatma
            $table->uuid('hatirlatilacak_id');
            $table->string('hatirlatilacak_type');

            $table->foreignUuid('personel_id')->constrained('personel')->onDelete('cascade');
            $table->string('baslik');
            $table->text('aciklama')->nullable();

            $table->timestamp('hatirlatma_tarihi');
            $table->enum('hatirlatma_tipi', [
                'arama',
                'toplanti',
                'email',
                'ziyaret',
                'sms',
                'gorev',
                'diger'
            ]);

            $table->enum('durum', [
                'beklemede',
                'tamamlandi',
                'iptal_edildi',
                'ertelendi'
            ])->default('beklemede');

            $table->timestamp('tamamlanma_tarihi')->nullable();
            $table->text('sonuc')->nullable();

            // Bildirim ayarları
            $table->json('bildirim_ayarlari')->nullable()->comment('Bildirim zamanları ve kanalları');
            $table->boolean('otomatik_bildirim')->default(false);
            $table->timestamp('son_bildirim_tarihi')->nullable();

            // Tekrarlama ayarları
            $table->enum('tekrarlama_tipi', ['yok', 'gunluk', 'haftalik', 'aylik', 'yillik'])->default('yok');
            $table->json('tekrarlama_ayarlari')->nullable();

            // Öncelik
            $table->tinyInteger('oncelik')->default(5)->comment('1-10 arası öncelik');

            // Audit trail
            $table->foreignUuid('olusturan_id')->nullable();
            $table->foreignUuid('guncelleyen_id')->nullable();

            $table->boolean('aktif_mi')->default(true);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();

            // İndeksler
            $table->index(['hatirlatilacak_id', 'hatirlatilacak_type']);
            $table->index(['personel_id', 'durum']);
            $table->index(['hatirlatma_tarihi', 'durum']);
            $table->index(['hatirlatma_tipi', 'durum']);
            $table->index(['durum', 'oncelik']);
            $table->index('tamamlanma_tarihi');
            $table->index(['otomatik_bildirim', 'hatirlatma_tarihi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hatirlatmalar');
    }
};
