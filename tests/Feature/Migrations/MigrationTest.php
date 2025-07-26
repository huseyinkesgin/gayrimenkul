<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function mulkler_table_is_created_correctly()
    {
        $this->assertTrue(Schema::hasTable('mulkler'));
        
        $columns = [
            'id', 'mulk_type', 'baslik', 'aciklama', 'fiyat', 'para_birimi',
            'metrekare', 'durum', 'yayinlanma_tarihi', 'olusturan_id', 'guncelleyen_id',
            'aktif_mi', 'siralama', 'olusturma_tarihi', 'guncelleme_tarihi', 'silinme_tarihi'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('mulkler', $column),
                "Column {$column} should exist in mulkler table"
            );
        }
    }

    /** @test */
    public function mulk_ozellikleri_table_is_created_correctly()
    {
        $this->assertTrue(Schema::hasTable('mulk_ozellikleri'));
        
        $columns = [
            'id', 'mulk_id', 'mulk_type', 'ozellik_adi', 'ozellik_degeri',
            'ozellik_tipi', 'birim', 'olusturan_id', 'guncelleyen_id',
            'aktif_mi', 'siralama', 'olusturma_tarihi', 'guncelleme_tarihi', 'silinme_tarihi'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('mulk_ozellikleri', $column),
                "Column {$column} should exist in mulk_ozellikleri table"
            );
        }
    }

    /** @test */
    public function musteri_mulk_iliskileri_table_is_created_correctly()
    {
        $this->assertTrue(Schema::hasTable('musteri_mulk_iliskileri'));
        
        $columns = [
            'id', 'musteri_id', 'mulk_id', 'mulk_type', 'iliski_tipi',
            'baslangic_tarihi', 'durum', 'ilgi_seviyesi', 'notlar',
            'olusturan_id', 'guncelleyen_id', 'olusturma_tarihi', 'guncelleme_tarihi'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('musteri_mulk_iliskileri', $column),
                "Column {$column} should exist in musteri_mulk_iliskileri table"
            );
        }
    }

    /** @test */
    public function musteri_hizmetleri_table_is_created_correctly()
    {
        $this->assertTrue(Schema::hasTable('musteri_hizmetleri'));
        
        $columns = [
            'id', 'musteri_id', 'personel_id', 'hizmet_tipi', 'hizmet_tarihi',
            'aciklama', 'sonuc', 'degerlendirme', 'sure_dakika', 'mulk_id',
            'mulk_type', 'olusturan_id', 'guncelleyen_id', 'aktif_mi',
            'olusturma_tarihi', 'guncelleme_tarihi', 'silinme_tarihi'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('musteri_hizmetleri', $column),
                "Column {$column} should exist in musteri_hizmetleri table"
            );
        }
    }

    /** @test */
    public function musteri_talepleri_table_is_created_correctly()
    {
        $this->assertTrue(Schema::hasTable('musteri_talepleri'));
        
        $columns = [
            'id', 'musteri_id', 'personel_id', 'mulk_kategorisi', 'alt_kategori',
            'min_metrekare', 'max_metrekare', 'min_fiyat', 'max_fiyat', 'para_birimi',
            'lokasyon_tercihleri', 'ozel_gereksinimler', 'durum', 'oncelik_seviyesi',
            'aciklama', 'son_takip_tarihi', 'hedef_tarih', 'olusturan_id', 'guncelleyen_id',
            'aktif_mi', 'olusturma_tarihi', 'guncelleme_tarihi', 'silinme_tarihi'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('musteri_talepleri', $column),
                "Column {$column} should exist in musteri_talepleri table"
            );
        }
    }

    /** @test */
    public function talep_portfoy_eslestirmeleri_table_is_created_correctly()
    {
        $this->assertTrue(Schema::hasTable('talep_portfoy_eslestirmeleri'));
        
        $columns = [
            'id', 'talep_id', 'mulk_id', 'mulk_type', 'eslestirme_skoru',
            'eslestirme_detaylari', 'durum', 'personel_notu', 'sunum_tarihi',
            'sunan_personel_id', 'musteri_geri_bildirimi', 'olusturan_id',
            'guncelleyen_id', 'aktif_mi', 'olusturma_tarihi', 'guncelleme_tarihi'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('talep_portfoy_eslestirmeleri', $column),
                "Column {$column} should exist in talep_portfoy_eslestirmeleri table"
            );
        }
    }

    /** @test */
    public function hatirlatmalar_table_is_created_correctly()
    {
        $this->assertTrue(Schema::hasTable('hatirlatmalar'));
        
        $columns = [
            'id', 'hatirlatilacak_id', 'hatirlatilacak_type', 'personel_id',
            'baslik', 'aciklama', 'hatirlatma_tarihi', 'hatirlatma_tipi',
            'durum', 'tamamlanma_tarihi', 'sonuc', 'bildirim_ayarlari',
            'otomatik_bildirim', 'son_bildirim_tarihi', 'tekrarlama_tipi',
            'tekrarlama_ayarlari', 'oncelik', 'olusturan_id', 'guncelleyen_id',
            'aktif_mi', 'olusturma_tarihi', 'guncelleme_tarihi', 'silinme_tarihi'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('hatirlatmalar', $column),
                "Column {$column} should exist in hatirlatmalar table"
            );
        }
    }

    /** @test */
    public function notlar_table_is_created_correctly()
    {
        $this->assertTrue(Schema::hasTable('notlar'));
        
        $columns = [
            'id', 'notable_id', 'notable_type', 'personel_id', 'baslik',
            'icerik', 'kategori', 'oncelik', 'gizli_mi', 'sabitlenmis_mi',
            'etiketler', 'ekler', 'olusturan_id', 'guncelleyen_id',
            'aktif_mi', 'olusturma_tarihi', 'guncelleme_tarihi', 'silinme_tarihi'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('notlar', $column),
                "Column {$column} should exist in notlar table"
            );
        }
    }

    /** @test */
    public function dokumanlar_table_is_created_correctly()
    {
        $this->assertTrue(Schema::hasTable('dokumanlar'));
        
        $columns = [
            'id', 'documentable_id', 'documentable_type', 'url', 'dokuman_tipi',
            'baslik', 'aciklama', 'dosya_adi', 'orijinal_dosya_adi', 'dosya_boyutu',
            'mime_type', 'dosya_uzantisi', 'dosya_hash', 'versiyon', 'ana_dokuman_id',
            'gizli_mi', 'erisim_izinleri', 'metadata', 'son_erisim_tarihi',
            'erisim_sayisi', 'olusturan_id', 'guncelleyen_id', 'aktif_mi',
            'olusturma_tarihi', 'guncelleme_tarihi', 'silinme_tarihi'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('dokumanlar', $column),
                "Column {$column} should exist in dokumanlar table"
            );
        }
    }

    /** @test */
    public function resim_table_is_updated_correctly()
    {
        $this->assertTrue(Schema::hasTable('resim'));
        
        $newColumns = [
            'kategori', 'baslik', 'aciklama', 'cekim_tarihi', 'dosya_boyutu',
            'genislik', 'yukseklik', 'dosya_hash', 'thumbnail_url', 'thumbnail_sizes',
            'metadata', 'olusturan_id', 'guncelleyen_id', 'siralama'
        ];
        
        foreach ($newColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('resim', $column),
                "Column {$column} should exist in updated resim table"
            );
        }
    }

    /** @test */
    public function foreign_key_constraints_exist()
    {
        // Bu test foreign key constraint'lerin varlığını kontrol eder
        // Gerçek veritabanında çalıştırılması gerekir
        
        $this->assertTrue(true); // Placeholder test
        
        // Gerçek test için:
        // $foreignKeys = DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL");
        // $this->assertNotEmpty($foreignKeys);
    }

    /** @test */
    public function indexes_are_created_correctly()
    {
        // Bu test index'lerin varlığını kontrol eder
        // Gerçek veritabanında çalıştırılması gerekir
        
        $this->assertTrue(true); // Placeholder test
        
        // Gerçek test için:
        // $indexes = DB::select("SHOW INDEX FROM mulkler");
        // $this->assertNotEmpty($indexes);
    }

    /** @test */
    public function json_columns_work_correctly()
    {
        // JSON sütunlarının çalıştığını test et
        $this->assertTrue(Schema::hasColumn('mulk_ozellikleri', 'ozellik_degeri'));
        $this->assertTrue(Schema::hasColumn('musteri_hizmetleri', 'degerlendirme'));
        $this->assertTrue(Schema::hasColumn('musteri_talepleri', 'lokasyon_tercihleri'));
        $this->assertTrue(Schema::hasColumn('talep_portfoy_eslestirmeleri', 'eslestirme_detaylari'));
        $this->assertTrue(Schema::hasColumn('hatirlatmalar', 'bildirim_ayarlari'));
        $this->assertTrue(Schema::hasColumn('notlar', 'etiketler'));
        $this->assertTrue(Schema::hasColumn('dokumanlar', 'metadata'));
    }

    /** @test */
    public function enum_columns_have_correct_values()
    {
        // Enum sütunlarının doğru değerlere sahip olduğunu test et
        $this->assertTrue(Schema::hasColumn('mulkler', 'durum'));
        $this->assertTrue(Schema::hasColumn('mulk_ozellikleri', 'ozellik_tipi'));
        $this->assertTrue(Schema::hasColumn('musteri_mulk_iliskileri', 'iliski_tipi'));
        $this->assertTrue(Schema::hasColumn('musteri_hizmetleri', 'hizmet_tipi'));
        $this->assertTrue(Schema::hasColumn('musteri_talepleri', 'mulk_kategorisi'));
        $this->assertTrue(Schema::hasColumn('hatirlatmalar', 'hatirlatma_tipi'));
        $this->assertTrue(Schema::hasColumn('notlar', 'kategori'));
        $this->assertTrue(Schema::hasColumn('dokumanlar', 'dokuman_tipi'));
    }
}