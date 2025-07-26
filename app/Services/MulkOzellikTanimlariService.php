<?php

namespace App\Services;

use App\Enums\MulkKategorisi;

class MulkOzellikTanimlariService
{
    /**
     * Mülk tipine göre özellik tanımlarını döndür
     */
    public static function getOzellikTanimlari(string $mulkType): array
    {
        return match ($mulkType) {
            // Arsa kategorisi
            'ticari_arsa' => self::getTicariArsaOzellikleri(),
            'sanayi_arsasi' => self::getSanayiArsasiOzellikleri(),
            'konut_arsasi' => self::getKonutArsasiOzellikleri(),
            
            // İşyeri kategorisi
            'fabrika' => self::getFabrikaOzellikleri(),
            'depo' => self::getDepoOzellikleri(),
            'ofis' => self::getOfisOzellikleri(),
            'magaza' => self::getMagazaOzellikleri(),
            'dukkan' => self::getDukkanOzellikleri(),
            
            // Konut kategorisi
            'daire' => self::getDaireOzellikleri(),
            'villa' => self::getVillaOzellikleri(),
            'rezidans' => self::getRezidansOzellikleri(),
            'yali' => self::getYaliOzellikleri(),
            'yazlik' => self::getYazlikOzellikleri(),
            
            // Turistik tesis kategorisi
            'butik_otel' => self::getButikOtelOzellikleri(),
            'apart_otel' => self::getApartOtelOzellikleri(),
            'hotel' => self::getHotelOzellikleri(),
            'motel' => self::getMotelOzellikleri(),
            'tatil_koyu' => self::getTatilKoyuOzellikleri(),
            
            default => []
        };
    }

    /**
     * Ticari arsa özellikleri
     */
    private static function getTicariArsaOzellikleri(): array
    {
        return [
            'imar_durumu' => [
                'label' => 'İmar Durumu',
                'type' => 'select',
                'options' => [
                    'imarlı' => 'İmarlı',
                    'imarli_konut' => 'İmarlı Konut',
                    'imarli_ticari' => 'İmarlı Ticari',
                    'imarli_sanayi' => 'İmarlı Sanayi',
                    'imarsiz' => 'İmarsız',
                    'tarla' => 'Tarla',
                    'bahce' => 'Bahçe'
                ],
                'required' => false,
                'group' => 'İmar Bilgileri'
            ],
            'kaks' => [
                'label' => 'KAKS',
                'type' => 'number',
                'min' => 0,
                'max' => 10,
                'step' => 0.01,
                'unit' => '',
                'required' => false,
                'group' => 'İmar Bilgileri',
                'help' => 'Kat Alanları Kat Sayısı'
            ],
            'gabari' => [
                'label' => 'Gabari',
                'type' => 'number',
                'min' => 0,
                'max' => 100,
                'unit' => 'm',
                'required' => false,
                'group' => 'İmar Bilgileri',
                'help' => 'Maksimum bina yüksekliği'
            ],
            'ada_no' => [
                'label' => 'Ada No',
                'type' => 'text',
                'maxlength' => 50,
                'required' => false,
                'group' => 'Tapu Bilgileri'
            ],
            'parsel_no' => [
                'label' => 'Parsel No',
                'type' => 'text',
                'maxlength' => 50,
                'required' => false,
                'group' => 'Tapu Bilgileri'
            ],
            'ticari_potansiyel' => [
                'label' => 'Ticari Potansiyel',
                'type' => 'textarea',
                'maxlength' => 200,
                'required' => false,
                'group' => 'Ticari Özellikler',
                'help' => 'Ticari kullanım potansiyeli açıklaması'
            ],
            'ana_cadde_cephesi' => [
                'label' => 'Ana Cadde Cephesi',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Konum Özellikleri'
            ],
            'kose_parsel_mi' => [
                'label' => 'Köşe Parsel',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Konum Özellikleri'
            ],
            'trafik_yogunlugu' => [
                'label' => 'Trafik Yoğunluğu',
                'type' => 'select',
                'options' => [
                    'düşük' => 'Düşük',
                    'orta' => 'Orta',
                    'yüksek' => 'Yüksek',
                    'çok_yüksek' => 'Çok Yüksek'
                ],
                'required' => false,
                'group' => 'Konum Özellikleri'
            ],
            'toplu_tasima_mesafesi' => [
                'label' => 'Toplu Taşıma Mesafesi',
                'type' => 'number',
                'min' => 0,
                'unit' => 'm',
                'required' => false,
                'group' => 'Ulaşım'
            ],
            'elektrik_var_mi' => [
                'label' => 'Elektrik',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Altyapı'
            ],
            'su_var_mi' => [
                'label' => 'Su',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Altyapı'
            ],
            'dogalgaz_var_mi' => [
                'label' => 'Doğalgaz',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Altyapı'
            ],
            'kanalizasyon_var_mi' => [
                'label' => 'Kanalizasyon',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Altyapı'
            ],
        ];
    }

    /**
     * Sanayi arsası özellikleri
     */
    private static function getSanayiArsasiOzellikleri(): array
    {
        $baseProperties = self::getArsaBaseOzellikleri();
        
        return array_merge($baseProperties, [
            'sanayi_bolgesi_tipi' => [
                'label' => 'Sanayi Bölgesi Tipi',
                'type' => 'select',
                'options' => [
                    'organize_sanayi' => 'Organize Sanayi Bölgesi',
                    'kucuk_sanayi' => 'Küçük Sanayi Sitesi',
                    'serbest_bolge' => 'Serbest Bölge',
                    'teknoloji_gelistirme' => 'Teknoloji Geliştirme Bölgesi'
                ],
                'required' => false,
                'group' => 'Sanayi Özellikleri'
            ],
            'cevre_kirliligi_durumu' => [
                'label' => 'Çevre Kirliliği Durumu',
                'type' => 'select',
                'options' => [
                    'temiz' => 'Temiz',
                    'orta' => 'Orta',
                    'kirli' => 'Kirli'
                ],
                'required' => false,
                'group' => 'Çevre Özellikleri'
            ],
            'agir_tasit_erisimi' => [
                'label' => 'Ağır Taşıt Erişimi',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Ulaşım'
            ],
            'elektrik_guc_kapasitesi' => [
                'label' => 'Elektrik Güç Kapasitesi',
                'type' => 'number',
                'min' => 0,
                'unit' => 'kW',
                'required' => false,
                'group' => 'Altyapı'
            ],
        ]);
    }

    /**
     * Konut arsası özellikleri
     */
    private static function getKonutArsasiOzellikleri(): array
    {
        $baseProperties = self::getArsaBaseOzellikleri();
        
        return array_merge($baseProperties, [
            'konut_tipi_uygunlugu' => [
                'label' => 'Konut Tipi Uygunluğu',
                'type' => 'select',
                'options' => [
                    'villa' => 'Villa',
                    'apartman' => 'Apartman',
                    'ikiz_villa' => 'İkiz Villa',
                    'mustakil_ev' => 'Müstakil Ev',
                    'site_ici' => 'Site İçi'
                ],
                'required' => false,
                'group' => 'Konut Özellikleri'
            ],
            'manzara_durumu' => [
                'label' => 'Manzara Durumu',
                'type' => 'select',
                'options' => [
                    'deniz' => 'Deniz Manzarası',
                    'orman' => 'Orman Manzarası',
                    'sehir' => 'Şehir Manzarası',
                    'dag' => 'Dağ Manzarası',
                    'yok' => 'Manzara Yok'
                ],
                'required' => false,
                'group' => 'Konum Özellikleri'
            ],
            'okul_mesafesi' => [
                'label' => 'Okul Mesafesi',
                'type' => 'number',
                'min' => 0,
                'unit' => 'm',
                'required' => false,
                'group' => 'Sosyal Tesisler'
            ],
            'hastane_mesafesi' => [
                'label' => 'Hastane Mesafesi',
                'type' => 'number',
                'min' => 0,
                'unit' => 'm',
                'required' => false,
                'group' => 'Sosyal Tesisler'
            ],
            'guvenlik_durumu' => [
                'label' => 'Güvenlik Durumu',
                'type' => 'select',
                'options' => [
                    'cok_guvenli' => 'Çok Güvenli',
                    'guvenli' => 'Güvenli',
                    'orta' => 'Orta',
                    'guvenli_degil' => 'Güvenli Değil'
                ],
                'required' => false,
                'group' => 'Güvenlik'
            ],
        ]);
    }

    /**
     * Fabrika özellikleri
     */
    private static function getFabrikaOzellikleri(): array
    {
        $baseProperties = self::getIsyeriBaseOzellikleri();
        
        return array_merge($baseProperties, [
            'uretim_alani' => [
                'label' => 'Üretim Alanı',
                'type' => 'number',
                'min' => 0,
                'unit' => 'm²',
                'required' => false,
                'group' => 'Alan Bilgileri'
            ],
            'depolama_alani' => [
                'label' => 'Depolama Alanı',
                'type' => 'number',
                'min' => 0,
                'unit' => 'm²',
                'required' => false,
                'group' => 'Alan Bilgileri'
            ],
            'vinc_kapasitesi' => [
                'label' => 'Vinç Kapasitesi',
                'type' => 'number',
                'min' => 0,
                'unit' => 'ton',
                'required' => false,
                'group' => 'Ekipman'
            ],
            'vinc_sayisi' => [
                'label' => 'Vinç Sayısı',
                'type' => 'number',
                'min' => 0,
                'max' => 50,
                'unit' => 'adet',
                'required' => false,
                'group' => 'Ekipman'
            ],
            'atiksu_aritma_sistemi' => [
                'label' => 'Atıksu Arıtma Sistemi',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Çevre Sistemi'
            ],
            'emisyon_olcum_sistemi' => [
                'label' => 'Emisyon Ölçüm Sistemi',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Çevre Sistemi'
            ],
            'patlama_riski_sinifi' => [
                'label' => 'Patlama Riski Sınıfı',
                'type' => 'select',
                'options' => [
                    'yok' => 'Yok',
                    'düşük' => 'Düşük',
                    'orta' => 'Orta',
                    'yüksek' => 'Yüksek'
                ],
                'required' => false,
                'group' => 'Güvenlik'
            ],
        ]);
    }

    /**
     * Depo özellikleri
     */
    private static function getDepoOzellikleri(): array
    {
        $baseProperties = self::getIsyeriBaseOzellikleri();
        
        return array_merge($baseProperties, [
            'ellecleme_alani' => [
                'label' => 'Elleçleme Alanı',
                'type' => 'number',
                'min' => 0,
                'unit' => 'm²',
                'required' => false,
                'group' => 'Alan Bilgileri'
            ],
            'rampa_sayisi' => [
                'label' => 'Rampa Sayısı',
                'type' => 'number',
                'min' => 0,
                'max' => 50,
                'unit' => 'adet',
                'required' => false,
                'group' => 'Yükleme Sistemi'
            ],
            'dock_sayisi' => [
                'label' => 'Dock Sayısı',
                'type' => 'number',
                'min' => 0,
                'max' => 100,
                'unit' => 'adet',
                'required' => false,
                'group' => 'Yükleme Sistemi'
            ],
            'raf_sistemi_var_mi' => [
                'label' => 'Raf Sistemi',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Depolama Sistemi'
            ],
            'raf_yuksekligi' => [
                'label' => 'Raf Yüksekliği',
                'type' => 'number',
                'min' => 0,
                'max' => 30,
                'unit' => 'm',
                'required' => false,
                'group' => 'Depolama Sistemi'
            ],
            'sicaklik_kontrol_sistemi' => [
                'label' => 'Sıcaklık Kontrol Sistemi',
                'type' => 'select',
                'options' => [
                    'yok' => 'Yok',
                    'isitma' => 'Isıtma',
                    'sogutma' => 'Soğutma',
                    'klima' => 'Klima'
                ],
                'required' => false,
                'group' => 'Kontrol Sistemleri'
            ],
        ]);
    }

    /**
     * Daire özellikleri
     */
    private static function getDaireOzellikleri(): array
    {
        $baseProperties = self::getKonutBaseOzellikleri();
        
        return array_merge($baseProperties, [
            'site_adi' => [
                'label' => 'Site Adı',
                'type' => 'text',
                'maxlength' => 200,
                'required' => false,
                'group' => 'Site Bilgileri'
            ],
            'blok_adi' => [
                'label' => 'Blok Adı',
                'type' => 'text',
                'maxlength' => 100,
                'required' => false,
                'group' => 'Site Bilgileri'
            ],
            'daire_no' => [
                'label' => 'Daire No',
                'type' => 'text',
                'maxlength' => 20,
                'required' => false,
                'group' => 'Site Bilgileri'
            ],
            'aidat_miktari' => [
                'label' => 'Aidat Miktarı',
                'type' => 'number',
                'min' => 0,
                'unit' => '₺',
                'required' => false,
                'group' => 'Mali Bilgiler'
            ],
            'kat_no' => [
                'label' => 'Kat No',
                'type' => 'number',
                'min' => -5,
                'max' => 100,
                'required' => false,
                'group' => 'Bina Bilgileri'
            ],
            'bina_kat_sayisi' => [
                'label' => 'Bina Kat Sayısı',
                'type' => 'number',
                'min' => 1,
                'max' => 100,
                'required' => false,
                'group' => 'Bina Bilgileri'
            ],
        ]);
    }

    /**
     * Villa özellikleri
     */
    private static function getVillaOzellikleri(): array
    {
        $baseProperties = self::getKonutBaseOzellikleri();
        
        return array_merge($baseProperties, [
            'bahce_alani' => [
                'label' => 'Bahçe Alanı',
                'type' => 'number',
                'min' => 0,
                'unit' => 'm²',
                'required' => false,
                'group' => 'Dış Alanlar'
            ],
            'havuz_alani' => [
                'label' => 'Havuz Alanı',
                'type' => 'number',
                'min' => 0,
                'unit' => 'm²',
                'required' => false,
                'group' => 'Dış Alanlar'
            ],
            'garaj_kapasitesi' => [
                'label' => 'Garaj Kapasitesi',
                'type' => 'number',
                'min' => 0,
                'max' => 20,
                'unit' => 'araç',
                'required' => false,
                'group' => 'Otopark'
            ],
            'güvenlik_sistemi_tipi' => [
                'label' => 'Güvenlik Sistemi Tipi',
                'type' => 'text',
                'maxlength' => 100,
                'required' => false,
                'group' => 'Güvenlik'
            ],
            'jenerator_var_mi' => [
                'label' => 'Jeneratör',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Teknik Donanım'
            ],
        ]);
    }

    /**
     * Butik otel özellikleri
     */
    private static function getButikOtelOzellikleri(): array
    {
        $baseProperties = self::getTuristikTesisBaseOzellikleri();
        
        return array_merge($baseProperties, [
            'tema_konsepti' => [
                'label' => 'Tema Konsepti',
                'type' => 'text',
                'maxlength' => 200,
                'required' => false,
                'group' => 'Konsept'
            ],
            'tasarim_stili' => [
                'label' => 'Tasarım Stili',
                'type' => 'text',
                'maxlength' => 200,
                'required' => false,
                'group' => 'Konsept'
            ],
            'kişiselleştirilmiş_hizmet' => [
                'label' => 'Kişiselleştirilmiş Hizmet',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Hizmetler'
            ],
            'sanat_eserleri_var_mi' => [
                'label' => 'Sanat Eserleri',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Dekorasyon'
            ],
            'şarap_mahzeni' => [
                'label' => 'Şarap Mahzeni',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Özel Alanlar'
            ],
        ]);
    }

    /**
     * Arsa base özellikleri
     */
    private static function getArsaBaseOzellikleri(): array
    {
        return [
            'imar_durumu' => [
                'label' => 'İmar Durumu',
                'type' => 'select',
                'options' => [
                    'imarlı' => 'İmarlı',
                    'imarsiz' => 'İmarsız',
                    'tarla' => 'Tarla'
                ],
                'required' => false,
                'group' => 'İmar Bilgileri'
            ],
            'kaks' => [
                'label' => 'KAKS',
                'type' => 'number',
                'min' => 0,
                'max' => 10,
                'step' => 0.01,
                'required' => false,
                'group' => 'İmar Bilgileri'
            ],
            'ada_no' => [
                'label' => 'Ada No',
                'type' => 'text',
                'maxlength' => 50,
                'required' => false,
                'group' => 'Tapu Bilgileri'
            ],
            'parsel_no' => [
                'label' => 'Parsel No',
                'type' => 'text',
                'maxlength' => 50,
                'required' => false,
                'group' => 'Tapu Bilgileri'
            ],
        ];
    }

    /**
     * İşyeri base özellikleri
     */
    private static function getIsyeriBaseOzellikleri(): array
    {
        return [
            'kapali_alan' => [
                'label' => 'Kapalı Alan',
                'type' => 'number',
                'min' => 0,
                'unit' => 'm²',
                'required' => false,
                'group' => 'Alan Bilgileri'
            ],
            'acik_alan' => [
                'label' => 'Açık Alan',
                'type' => 'number',
                'min' => 0,
                'unit' => 'm²',
                'required' => false,
                'group' => 'Alan Bilgileri'
            ],
            'tavan_yuksekligi' => [
                'label' => 'Tavan Yüksekliği',
                'type' => 'number',
                'min' => 2,
                'max' => 50,
                'unit' => 'm',
                'required' => false,
                'group' => 'Yapısal Özellikler'
            ],
            'asansor_var_mi' => [
                'label' => 'Asansör',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Teknik Donanım'
            ],
            'otopark_kapasitesi' => [
                'label' => 'Otopark Kapasitesi',
                'type' => 'number',
                'min' => 0,
                'max' => 1000,
                'unit' => 'araç',
                'required' => false,
                'group' => 'Otopark'
            ],
        ];
    }

    /**
     * Konut base özellikleri
     */
    private static function getKonutBaseOzellikleri(): array
    {
        return [
            'oda_sayisi' => [
                'label' => 'Oda Sayısı',
                'type' => 'number',
                'min' => 1,
                'max' => 20,
                'unit' => 'adet',
                'required' => false,
                'group' => 'Oda Bilgileri'
            ],
            'salon_sayisi' => [
                'label' => 'Salon Sayısı',
                'type' => 'number',
                'min' => 1,
                'max' => 10,
                'unit' => 'adet',
                'required' => false,
                'group' => 'Oda Bilgileri'
            ],
            'banyo_sayisi' => [
                'label' => 'Banyo Sayısı',
                'type' => 'number',
                'min' => 1,
                'max' => 10,
                'unit' => 'adet',
                'required' => false,
                'group' => 'Oda Bilgileri'
            ],
            'balkon_sayisi' => [
                'label' => 'Balkon Sayısı',
                'type' => 'number',
                'min' => 0,
                'max' => 20,
                'unit' => 'adet',
                'required' => false,
                'group' => 'Oda Bilgileri'
            ],
            'asansor_var_mi' => [
                'label' => 'Asansör',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Bina Özellikleri'
            ],
            'otopark_var_mi' => [
                'label' => 'Otopark',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Bina Özellikleri'
            ],
            'isitma_tipi' => [
                'label' => 'Isıtma Tipi',
                'type' => 'text',
                'maxlength' => 100,
                'required' => false,
                'group' => 'Teknik Donanım'
            ],
        ];
    }

    /**
     * Turistik tesis base özellikleri
     */
    private static function getTuristikTesisBaseOzellikleri(): array
    {
        return [
            'oda_sayisi' => [
                'label' => 'Oda Sayısı',
                'type' => 'number',
                'min' => 1,
                'max' => 1000,
                'unit' => 'adet',
                'required' => false,
                'group' => 'Kapasite'
            ],
            'yatak_kapasitesi' => [
                'label' => 'Yatak Kapasitesi',
                'type' => 'number',
                'min' => 1,
                'max' => 2000,
                'unit' => 'kişi',
                'required' => false,
                'group' => 'Kapasite'
            ],
            'restoran_var_mi' => [
                'label' => 'Restoran',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Hizmetler'
            ],
            'havuz_var_mi' => [
                'label' => 'Havuz',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Sosyal Tesisler'
            ],
            'spa_var_mi' => [
                'label' => 'Spa',
                'type' => 'checkbox',
                'required' => false,
                'group' => 'Sosyal Tesisler'
            ],
            'yildiz_sayisi' => [
                'label' => 'Yıldız Sayısı',
                'type' => 'number',
                'min' => 1,
                'max' => 5,
                'unit' => 'yıldız',
                'required' => false,
                'group' => 'Sınıflandırma'
            ],
        ];
    }

    /**
     * Özellik gruplarını döndür
     */
    public static function getOzellikGruplari(string $mulkType): array
    {
        $ozellikler = self::getOzellikTanimlari($mulkType);
        $gruplar = [];

        foreach ($ozellikler as $ozellik) {
            $grup = $ozellik['group'] ?? 'Diğer';
            if (!in_array($grup, $gruplar)) {
                $gruplar[] = $grup;
            }
        }

        return $gruplar;
    }

    /**
     * Gruba göre özellikleri döndür
     */
    public static function getOzelliklerByGroup(string $mulkType, string $group): array
    {
        $ozellikler = self::getOzellikTanimlari($mulkType);
        $grupOzellikleri = [];

        foreach ($ozellikler as $key => $ozellik) {
            if (($ozellik['group'] ?? 'Diğer') === $group) {
                $grupOzellikleri[$key] = $ozellik;
            }
        }

        return $grupOzellikleri;
    }
}