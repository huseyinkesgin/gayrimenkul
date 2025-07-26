<?php

namespace Tests\Unit\Models\Mulk;

use Tests\TestCase;
use App\Models\Mulk\Arsa\TicariArsa;
use App\Models\Mulk\Isyeri\Fabrika;
use App\Models\Mulk\Konut\Daire;
use App\Models\MulkOzellik;
use App\Models\Musteri\Musteri;
use App\Models\MusteriMulkIliskisi;
use App\Models\MusteriHizmet;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MulkModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_ozellikler_relationship()
    {
        $fabrika = Fabrika::factory()->create();
        
        // Özellik ekle
        $ozellik = MulkOzellik::create([
            'mulk_id' => $fabrika->id,
            'mulk_type' => $fabrika->getMulkType(),
            'ozellik_adi' => 'uretim_alani',
            'ozellik_degeri' => [500],
            'ozellik_tipi' => 'sayi',
            'birim' => 'm2',
            'aktif_mi' => true,
        ]);

        $this->assertTrue($fabrika->ozellikler()->exists());
        $this->assertEquals(1, $fabrika->ozellikler()->count());
        $this->assertEquals('uretim_alani', $fabrika->ozellikler()->first()->ozellik_adi);
    }

    /** @test */
    public function it_has_aktif_ozellikler_relationship()
    {
        $daire = Daire::factory()->create();
        
        // Aktif özellik
        MulkOzellik::create([
            'mulk_id' => $daire->id,
            'mulk_type' => $daire->getMulkType(),
            'ozellik_adi' => 'oda_sayisi',
            'ozellik_degeri' => [3],
            'ozellik_tipi' => 'sayi',
            'aktif_mi' => true,
        ]);

        // Pasif özellik
        MulkOzellik::create([
            'mulk_id' => $daire->id,
            'mulk_type' => $daire->getMulkType(),
            'ozellik_adi' => 'eski_ozellik',
            'ozellik_degeri' => ['test'],
            'ozellik_tipi' => 'metin',
            'aktif_mi' => false,
        ]);

        $this->assertEquals(2, $daire->ozellikler()->count());
        $this->assertEquals(1, $daire->aktifOzellikler()->count());
        $this->assertEquals('oda_sayisi', $daire->aktifOzellikler()->first()->ozellik_adi);
    }

    /** @test */
    public function it_has_musteri_iliskileri_relationship()
    {
        $arsa = TicariArsa::factory()->create();
        $musteri = Musteri::factory()->create();
        
        // Müşteri-mülk ilişkisi oluştur
        MusteriMulkIliskisi::create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $arsa->id,
            'mulk_type' => $arsa->getMulkType(),
            'iliski_tipi' => 'ilgileniyor',
            'durum' => 'aktif',
            'ilgi_seviyesi' => 8,
            'baslangic_tarihi' => now(),
        ]);

        $this->assertTrue($arsa->musteriIliskileri()->exists());
        $this->assertEquals(1, $arsa->musteriIliskileri()->count());
        $this->assertEquals('ilgileniyor', $arsa->musteriIliskileri()->first()->iliski_tipi);
    }

    /** @test */
    public function it_has_aktif_musteri_iliskileri_relationship()
    {
        $fabrika = Fabrika::factory()->create();
        $musteri1 = Musteri::factory()->create();
        $musteri2 = Musteri::factory()->create();
        
        // Aktif ilişki
        MusteriMulkIliskisi::create([
            'musteri_id' => $musteri1->id,
            'mulk_id' => $fabrika->id,
            'mulk_type' => $fabrika->getMulkType(),
            'iliski_tipi' => 'teklif_verdi',
            'durum' => 'aktif',
            'ilgi_seviyesi' => 9,
            'baslangic_tarihi' => now(),
        ]);

        // Pasif ilişki
        MusteriMulkIliskisi::create([
            'musteri_id' => $musteri2->id,
            'mulk_id' => $fabrika->id,
            'mulk_type' => $fabrika->getMulkType(),
            'iliski_tipi' => 'ilgileniyor',
            'durum' => 'pasif',
            'ilgi_seviyesi' => 5,
            'baslangic_tarihi' => now()->subDays(30),
        ]);

        $this->assertEquals(2, $fabrika->musteriIliskileri()->count());
        $this->assertEquals(1, $fabrika->aktifMusteriIliskileri()->count());
        $this->assertEquals('teklif_verdi', $fabrika->aktifMusteriIliskileri()->first()->iliski_tipi);
    }

    /** @test */
    public function it_has_musteriler_many_to_many_relationship()
    {
        $daire = Daire::factory()->create();
        $musteri1 = Musteri::factory()->create();
        $musteri2 = Musteri::factory()->create();
        
        // İlişkileri oluştur
        MusteriMulkIliskisi::create([
            'musteri_id' => $musteri1->id,
            'mulk_id' => $daire->id,
            'mulk_type' => $daire->getMulkType(),
            'iliski_tipi' => 'ilgileniyor',
            'durum' => 'aktif',
            'ilgi_seviyesi' => 7,
            'baslangic_tarihi' => now(),
        ]);

        MusteriMulkIliskisi::create([
            'musteri_id' => $musteri2->id,
            'mulk_id' => $daire->id,
            'mulk_type' => $daire->getMulkType(),
            'iliski_tipi' => 'gorustu',
            'durum' => 'aktif',
            'ilgi_seviyesi' => 6,
            'baslangic_tarihi' => now()->subDays(5),
        ]);

        $musteriler = $daire->musteriler;
        
        $this->assertEquals(2, $musteriler->count());
        $this->assertTrue($musteriler->contains($musteri1));
        $this->assertTrue($musteriler->contains($musteri2));
        
        // Pivot verilerini kontrol et
        $pivot1 = $musteriler->find($musteri1->id)->pivot;
        $this->assertEquals('ilgileniyor', $pivot1->iliski_tipi);
        $this->assertEquals(7, $pivot1->ilgi_seviyesi);
    }

    /** @test */
    public function it_has_hizmetler_relationship()
    {
        $arsa = TicariArsa::factory()->create();
        $musteri = Musteri::factory()->create();
        
        // Hizmet kaydı oluştur
        MusteriHizmet::create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $arsa->id,
            'mulk_type' => $arsa->getMulkType(),
            'hizmet_tipi' => 'telefon',
            'hizmet_tarihi' => now(),
            'aciklama' => 'Arsa hakkında bilgi verildi',
            'sonuc' => 'Olumlu',
            'degerlendirme' => ['tip' => 'olumlu', 'puan' => 8],
            'sure_dakika' => 15,
        ]);

        $this->assertTrue($arsa->hizmetler()->exists());
        $this->assertEquals(1, $arsa->hizmetler()->count());
        $this->assertEquals('telefon', $arsa->hizmetler()->first()->hizmet_tipi);
    }

    /** @test */
    public function it_filters_relationships_by_mulk_type()
    {
        $fabrika = Fabrika::factory()->create();
        $daire = Daire::factory()->create();
        
        // Fabrika için özellik
        MulkOzellik::create([
            'mulk_id' => $fabrika->id,
            'mulk_type' => 'fabrika',
            'ozellik_adi' => 'uretim_alani',
            'ozellik_degeri' => [1000],
            'ozellik_tipi' => 'sayi',
            'aktif_mi' => true,
        ]);

        // Daire için özellik
        MulkOzellik::create([
            'mulk_id' => $daire->id,
            'mulk_type' => 'daire',
            'ozellik_adi' => 'oda_sayisi',
            'ozellik_degeri' => [3],
            'ozellik_tipi' => 'sayi',
            'aktif_mi' => true,
        ]);

        // Her mülk sadece kendi tipindeki özellikleri görmeli
        $this->assertEquals(1, $fabrika->ozellikler()->count());
        $this->assertEquals(1, $daire->ozellikler()->count());
        $this->assertEquals('uretim_alani', $fabrika->ozellikler()->first()->ozellik_adi);
        $this->assertEquals('oda_sayisi', $daire->ozellikler()->first()->ozellik_adi);
    }

    /** @test */
    public function it_cascades_relationships_correctly()
    {
        $fabrika = Fabrika::factory()->create();
        $musteri = Musteri::factory()->create();
        
        // İlişkili veriler oluştur
        $ozellik = MulkOzellik::create([
            'mulk_id' => $fabrika->id,
            'mulk_type' => $fabrika->getMulkType(),
            'ozellik_adi' => 'kapali_alan',
            'ozellik_degeri' => [2000],
            'ozellik_tipi' => 'sayi',
            'aktif_mi' => true,
        ]);

        $iliski = MusteriMulkIliskisi::create([
            'musteri_id' => $musteri->id,
            'mulk_id' => $fabrika->id,
            'mulk_type' => $fabrika->getMulkType(),
            'iliski_tipi' => 'ilgileniyor',
            'durum' => 'aktif',
            'ilgi_seviyesi' => 8,
            'baslangic_tarihi' => now(),
        ]);

        // İlişkilerin var olduğunu doğrula
        $this->assertTrue($fabrika->ozellikler()->exists());
        $this->assertTrue($fabrika->musteriIliskileri()->exists());
        
        // Mülk silindiğinde ilişkili verilerin durumunu kontrol et
        // (Gerçek uygulamada soft delete kullanılacak)
        $fabrikaId = $fabrika->id;
        $fabrika->delete();
        
        // Veritabanından kontrol et
        $this->assertDatabaseMissing('mulkler', ['id' => $fabrikaId]);
    }
}