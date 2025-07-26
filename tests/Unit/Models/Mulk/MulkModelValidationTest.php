<?php

namespace Tests\Unit\Models\Mulk;

use Tests\TestCase;
use App\Models\Mulk\Arsa\TicariArsa;
use App\Models\Mulk\Arsa\SanayiArsasi;
use App\Models\Mulk\Isyeri\Fabrika;
use App\Models\Mulk\Isyeri\Depo;
use App\Models\Mulk\Isyeri\Ofis;
use App\Models\Mulk\Konut\Daire;
use App\Models\Mulk\Konut\Villa;
use App\Models\Mulk\TuristikTesis\Hotel;
use App\Models\Mulk\TuristikTesis\ButikOtel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class MulkModelValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_validates_base_mulk_fields()
    {
        $rules = TicariArsa::getBaseValidationRules();
        
        // Geçerli veri
        $validData = [
            'baslik' => 'Test Ticari Arsa',
            'aciklama' => 'Test açıklama',
            'fiyat' => 1500000,
            'para_birimi' => 'TRY',
            'metrekare' => 800,
            'durum' => 'aktif',
            'yayinlanma_tarihi' => now()->format('Y-m-d'),
            'aktif_mi' => true,
            'siralama' => 10,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());

        // Geçersiz veri - zorunlu alan eksik
        $invalidData = $validData;
        unset($invalidData['baslik']);
        
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('baslik', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_price_field()
    {
        $rules = TicariArsa::getBaseValidationRules();
        
        $testCases = [
            ['fiyat' => 0, 'should_pass' => true],
            ['fiyat' => 1000000, 'should_pass' => true],
            ['fiyat' => -100, 'should_pass' => false],
            ['fiyat' => 'invalid', 'should_pass' => false],
            ['fiyat' => 999999999999.99, 'should_pass' => true],
            ['fiyat' => 9999999999999.99, 'should_pass' => false],
        ];

        foreach ($testCases as $testCase) {
            $data = [
                'baslik' => 'Test',
                'durum' => 'aktif',
                'fiyat' => $testCase['fiyat'],
            ];

            $validator = Validator::make($data, $rules);
            
            if ($testCase['should_pass']) {
                $this->assertTrue($validator->passes(), "Fiyat {$testCase['fiyat']} geçerli olmalı");
            } else {
                $this->assertTrue($validator->fails(), "Fiyat {$testCase['fiyat']} geçersiz olmalı");
            }
        }
    }

    /** @test */
    public function it_validates_currency_field()
    {
        $rules = TicariArsa::getBaseValidationRules();
        
        $validCurrencies = ['TRY', 'USD', 'EUR'];
        $invalidCurrencies = ['GBP', 'JPY', 'TR', 'US', 'TRYY'];

        foreach ($validCurrencies as $currency) {
            $data = [
                'baslik' => 'Test',
                'durum' => 'aktif',
                'para_birimi' => $currency,
            ];

            $validator = Validator::make($data, $rules);
            $this->assertTrue($validator->passes(), "Para birimi {$currency} geçerli olmalı");
        }

        foreach ($invalidCurrencies as $currency) {
            $data = [
                'baslik' => 'Test',
                'durum' => 'aktif',
                'para_birimi' => $currency,
            ];

            $validator = Validator::make($data, $rules);
            $this->assertTrue($validator->fails(), "Para birimi {$currency} geçersiz olmalı");
        }
    }

    /** @test */
    public function it_validates_status_field()
    {
        $rules = TicariArsa::getBaseValidationRules();
        
        $validStatuses = ['aktif', 'pasif', 'satildi', 'kiralandi'];
        $invalidStatuses = ['draft', 'pending', 'sold', 'rented'];

        foreach ($validStatuses as $status) {
            $data = [
                'baslik' => 'Test',
                'durum' => $status,
            ];

            $validator = Validator::make($data, $rules);
            $this->assertTrue($validator->passes(), "Durum {$status} geçerli olmalı");
        }

        foreach ($invalidStatuses as $status) {
            $data = [
                'baslik' => 'Test',
                'durum' => $status,
            ];

            $validator = Validator::make($data, $rules);
            $this->assertTrue($validator->fails(), "Durum {$status} geçersiz olmalı");
        }
    }

    /** @test */
    public function it_validates_fabrika_specific_fields()
    {
        $fabrika = new Fabrika();
        $rules = $fabrika->getValidationRules();
        
        $validData = [
            'baslik' => 'Test Fabrika',
            'durum' => 'aktif',
            'uretim_alani' => 1000,
            'vinc_kapasitesi' => 5,
            'vinc_sayisi' => 2,
            'konveyor_sistemi' => true,
            'atiksu_aritma_sistemi' => false,
            'patlama_riski_sinifi' => 'düşük',
            'yemekhane_kapasitesi' => 50,
            'acil_cikis_sayisi' => 4,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());

        // Geçersiz patlama riski sınıfı
        $invalidData = $validData;
        $invalidData['patlama_riski_sinifi'] = 'çok_yüksek';
        
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('patlama_riski_sinifi', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_depo_specific_fields()
    {
        $depo = new Depo();
        $rules = $depo->getValidationRules();
        
        $validData = [
            'baslik' => 'Test Depo',
            'durum' => 'aktif',
            'ellecleme_alani' => 200,
            'rampa_sayisi' => 3,
            'raf_sistemi_var_mi' => true,
            'soguk_hava_sistemi' => false,
            'forklift_gecis_genisligi' => 3.5,
            'tavan_yuksekligi' => 8,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());

        // Negatif rampa sayısı
        $invalidData = $validData;
        $invalidData['rampa_sayisi'] = -1;
        
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_validates_daire_specific_fields()
    {
        $daire = new Daire();
        $rules = $daire->getValidationRules();
        
        $validData = [
            'baslik' => 'Test Daire',
            'durum' => 'aktif',
            'oda_sayisi' => 3,
            'salon_sayisi' => 1,
            'banyo_sayisi' => 2,
            'balkon_sayisi' => 1,
            'asansor_var_mi' => true,
            'kat_numarasi' => 5,
            'bina_kat_sayisi' => 8,
            'aidat_miktari' => 350,
            'aidat_dahil_mi' => false,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());

        // Geçersiz oda sayısı
        $invalidData = $validData;
        $invalidData['oda_sayisi'] = 0;
        
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_validates_villa_specific_fields()
    {
        $villa = new Villa();
        $rules = $villa->getValidationRules();
        
        $validData = [
            'baslik' => 'Test Villa',
            'durum' => 'aktif',
            'oda_sayisi' => 5,
            'salon_sayisi' => 2,
            'banyo_sayisi' => 3,
            'bahce_alani' => 500,
            'havuz_alani' => 50,
            'garaj_kapasitesi' => 2,
            'kat_sayisi' => 2,
            'cati_tipi' => 'kiremit',
            'havuz_var_mi' => true,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function it_validates_hotel_specific_fields()
    {
        $hotel = new Hotel();
        $rules = $hotel->getValidationRules();
        
        $validData = [
            'baslik' => 'Test Hotel',
            'durum' => 'aktif',
            'oda_sayisi' => 100,
            'yatak_kapasitesi' => 200,
            'yildiz_sayisi' => 4,
            'resepsiyon_var_mi' => true,
            'restoran_var_mi' => true,
            'havuz_var_mi' => true,
            'spa_var_mi' => false,
            'konferans_salonu_var_mi' => true,
            'otopark_kapasitesi' => 50,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());

        // Geçersiz yıldız sayısı
        $invalidData = $validData;
        $invalidData['yildiz_sayisi'] = 6;
        
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_validates_butik_otel_specific_fields()
    {
        $butikOtel = new ButikOtel();
        $rules = $butikOtel->getValidationRules();
        
        $validData = [
            'baslik' => 'Test Butik Otel',
            'durum' => 'aktif',
            'oda_sayisi' => 20,
            'yatak_kapasitesi' => 40,
            'tema_konsepti' => 'modern',
            'tasarim_stili' => 'minimalist',
            'kişiselleştirilmiş_hizmet' => true,
            'butik_ozellik_sayisi' => 5,
            'sanat_eseri_var_mi' => true,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function it_merges_base_and_specific_validation_rules()
    {
        $fabrika = new Fabrika();
        $allRules = $fabrika->getValidationRules();
        $baseRules = Fabrika::getBaseValidationRules();
        $specificRules = $fabrika->getSpecificValidationRules();
        
        // Base rules should be included
        foreach ($baseRules as $field => $rule) {
            $this->assertArrayHasKey($field, $allRules);
        }
        
        // Specific rules should be included
        foreach ($specificRules as $field => $rule) {
            $this->assertArrayHasKey($field, $allRules);
        }
        
        // Should have both base and specific rules
        $this->assertArrayHasKey('baslik', $allRules); // Base rule
        $this->assertArrayHasKey('uretim_alani', $allRules); // Specific rule
    }

    /** @test */
    public function it_validates_numeric_fields_correctly()
    {
        $fabrika = new Fabrika();
        $rules = $fabrika->getValidationRules();
        
        $numericFields = [
            'fiyat', 'metrekare', 'uretim_alani', 'vinc_kapasitesi',
            'yemekhane_kapasitesi', 'acil_cikis_sayisi'
        ];
        
        foreach ($numericFields as $field) {
            // Valid numeric value
            $validData = [
                'baslik' => 'Test',
                'durum' => 'aktif',
                $field => 100,
            ];
            
            $validator = Validator::make($validData, $rules);
            $this->assertTrue($validator->passes(), "Field {$field} should accept numeric values");
            
            // Invalid string value
            $invalidData = [
                'baslik' => 'Test',
                'durum' => 'aktif',
                $field => 'invalid_number',
            ];
            
            $validator = Validator::make($invalidData, $rules);
            if (isset($rules[$field])) {
                $this->assertTrue($validator->fails(), "Field {$field} should reject non-numeric values");
            }
        }
    }
}