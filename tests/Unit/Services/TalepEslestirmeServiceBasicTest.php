<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\TalepEslestirmeService;
use App\Services\EslestirmeBildirimService;
use Mockery;

class TalepEslestirmeServiceBasicTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_service_olusturulabilir()
    {
        // Arrange
        $bildirimServiceMock = Mockery::mock(EslestirmeBildirimService::class);
        
        // Act
        $service = new TalepEslestirmeService($bildirimServiceMock);
        
        // Assert
        $this->assertInstanceOf(TalepEslestirmeService::class, $service);
    }

    public function test_minimum_eslestirme_skoru_sabiti_dogru()
    {
        // Assert
        $this->assertEquals(0.3, TalepEslestirmeService::MIN_ESLESTIRME_SKORU);
    }

    public function test_maksimum_eslestirme_sayisi_sabiti_dogru()
    {
        // Assert
        $this->assertEquals(20, TalepEslestirmeService::MAX_ESLESTIRME_SAYISI);
    }

    public function test_agirliklar_sabiti_dogru()
    {
        // Assert
        $expectedAgirliklar = [
            'fiyat' => 0.30,
            'metrekare' => 0.25,
            'lokasyon' => 0.20,
            'ozellikler' => 0.15,
            'kategori' => 0.10,
        ];
        
        $this->assertEquals($expectedAgirliklar, TalepEslestirmeService::AGIRLIKLAR);
        
        // Ağırlıkların toplamı 1.0 olmalı
        $toplam = array_sum(TalepEslestirmeService::AGIRLIKLAR);
        $this->assertEquals(1.0, $toplam);
    }

    public function test_eslestirme_istatistikleri_method_exists()
    {
        // Arrange
        $bildirimServiceMock = Mockery::mock(EslestirmeBildirimService::class);
        $service = new TalepEslestirmeService($bildirimServiceMock);
        
        // Assert
        $this->assertTrue(method_exists($service, 'eslestirmeIstatistikleri'));
    }

    public function test_talep_icin_eslestirme_yap_method_exists()
    {
        // Arrange
        $bildirimServiceMock = Mockery::mock(EslestirmeBildirimService::class);
        $service = new TalepEslestirmeService($bildirimServiceMock);
        
        // Assert
        $this->assertTrue(method_exists($service, 'talepIcinEslestirmeYap'));
    }

    public function test_otomatik_eslestirme_kontrolu_method_exists()
    {
        // Arrange
        $bildirimServiceMock = Mockery::mock(EslestirmeBildirimService::class);
        $service = new TalepEslestirmeService($bildirimServiceMock);
        
        // Assert
        $this->assertTrue(method_exists($service, 'otomatikEslestirmeKontrolu'));
    }
}