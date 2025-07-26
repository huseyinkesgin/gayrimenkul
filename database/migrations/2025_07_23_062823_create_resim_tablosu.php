<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resim', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->enum('kategori', [
                'galeri',
                'avatar',
                'logo',
                'uydu',
                'oznitelik',
                'buyuksehir',
                'egim',
                'eimar'
            ])->default('galeri');
            $table->string('baslik')->nullable();
            $table->text('aciklama')->nullable();
            $table->timestamp('cekim_tarihi')->nullable();
            $table->bigInteger('dosya_boyutu')->nullable()->comment('Byte cinsinden');
            $table->integer('genislik')->nullable()->comment('Pixel cinsinden');
            $table->integer('yukseklik')->nullable()->comment('Pixel cinsinden');
            $table->string('url')->nullable();
            $table->uuid('imageable_id');
            $table->string('imageable_type');
            $table->boolean('aktif_mi')->default(true);

            // Dosya hash'i - Duplicate kontrolü için
            $table->string('dosya_hash', 64)->nullable();

            // Thumbnail bilgileri
            $table->string('thumbnail_url')->nullable();
            $table->json('thumbnail_sizes')->nullable()->comment('Farklı boyutlardaki thumbnail\'ler');

            // Metadata
            $table->json('metadata')->nullable()->comment('EXIF ve diğer metadata');

            // Audit trail sütunları ekle
            $table->uuid('olusturan_id')->nullable();
            $table->uuid('guncelleyen_id')->nullable();

            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();



            // Siralama sütunu ekle
            $table->integer('siralama')->default(0);

            $table->index(['imageable_id', 'imageable_type']);


        });
        Schema::table('resim', function (Blueprint $table) {
            $table->index(['kategori', 'aktif_mi']);
            $table->index('dosya_hash');
            $table->index(['imageable_id', 'imageable_type', 'kategori']);
            $table->index('cekim_tarihi');
            $table->index('siralama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resim');
    }
};
