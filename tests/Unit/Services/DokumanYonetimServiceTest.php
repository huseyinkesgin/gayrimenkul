<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DokumanYonetimService;
use App\Services\DokumanUploadService;
use App\Models\Dokuman;
use App\Models\User;
use App\Enums\DokumanTipi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DokumanYonetimServiceTest extends TestCase
{
    use RefreshDatabase;

    private DokumanYonetimService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        $this->user = User::factory()->create();
        Auth::login($this->user);
        
        $this->service = app(DokumanYonetimService::class);
    }

    /** @test */
    public function mulk_tipine_gore_dokuman_tiplerini_getirir()
    {
        // Arsa için döküman tipleri
        $arsaTipleri = $this->service->getMulkTipineGoreDokumanTipleri('arsa');
        
        $this->assertIsArray($arsaTipleri);
        $this->assertNotEmpty($arsaTipleri);
        
        // Arsa için TAPU olmalı, AUTOCAD olmamalı
        $tipValues = array_column($arsaTipleri, 'value');
        $this->assertContains(DokumanTipi::TAPU->value, $tipValues);
        $this->assertNotContains(DokumanTipi::AUTOCAD->value, $tipValues);

        // İşyeri için döküman tipleri
        $isyeriTipleri = $this->service->getMulkTipineGoreDokumanTipleri('isyeri');
        $isyeriTipValues = array_column($isyeriTipleri, 'value');
        
        // İşyeri için hem TAPU hem AUTOCAD olmalı
        $this->assertContains(DokumanTipi::TAPU->value, $isyeriTipValues);
        $this->assertContains(DokumanTipi::AUTOCAD->value, $isyeriTipValues);
    }

    /** @test */
    public function dokuman_yukler_ve_kaydeder()
    {
        $file = UploadedFile::fake()->create('tapu.pdf', 1024, 'application/pdf');
        
        $result = $this->service->dokumanYukle(
            $file,
            User::class,
            $this->user->id,
            DokumanTipi::TAPU,
            [
                'baslik' => 'Test Tapu Belgesi',
                'aciklama' => 'Test açıklaması'
            ]
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('dokuman', $result);
        $this->assertInstanceOf(Dokuman::class, $result['dokuman']);
        
        // Veritabanında kayıt kontrolü
        $this->assertDatabaseHas('dokumanlar', [
            'baslik' => 'Test Tapu Belgesi',
            'dokuman_tipi' => DokumanTipi::TAPU->value,
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'olusturan_id' => $this->user->id,
            'aktif_mi' => true
        ]);
    }

    /** @test */
    public function dokuman_versiyonu_gunceller()
    {
        // İlk dökümanı oluştur
        $eskiDokuman = Dokuman::factory()->create([
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'dokuman_tipi' => DokumanTipi::TAPU,
            'versiyon' => 1
        ]);

        $yeniDosya = UploadedFile::fake()->create('yeni-tapu.pdf', 2048, 'application/pdf');
        
        $result = $this->service->dokumanVersiyonuGuncelle(
            $eskiDokuman,
            $yeniDosya,
            ['baslik' => 'Güncellenmiş Tapu Belgesi']
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('dokuman', $result);
        $this->assertArrayHasKey('eski_versiyon', $result);
        
        // Yeni versiyon kontrolü
        $yeniVersiyon = $result['dokuman'];
        $this->assertEquals(2, $yeniVersiyon->versiyon);
        $this->assertEquals($eskiDokuman->id, $yeniVersiyon->ana_dokuman_id);
        
        // Eski versiyon arşivlendi mi?
        $eskiDokuman->refresh();
        $this->assertFalse($eskiDokuman->aktif_mi);
    }

    /** @test */
    public function dokumanlari_mulk_tipine_gore_filtreler()
    {
        // Farklı tipte dökümanlar oluştur
        Dokuman::factory()->create([
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'dokuman_tipi' => DokumanTipi::TAPU,
            'aktif_mi' => true
        ]);

        Dokuman::factory()->create([
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'dokuman_tipi' => DokumanTipi::AUTOCAD,
            'aktif_mi' => true
        ]);

        // Arsa tipi için filtrele (AUTOCAD olmamalı)
        $arsaDokumanlari = $this->service->dokumanlariFiltrelemeMulkTipineGore(
            User::class,
            $this->user->id,
            'arsa'
        );

        $this->assertArrayHasKey(DokumanTipi::TAPU->value, $arsaDokumanlari->toArray());
        $this->assertArrayNotHasKey(DokumanTipi::AUTOCAD->value, $arsaDokumanlari->toArray());

        // İşyeri tipi için filtrele (her ikisi de olmalı)
        $isyeriDokumanlari = $this->service->dokumanlariFiltrelemeMulkTipineGore(
            User::class,
            $this->user->id,
            'isyeri'
        );

        $this->assertArrayHasKey(DokumanTipi::TAPU->value, $isyeriDokumanlari->toArray());
        $this->assertArrayHasKey(DokumanTipi::AUTOCAD->value, $isyeriDokumanlari->toArray());
    }

    /** @test */
    public function dokuman_soft_delete_yapar()
    {
        $dokuman = Dokuman::factory()->create([
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'aktif_mi' => true
        ]);

        $result = $this->service->dokumanSil($dokuman, 'Test silme nedeni');

        $this->assertTrue($result['success']);
        
        // Soft delete kontrolü
        $dokuman->refresh();
        $this->assertFalse($dokuman->aktif_mi);
        $this->assertNotNull($dokuman->deleted_at);
        $this->assertEquals($this->user->id, $dokuman->guncelleyen_id);
        
        // Metadata'da silme bilgileri
        $this->assertArrayHasKey('silme_tarihi', $dokuman->metadata);
        $this->assertArrayHasKey('silen_kullanici', $dokuman->metadata);
        $this->assertEquals('Test silme nedeni', $dokuman->metadata['silme_nedeni']);
    }

    /** @test */
    public function dokuman_geri_yukler()
    {
        $dokuman = Dokuman::factory()->create([
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'aktif_mi' => false
        ]);
        $dokuman->delete(); // Soft delete

        $result = $this->service->dokumanGeriYukle($dokuman);

        $this->assertTrue($result['success']);
        
        // Geri yükleme kontrolü
        $dokuman->refresh();
        $this->assertTrue($dokuman->aktif_mi);
        $this->assertNull($dokuman->deleted_at);
        $this->assertEquals($this->user->id, $dokuman->guncelleyen_id);
        
        // Metadata'da geri yükleme bilgileri
        $this->assertArrayHasKey('geri_yukleme_tarihi', $dokuman->metadata);
        $this->assertArrayHasKey('geri_yukleyen_kullanici', $dokuman->metadata);
    }

    /** @test */
    public function dokuman_istatistiklerini_getirir()
    {
        // Test dökümanları oluştur
        Dokuman::factory()->count(3)->create([
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'dokuman_tipi' => DokumanTipi::TAPU,
            'aktif_mi' => true,
            'dosya_boyutu' => 1024
        ]);

        Dokuman::factory()->count(2)->create([
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'dokuman_tipi' => DokumanTipi::AUTOCAD,
            'aktif_mi' => true,
            'dosya_boyutu' => 2048
        ]);

        Dokuman::factory()->create([
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'aktif_mi' => false // Arşivlenmiş
        ]);

        $istatistikler = $this->service->getDokumanIstatistikleri(
            User::class,
            $this->user->id
        );

        $this->assertEquals(5, $istatistikler['toplam_dokuman']);
        $this->assertEquals(1, $istatistikler['arsivlenen_dokuman']);
        $this->assertEquals(7168, $istatistikler['toplam_boyut']); // (3*1024) + (2*2048)
        
        $this->assertArrayHasKey('tip_bazinda_dagilim', $istatistikler);
        $this->assertArrayHasKey('son_yuklenenler', $istatistikler);
        
        // Tip bazında dağılım kontrolü
        $dagilim = $istatistikler['tip_bazinda_dagilim'];
        $this->assertEquals(3, $dagilim[DokumanTipi::TAPU->value]['adet']);
        $this->assertEquals(2, $dagilim[DokumanTipi::AUTOCAD->value]['adet']);
    }

    /** @test */
    public function eksik_zorunlu_dokumanlari_tespit_eder()
    {
        // Mock bir mülk tipi için test (arsa - sadece TAPU zorunlu)
        $eksikler = $this->service->getEksikZorunluDokumanlar(
            User::class,
            $this->user->id,
            'arsa'
        );

        // TAPU zorunlu olduğu için eksik listesinde olmalı
        $eksikTipler = array_column($eksikler, 'tip');
        $this->assertContains(DokumanTipi::TAPU->value, $eksikTipler);

        // Şimdi TAPU ekleyelim
        Dokuman::factory()->create([
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'dokuman_tipi' => DokumanTipi::TAPU,
            'aktif_mi' => true
        ]);

        $eksiklerSonra = $this->service->getEksikZorunluDokumanlar(
            User::class,
            $this->user->id,
            'arsa'
        );

        // Artık eksik olmamalı
        $this->assertEmpty($eksiklerSonra);
    }

    /** @test */
    public function dokuman_arama_yapar()
    {
        Dokuman::factory()->create([
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'baslik' => 'Tapu Senedi Belgesi',
            'aciklama' => 'İstanbul Kadıköy tapu belgesi',
            'aktif_mi' => true
        ]);

        Dokuman::factory()->create([
            'documentable_type' => User::class,
            'documentable_id' => $this->user->id,
            'baslik' => 'AutoCAD Çizimi',
            'aciklama' => 'Teknik proje çizimi',
            'aktif_mi' => true
        ]);

        // "tapu" araması
        $sonuclar = $this->service->dokumanAra('tapu');
        $this->assertEquals(1, $sonuclar->count());
        $this->assertStringContainsString('Tapu', $sonuclar->first()->baslik);

        // "çizim" araması
        $sonuclar2 = $this->service->dokumanAra('çizim');
        $this->assertEquals(1, $sonuclar2->count());
        $this->assertStringContainsString('Çizimi', $sonuclar2->first()->baslik);
    }

    /** @test */
    public function toplu_dokuman_yukler()
    {
        $files = [
            UploadedFile::fake()->create('tapu1.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('tapu2.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('tapu3.pdf', 1024, 'application/pdf'),
        ];

        $result = $this->service->topluDokumanYukle(
            $files,
            User::class,
            $this->user->id,
            DokumanTipi::TAPU
        );

        $this->assertArrayHasKey('summary', $result);
        $this->assertEquals(3, $result['summary']['total']);
        $this->assertEquals(3, $result['summary']['success']);
        $this->assertEquals(0, $result['summary']['error']);

        // Veritabanında 3 döküman olmalı
        $this->assertEquals(3, Dokuman::where('documentable_type', User::class)
                                    ->where('documentable_id', $this->user->id)
                                    ->where('dokuman_tipi', DokumanTipi::TAPU)
                                    ->count());
    }
}