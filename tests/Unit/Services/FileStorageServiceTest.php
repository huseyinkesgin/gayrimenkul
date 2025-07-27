<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FileStorageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * FileStorageService Unit Testleri
 * 
 * Bu test sınıfı FileStorageService'in tüm özelliklerini test eder:
 * - Dosya kaydetme ve okuma
 * - Dosya kopyalama ve taşıma
 * - Klasör yönetimi
 * - Depolama istatistikleri
 * - Dosya sıkıştırma
 */
class FileStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    private FileStorageService $fileStorageService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fileStorageService = new FileStorageService();
        
        // Test için fake storage kullan
        Storage::fake('public');
        Storage::fake('local');
    }

    /** @test */
    public function basarili_dosya_kaydetme_testi()
    {
        $icerik = 'Bu bir test dosyasıdır.';
        $dosyaYolu = 'test/dosya.txt';
        
        $sonuc = $this->fileStorageService->dosyaKaydet($icerik, $dosyaYolu);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals($dosyaYolu, $sonuc['dosya_yolu']);
        $this->assertEquals('public', $sonuc['disk']);
        $this->assertEquals(strlen($icerik), $sonuc['boyut']);
        $this->assertArrayHasKey('url', $sonuc);
        
        // Dosya gerçekten kaydedildi mi?
        Storage::disk('public')->assertExists($dosyaYolu);
    }

    /** @test */
    public function yedekleme_ile_dosya_kaydetme_testi()
    {
        $icerik = 'Yedeklenecek dosya içeriği';
        $dosyaYolu = 'test/yedekli-dosya.txt';
        
        $sonuc = $this->fileStorageService->dosyaKaydet($icerik, $dosyaYolu, 'public', true);

        $this->assertTrue($sonuc['basarili']);
        $this->assertArrayHasKey('yedek', $sonuc);
        
        // Ana dosya kaydedildi mi?
        Storage::disk('public')->assertExists($dosyaYolu);
        
        // Yedek dosya oluşturuldu mu?
        $yedekYolu = 'yedekler/' . date('Y/m/d') . '/' . $dosyaYolu;
        Storage::disk('local')->assertExists($yedekYolu);
    }

    /** @test */
    public function dosya_okuma_testi()
    {
        $icerik = 'Okunacak dosya içeriği';
        $dosyaYolu = 'test/okunacak-dosya.txt';
        
        // Önce dosyayı kaydet
        Storage::disk('public')->put($dosyaYolu, $icerik);
        
        $sonuc = $this->fileStorageService->dosyaOku($dosyaYolu);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals($icerik, $sonuc['icerik']);
        $this->assertEquals(strlen($icerik), $sonuc['boyut']);
        $this->assertArrayHasKey('son_degisiklik', $sonuc);
        $this->assertArrayHasKey('mime_type', $sonuc);
    }

    /** @test */
    public function olmayan_dosya_okuma_testi()
    {
        $sonuc = $this->fileStorageService->dosyaOku('olmayan/dosya.txt');

        $this->assertFalse($sonuc['basarili']);
        $this->assertEquals('Dosya bulunamadı', $sonuc['hata']);
    }

    /** @test */
    public function dosya_silme_testi()
    {
        $dosyaYolu = 'test/silinecek-dosya.txt';
        
        // Önce dosyayı oluştur
        Storage::disk('public')->put($dosyaYolu, 'Silinecek içerik');
        
        $sonuc = $this->fileStorageService->dosyaSil($dosyaYolu);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals($dosyaYolu, $sonuc['dosya_yolu']);
        $this->assertEquals('public', $sonuc['disk']);
        
        // Dosya silinmiş mi?
        Storage::disk('public')->assertMissing($dosyaYolu);
    }

    /** @test */
    public function yedekten_de_dosya_silme_testi()
    {
        $dosyaYolu = 'test/yedekten-silinecek.txt';
        
        // Ana ve yedek dosyaları oluştur
        Storage::disk('public')->put($dosyaYolu, 'Ana dosya');
        Storage::disk('local')->put($dosyaYolu, 'Yedek dosya');
        
        $sonuc = $this->fileStorageService->dosyaSil($dosyaYolu, 'public', true);

        $this->assertTrue($sonuc['basarili']);
        $this->assertTrue($sonuc['yedek_silindi']);
        
        // Her iki dosya da silinmiş mi?
        Storage::disk('public')->assertMissing($dosyaYolu);
        Storage::disk('local')->assertMissing($dosyaYolu);
    }

    /** @test */
    public function dosya_yedekleme_testi()
    {
        $dosyaYolu = 'test/yedeklenecek.txt';
        $icerik = 'Yedeklenecek içerik';
        
        // Kaynak dosyayı oluştur
        Storage::disk('public')->put($dosyaYolu, $icerik);
        
        $sonuc = $this->fileStorageService->dosyaYedekle($dosyaYolu);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals($dosyaYolu, $sonuc['kaynak_yol']);
        $this->assertEquals('public', $sonuc['kaynak_disk']);
        $this->assertEquals('local', $sonuc['yedek_disk']);
        $this->assertArrayHasKey('yedek_yol', $sonuc);
        
        // Yedek dosya oluşturulmuş mu?
        $yedekYolu = $sonuc['yedek_yol'];
        Storage::disk('local')->assertExists($yedekYolu);
        $this->assertEquals($icerik, Storage::disk('local')->get($yedekYolu));
    }

    /** @test */
    public function dosya_kopyalama_testi()
    {
        $kaynakYol = 'test/kaynak.txt';
        $hedefYol = 'test/hedef.txt';
        $icerik = 'Kopyalanacak içerik';
        
        // Kaynak dosyayı oluştur
        Storage::disk('public')->put($kaynakYol, $icerik);
        
        $sonuc = $this->fileStorageService->dosyaKopyala($kaynakYol, $hedefYol);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals($kaynakYol, $sonuc['kaynak_yol']);
        $this->assertEquals($hedefYol, $sonuc['hedef_yol']);
        
        // Her iki dosya da mevcut mu?
        Storage::disk('public')->assertExists($kaynakYol);
        Storage::disk('public')->assertExists($hedefYol);
        $this->assertEquals($icerik, Storage::disk('public')->get($hedefYol));
    }

    /** @test */
    public function farkli_diskler_arasi_kopyalama_testi()
    {
        $kaynakYol = 'test/kaynak.txt';
        $hedefYol = 'test/hedef.txt';
        $icerik = 'Diskler arası kopyalama';
        
        // Kaynak dosyayı public disk'e oluştur
        Storage::disk('public')->put($kaynakYol, $icerik);
        
        $sonuc = $this->fileStorageService->dosyaKopyala(
            $kaynakYol, 
            $hedefYol, 
            'public', 
            'local'
        );

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals('public', $sonuc['kaynak_disk']);
        $this->assertEquals('local', $sonuc['hedef_disk']);
        
        // Hedef dosya local disk'te oluşturulmuş mu?
        Storage::disk('local')->assertExists($hedefYol);
        $this->assertEquals($icerik, Storage::disk('local')->get($hedefYol));
    }

    /** @test */
    public function dosya_tasima_testi()
    {
        $kaynakYol = 'test/kaynak.txt';
        $hedefYol = 'test/hedef.txt';
        $icerik = 'Taşınacak içerik';
        
        // Kaynak dosyayı oluştur
        Storage::disk('public')->put($kaynakYol, $icerik);
        
        $sonuc = $this->fileStorageService->dosyaTasi($kaynakYol, $hedefYol);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals($kaynakYol, $sonuc['kaynak_yol']);
        $this->assertEquals($hedefYol, $sonuc['hedef_yol']);
        
        // Kaynak dosya silinmiş, hedef dosya oluşturulmuş mu?
        Storage::disk('public')->assertMissing($kaynakYol);
        Storage::disk('public')->assertExists($hedefYol);
        $this->assertEquals($icerik, Storage::disk('public')->get($hedefYol));
    }

    /** @test */
    public function klasor_olusturma_testi()
    {
        $klasorYolu = 'test/yeni-klasor';
        
        $sonuc = $this->fileStorageService->klasorOlustur($klasorYolu);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals($klasorYolu, $sonuc['klasor_yolu']);
        $this->assertEquals('public', $sonuc['disk']);
        
        // Klasör oluşturulmuş mu?
        $this->assertTrue(Storage::disk('public')->exists($klasorYolu));
    }

    /** @test */
    public function klasor_silme_testi()
    {
        $klasorYolu = 'test/silinecek-klasor';
        
        // Klasörü ve içinde dosya oluştur
        Storage::disk('public')->makeDirectory($klasorYolu);
        Storage::disk('public')->put($klasorYolu . '/dosya.txt', 'İçerik');
        
        $sonuc = $this->fileStorageService->klasorSil($klasorYolu);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals($klasorYolu, $sonuc['klasor_yolu']);
        
        // Klasör ve içeriği silinmiş mi?
        Storage::disk('public')->assertMissing($klasorYolu);
        Storage::disk('public')->assertMissing($klasorYolu . '/dosya.txt');
    }

    /** @test */
    public function klasor_icerigini_listeleme_testi()
    {
        $klasorYolu = 'test/liste-klasoru';
        
        // Klasör ve içerik oluştur
        Storage::disk('public')->makeDirectory($klasorYolu);
        Storage::disk('public')->makeDirectory($klasorYolu . '/alt-klasor');
        Storage::disk('public')->put($klasorYolu . '/dosya1.txt', 'İçerik 1');
        Storage::disk('public')->put($klasorYolu . '/dosya2.txt', 'İçerik 2');
        
        $sonuc = $this->fileStorageService->klasorIcerigiListele($klasorYolu);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals($klasorYolu, $sonuc['klasor_yolu']);
        $this->assertEquals(2, $sonuc['toplam_dosya']);
        $this->assertEquals(1, $sonuc['toplam_klasor']);
        $this->assertCount(2, $sonuc['dosyalar']);
        $this->assertCount(1, $sonuc['klasorler']);
        
        // Dosya bilgileri doğru mu?
        $dosya = $sonuc['dosyalar'][0];
        $this->assertArrayHasKey('ad', $dosya);
        $this->assertArrayHasKey('yol', $dosya);
        $this->assertArrayHasKey('boyut', $dosya);
        $this->assertArrayHasKey('son_degisiklik', $dosya);
        $this->assertArrayHasKey('mime_type', $dosya);
        $this->assertArrayHasKey('url', $dosya);
    }

    /** @test */
    public function depolama_istatistikleri_testi()
    {
        // Test dosyaları oluştur
        Storage::disk('public')->put('test/dosya1.txt', 'İçerik 1');
        Storage::disk('public')->put('test/dosya2.pdf', 'PDF İçeriği');
        Storage::disk('public')->put('test/resim.jpg', 'JPEG İçeriği');
        
        $sonuc = $this->fileStorageService->depolamaIstatistikleri();

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals('public', $sonuc['disk']);
        $this->assertEquals(3, $sonuc['toplam_dosya']);
        $this->assertGreaterThan(0, $sonuc['toplam_boyut']);
        $this->assertArrayHasKey('toplam_boyut_formatli', $sonuc);
        $this->assertArrayHasKey('dosya_tipleri', $sonuc);
        $this->assertGreaterThan(0, $sonuc['ortalama_dosya_boyutu']);
        
        // Dosya tipi dağılımı doğru mu?
        $dosyaTipleri = $sonuc['dosya_tipleri'];
        $this->assertArrayHasKey('txt', $dosyaTipleri);
        $this->assertArrayHasKey('pdf', $dosyaTipleri);
        $this->assertArrayHasKey('jpg', $dosyaTipleri);
    }

    /** @test */
    public function eski_dosyalari_temizleme_testi()
    {
        // Eski ve yeni dosyalar oluştur
        Storage::disk('public')->put('test/yeni-dosya.txt', 'Yeni içerik');
        Storage::disk('public')->put('test/eski-dosya.txt', 'Eski içerik');
        
        // Eski dosyanın tarihini değiştir (35 gün önce)
        $eskiTarih = time() - (35 * 24 * 60 * 60);
        touch(Storage::disk('public')->path('test/eski-dosya.txt'), $eskiTarih);
        
        $sonuc = $this->fileStorageService->eskiDosyalariTemizle('public', 30);

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals('public', $sonuc['disk']);
        $this->assertEquals(30, $sonuc['gun_sayisi']);
        $this->assertEquals(1, $sonuc['silinen_adet']);
        $this->assertGreaterThan(0, $sonuc['silinen_boyut']);
        
        // Eski dosya silinmiş, yeni dosya korunmuş mu?
        Storage::disk('public')->assertMissing('test/eski-dosya.txt');
        Storage::disk('public')->assertExists('test/yeni-dosya.txt');
    }

    /** @test */
    public function dosya_sikistirma_testi()
    {
        // Test dosyaları oluştur
        $dosyaYollari = [
            'test/dosya1.txt',
            'test/dosya2.txt',
            'test/dosya3.txt'
        ];
        
        foreach ($dosyaYollari as $yol) {
            Storage::disk('public')->put($yol, "İçerik: {$yol}");
        }
        
        $zipAdi = 'test-arsiv.zip';
        
        $sonuc = $this->fileStorageService->dosyaSikistir($dosyaYollari, $zipAdi);

        $this->assertTrue($sonuc['basarili']);
        $this->assertStringContains($zipAdi, $sonuc['zip_yolu']);
        $this->assertGreaterThan(0, $sonuc['zip_boyutu']);
        $this->assertEquals(3, $sonuc['eklenen_adet']);
        $this->assertCount(3, $sonuc['eklenen_dosyalar']);
        $this->assertArrayHasKey('url', $sonuc);
        
        // ZIP dosyası oluşturulmuş mu?
        Storage::disk('public')->assertExists($sonuc['zip_yolu']);
    }

    /** @test */
    public function benzersiz_dosya_adi_olusturma_testi()
    {
        $orijinalAd = 'test-dosya.txt';
        $klasorYolu = 'test';
        
        // Aynı isimde dosya oluştur
        Storage::disk('public')->put($klasorYolu . '/' . $orijinalAd, 'Mevcut dosya');
        
        $benzersizAd = $this->fileStorageService->benzersizDosyaAdiOlustur(
            $orijinalAd, 
            $klasorYolu
        );

        $this->assertNotEquals($orijinalAd, $benzersizAd);
        $this->assertStringContains('test-dosya_1.txt', $benzersizAd);
        
        // Benzersiz ad ile dosya mevcut değil mi?
        Storage::disk('public')->assertMissing($klasorYolu . '/' . $benzersizAd);
    }

    /** @test */
    public function disk_durumu_kontrolu_testi()
    {
        $sonuc = $this->fileStorageService->diskDurumuKontrol();

        $this->assertTrue($sonuc['basarili']);
        $this->assertEquals('public', $sonuc['disk']);
        $this->assertTrue($sonuc['erisim']);
        $this->assertEquals('Çalışıyor', $sonuc['durum']);
        $this->assertArrayHasKey('test_tarihi', $sonuc);
        
        // Test dosyası temizlenmiş mi?
        Storage::disk('public')->assertMissing('test_file.txt');
    }

    /** @test */
    public function boyut_formatlama_testi()
    {
        // Private metoda erişim için reflection kullan
        $reflection = new \ReflectionClass($this->fileStorageService);
        $method = $reflection->getMethod('boyutFormatla');
        $method->setAccessible(true);

        $this->assertEquals('1.00 KB', $method->invoke($this->fileStorageService, 1024));
        $this->assertEquals('1.00 MB', $method->invoke($this->fileStorageService, 1024 * 1024));
        $this->assertEquals('1.00 GB', $method->invoke($this->fileStorageService, 1024 * 1024 * 1024));
        $this->assertEquals('0 B', $method->invoke($this->fileStorageService, 0));
    }

    /** @test */
    public function hata_durumlarinda_graceful_handling_testi()
    {
        // Olmayan dosyayı kopyalama
        $sonuc = $this->fileStorageService->dosyaKopyala('olmayan.txt', 'hedef.txt');
        $this->assertFalse($sonuc['basarili']);
        $this->assertArrayHasKey('hata', $sonuc);
        
        // Olmayan klasörü silme
        $sonuc = $this->fileStorageService->klasorSil('olmayan-klasor');
        $this->assertFalse($sonuc['basarili']);
        $this->assertEquals('Klasör bulunamadı', $sonuc['hata']);
        
        // Olmayan dosyayı yedekleme
        $sonuc = $this->fileStorageService->dosyaYedekle('olmayan.txt');
        $this->assertFalse($sonuc['basarili']);
        $this->assertEquals('Kaynak dosya bulunamadı', $sonuc['hata']);
    }

    protected function tearDown(): void
    {
        // Test sonrası temizlik
        Storage::fake('public');
        Storage::fake('local');
        parent::tearDown();
    }
}