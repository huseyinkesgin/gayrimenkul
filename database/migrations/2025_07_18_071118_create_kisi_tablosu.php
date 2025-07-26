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
        Schema::create('kisi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ad');
            $table->string('soyad');
            $table->string('tc_kimlik_no')->default('11111111111')->unique();
            $table->date('dogum_tarihi')->nullable();
            $table->string('cinsiyet')->nullable(); // 'Erkek', 'Kadın', 'Diğer'
            $table->string('dogum_yeri')->nullable();
            $table->string('medeni_hali')->nullable(); // 'Bekar', 'Evli', 'Dul', 'Boşanmış'
            $table->string('email')->nullable();
            $table->string('telefon')->nullable();
            $table->boolean('aktif_mi')->default(true);
            $table->timestamp('olusturma_tarihi')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('guncelleme_tarihi')->default(DB::raw('CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes('silinme_tarihi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kisi');
    }
};
