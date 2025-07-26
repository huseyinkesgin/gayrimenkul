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
        // Müşteri Kategorileri tablosu
        Schema::create('musteri_kategori', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('value', 50)->unique(); // enum değeri
            $table->string('label', 100); // görünen ad
            $table->string('description', 500)->nullable(); // açıklama
            $table->string('color', 20)->nullable(); // renk
            $table->string('icon', 100)->nullable(); // ikon
            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->uuid('olusturan_id')->nullable();
            $table->uuid('guncelleyen_id')->nullable();
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
            
            // İndeksler
            $table->index(['aktif_mi', 'siralama']);
            $table->index(['value']);
        });


        // Müşteri tablosu
        Schema::create('musteri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kisi_id')->constrained('kisi')->onDelete('cascade');
            $table->enum('tip', ['bireysel', 'kurumsal'])->default('bireysel');
            $table->string('musteri_no', 50)->unique()->nullable();
            $table->timestamp('kayit_tarihi')->nullable();
            $table->string('kaynak', 100)->nullable();
            $table->uuid('referans_musteri_id')->nullable();
            $table->decimal('potansiyel_deger', 15, 2)->nullable();
            $table->string('para_birimi', 3)->default('TRY');
            $table->text('notlar')->nullable();
            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->uuid('olusturan_id')->nullable();
            $table->uuid('guncelleyen_id')->nullable();
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
            
            // Foreign keys
            $table->foreign('referans_musteri_id')->references('id')->on('musteri')->onDelete('set null');
            
            // İndeksler
            $table->index(['tip', 'aktif_mi']);
            $table->index(['kayit_tarihi']);
            $table->index(['kaynak']);
            $table->index(['potansiyel_deger']);
            $table->index(['referans_musteri_id']);
        });

        //Firma tablosu
        Schema::create('firma', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('unvan');
            $table->string('ticaret_unvani')->nullable();
            $table->string('vergi_no', 10)->unique();
            $table->string('vergi_dairesi', 100);
            $table->string('mersis_no', 16)->nullable();
            $table->string('faaliyet_kodu', 10)->nullable();
            $table->string('telefon', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->date('kuruluş_tarihi')->nullable();
            $table->integer('çalışan_sayisi')->nullable();
            $table->decimal('sermaye', 15, 2)->nullable();
            $table->string('para_birimi', 3)->default('TRY');
            $table->string('sektor', 100)->nullable();
            $table->text('notlar')->nullable();
            $table->boolean('aktif_mi')->default(true);
            $table->integer('siralama')->default(0);
            $table->uuid('olusturan_id')->nullable();
            $table->uuid('guncelleyen_id')->nullable();
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
            
            // İndeksler
            $table->index(['aktif_mi', 'sektor']);
            $table->index(['çalışan_sayisi']);
            $table->index(['kuruluş_tarihi']);
            $table->index(['unvan']);
            $table->index(['olusturma_tarihi']);
        });

        // Müşteri Firma ilişkisi
        Schema::create('musteri_firma', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('musteri_id')->constrained('musteri')->onDelete('cascade');
            $table->foreignUuid('firma_id')->constrained('firma')->onDelete('cascade');
            $table->string('pozisyon', 100)->nullable();
            $table->integer('yetki_seviyesi')->default(1); // 1-10 arası
            $table->timestamp('baslangic_tarihi')->useCurrent();
            $table->timestamp('bitis_tarihi')->nullable();
            $table->boolean('aktif_mi')->default(true);
            $table->text('notlar')->nullable();
            $table->uuid('olusturan_id')->nullable();
            $table->uuid('guncelleyen_id')->nullable();
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();

            // İndeksler
            $table->index(['musteri_id', 'aktif_mi']);
            $table->index(['firma_id', 'aktif_mi']);
            $table->index(['yetki_seviyesi']);
            $table->index(['baslangic_tarihi']);

            // Unique constraint - bir müşteri aynı firmada aynı anda sadece bir aktif ilişkiye sahip olabilir
            $table->unique(['musteri_id', 'firma_id', 'aktif_mi'], 'unique_active_musteri_firma');
        });

        // Müşteri Kategori ilişkisi
        Schema::create('musteri_musteri_kategori', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('musteri_id')->constrained('musteri')->onDelete('cascade');
            $table->foreignUuid('musteri_kategori_id')->constrained('musteri_kategori')->onDelete('cascade');
            $table->timestamp('baslangic_tarihi')->useCurrent();
            $table->timestamp('bitis_tarihi')->nullable();
            $table->boolean('aktif_mi')->default(true);
            $table->text('notlar')->nullable();
            $table->uuid('olusturan_id')->nullable();
            $table->uuid('guncelleyen_id')->nullable();
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();

            // İndeksler
            $table->index(['musteri_id', 'aktif_mi']);
            $table->index(['musteri_kategori_id', 'aktif_mi']);
            $table->index(['baslangic_tarihi']);

            // Unique constraint - bir müşteri aynı kategoriye aynı anda sadece bir kez sahip olabilir
            $table->unique(['musteri_id', 'musteri_kategori_id'], 'unique_musteri_kategori');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musteri_musteri_kategori');
        Schema::dropIfExists('musteri_firma');
        Schema::dropIfExists('firma');
        Schema::dropIfExists('musteri');
        Schema::dropIfExists('musteri_kategori');
    }
};
