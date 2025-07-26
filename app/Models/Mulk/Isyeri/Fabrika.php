<?php

namespace App\Models\Mulk\Isyeri;

class Fabrika extends Isyeri
{
    /**
     * Mülk tipini döndür
     */
    public function getMulkType(): string
    {
        return 'fabrika';
    }

    /**
     * Fabrika için ek geçerli özellikler
     */
    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'uretim_alani',
            'depolama_alani',
            'hammadde_depo_alani',
            'mamul_depo_alani',
            'vinc_kapasitesi',
            'vinc_sayisi',
            'forklift_kapasitesi',
            'konveyor_sistemi',
            'havalandirma_kapasitesi',
            'atiksu_aritma_sistemi',
            'cevre_izin_durumu',
            'emisyon_olcum_sistemi',
            'gurultu_izolasyonu',
            'titresim_izolasyonu',
            'patlama_riski_sinifi',
            'kimyasal_depolama_alani',
            'laboratuvar_alani',
            'kalite_kontrol_alani',
            'personel_sosyal_alani',
            'yemekhane_kapasitesi',
            'soyunma_odasi_kapasitesi',
            'dus_kapasitesi',
            'ilkyardim_odasi',
            'itfaiye_sistemi',
            'acil_cikis_sayisi',
            'is_guvenligi_sertifikasi',
        ]);
    }

    /**
     * Fabrika için ek validation kuralları
     */
    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'uretim_alani' => 'nullable|numeric|min:0',
            'depolama_alani' => 'nullable|numeric|min:0',
            'hammadde_depo_alani' => 'nullable|numeric|min:0',
            'mamul_depo_alani' => 'nullable|numeric|min:0',
            'vinc_kapasitesi' => 'nullable|numeric|min:0',
            'vinc_sayisi' => 'nullable|integer|min:0|max:50',
            'forklift_kapasitesi' => 'nullable|numeric|min:0',
            'konveyor_sistemi' => 'nullable|boolean',
            'havalandirma_kapasitesi' => 'nullable|numeric|min:0',
            'atiksu_aritma_sistemi' => 'nullable|boolean',
            'cevre_izin_durumu' => 'nullable|string|max:200',
            'emisyon_olcum_sistemi' => 'nullable|boolean',
            'gurultu_izolasyonu' => 'nullable|boolean',
            'titresim_izolasyonu' => 'nullable|boolean',
            'patlama_riski_sinifi' => 'nullable|in:yok,düşük,orta,yüksek',
            'kimyasal_depolama_alani' => 'nullable|numeric|min:0',
            'laboratuvar_alani' => 'nullable|numeric|min:0',
            'kalite_kontrol_alani' => 'nullable|numeric|min:0',
            'personel_sosyal_alani' => 'nullable|numeric|min:0',
            'yemekhane_kapasitesi' => 'nullable|integer|min:0|max:1000',
            'soyunma_odasi_kapasitesi' => 'nullable|integer|min:0|max:1000',
            'dus_kapasitesi' => 'nullable|integer|min:0|max:100',
            'ilkyardim_odasi' => 'nullable|boolean',
            'itfaiye_sistemi' => 'nullable|string|max:200',
            'acil_cikis_sayisi' => 'nullable|integer|min:1|max:20',
            'is_guvenligi_sertifikasi' => 'nullable|string|max:200',
        ]);
    }

    /**
     * Üretim alanı dağılımı
     */
    public function getUretimAlaniDagilimiAttribute(): array
    {
        $uretimAlani = $this->getProperty('uretim_alani', 0);
        $depolamaAlani = $this->getProperty('depolama_alani', 0);
        $ofisAlani = $this->getProperty('ofis_alani', 0);
        $sosyalAlan = $this->getProperty('personel_sosyal_alani', 0);
        $toplamAlan = $this->getProperty('kapali_alan', 0);

        if ($toplamAlan <= 0) {
            return [];
        }

        return [
            'uretim' => [
                'alan' => $uretimAlani,
                'oran' => round(($uretimAlani / $toplamAlan) * 100, 1)
            ],
            'depolama' => [
                'alan' => $depolamaAlani,
                'oran' => round(($depolamaAlani / $toplamAlan) * 100, 1)
            ],
            'ofis' => [
                'alan' => $ofisAlani,
                'oran' => round(($ofisAlani / $toplamAlan) * 100, 1)
            ],
            'sosyal' => [
                'alan' => $sosyalAlan,
                'oran' => round(($sosyalAlan / $toplamAlan) * 100, 1)
            ],
        ];
    }

    /**
     * Malzeme taşıma kapasitesi
     */
    public function getMalzemeTasimaKapasitesiAttribute(): array
    {
        $vincKapasite = $this->getProperty('vinc_kapasitesi', 0);
        $vincSayisi = $this->getProperty('vinc_sayisi', 0);
        $forkliftKapasite = $this->getProperty('forklift_kapasitesi', 0);
        $konveyorVar = $this->getProperty('konveyor_sistemi', false);

        return [
            'vinc' => [
                'kapasite' => $vincKapasite,
                'sayisi' => $vincSayisi,
                'toplam_kapasite' => $vincKapasite * $vincSayisi
            ],
            'forklift' => [
                'kapasite' => $forkliftKapasite
            ],
            'konveyor' => [
                'var_mi' => $konveyorVar
            ],
            'genel_degerlendirme' => $this->getMalzemeTasimaGenel()
        ];
    }

    /**
     * Malzeme taşıma genel değerlendirme
     */
    private function getMalzemeTasimaGenel(): string
    {
        $vincKapasite = $this->getProperty('vinc_kapasitesi', 0) * $this->getProperty('vinc_sayisi', 0);
        $forkliftKapasite = $this->getProperty('forklift_kapasitesi', 0);
        $konveyorVar = $this->getProperty('konveyor_sistemi', false);

        $puan = 0;
        if ($vincKapasite >= 10) $puan += 30;
        elseif ($vincKapasite >= 5) $puan += 20;
        elseif ($vincKapasite > 0) $puan += 10;

        if ($forkliftKapasite >= 5) $puan += 25;
        elseif ($forkliftKapasite >= 2) $puan += 15;
        elseif ($forkliftKapasite > 0) $puan += 10;

        if ($konveyorVar) $puan += 20;

        return match (true) {
            $puan >= 70 => 'Çok İyi',
            $puan >= 50 => 'İyi',
            $puan >= 30 => 'Orta',
            $puan >= 15 => 'Düşük',
            default => 'Yetersiz'
        };
    }

    /**
     * Çevre uyumluluk durumu
     */
    public function getCevreUyumlulukDurumuAttribute(): array
    {
        $atiksuAritma = $this->getProperty('atiksu_aritma_sistemi', false);
        $emisyonOlcum = $this->getProperty('emisyon_olcum_sistemi', false);
        $gurultuIzolasyon = $this->getProperty('gurultu_izolasyonu', false);
        $titresimIzolasyon = $this->getProperty('titresim_izolasyonu', false);
        $cevreIzin = $this->getProperty('cevre_izin_durumu');

        $puan = 0;
        $detaylar = [];

        if ($atiksuAritma) {
            $puan += 25;
            $detaylar[] = 'Atıksu arıtma sistemi mevcut';
        }

        if ($emisyonOlcum) {
            $puan += 20;
            $detaylar[] = 'Emisyon ölçüm sistemi mevcut';
        }

        if ($gurultuIzolasyon) {
            $puan += 15;
            $detaylar[] = 'Gürültü izolasyonu mevcut';
        }

        if ($titresimIzolasyon) {
            $puan += 15;
            $detaylar[] = 'Titreşim izolasyonu mevcut';
        }

        if (!empty($cevreIzin)) {
            $puan += 25;
            $detaylar[] = 'Çevre izni mevcut';
        }

        return [
            'puan' => $puan,
            'seviye' => match (true) {
                $puan >= 80 => 'Çok İyi',
                $puan >= 60 => 'İyi',
                $puan >= 40 => 'Orta',
                $puan >= 20 => 'Düşük',
                default => 'Yetersiz'
            },
            'detaylar' => $detaylar
        ];
    }

    /**
     * İş güvenliği durumu
     */
    public function getIsGuvenligiDurumuAttribute(): array
    {
        $patlamaRiski = $this->getProperty('patlama_riski_sinifi', 'yok');
        $ilkyardimOdasi = $this->getProperty('ilkyardim_odasi', false);
        $itfaiyeSistemi = $this->getProperty('itfaiye_sistemi');
        $acilCikisSayisi = $this->getProperty('acil_cikis_sayisi', 0);
        $isGuvenligiSertifika = $this->getProperty('is_guvenligi_sertifikasi');

        $puan = 0;
        $detaylar = [];

        // Patlama riski (düşük risk daha iyi)
        $patlamaPuan = match ($patlamaRiski) {
            'yok' => 25,
            'düşük' => 20,
            'orta' => 10,
            'yüksek' => 0,
            default => 0
        };
        $puan += $patlamaPuan;
        $detaylar[] = "Patlama riski: {$patlamaRiski}";

        if ($ilkyardimOdasi) {
            $puan += 15;
            $detaylar[] = 'İlkyardım odası mevcut';
        }

        if (!empty($itfaiyeSistemi)) {
            $puan += 20;
            $detaylar[] = 'İtfaiye sistemi mevcut';
        }

        if ($acilCikisSayisi >= 3) {
            $puan += 20;
            $detaylar[] = 'Yeterli acil çıkış';
        } elseif ($acilCikisSayisi >= 2) {
            $puan += 15;
            $detaylar[] = 'Orta seviye acil çıkış';
        } elseif ($acilCikisSayisi >= 1) {
            $puan += 10;
            $detaylar[] = 'Minimum acil çıkış';
        }

        if (!empty($isGuvenligiSertifika)) {
            $puan += 20;
            $detaylar[] = 'İş güvenliği sertifikası mevcut';
        }

        return [
            'puan' => $puan,
            'seviye' => match (true) {
                $puan >= 80 => 'Çok Güvenli',
                $puan >= 60 => 'Güvenli',
                $puan >= 40 => 'Orta',
                $puan >= 20 => 'Riskli',
                default => 'Çok Riskli'
            ],
            'detaylar' => $detaylar
        ];
    }

    /**
     * Personel sosyal alan yeterliliği
     */
    public function getPersonelSosyalAlanYeterliligiAttribute(): string
    {
        $sosyalAlan = $this->getProperty('personel_sosyal_alani', 0);
        $yemekhaneKapasite = $this->getProperty('yemekhane_kapasitesi', 0);
        $soyunmaKapasite = $this->getProperty('soyunma_odasi_kapasitesi', 0);
        $dusKapasite = $this->getProperty('dus_kapasitesi', 0);

        // Genel bir personel sayısı tahmini (üretim alanı / 20m² per person)
        $uretimAlani = $this->getProperty('uretim_alani', 0);
        $tahminiPersonel = $uretimAlani > 0 ? ceil($uretimAlani / 20) : 0;

        if ($tahminiPersonel <= 0) {
            return 'Hesaplanamaz';
        }

        $yeterlilikPuani = 0;

        // Yemekhane yeterliliği
        if ($yemekhaneKapasite >= $tahminiPersonel * 0.8) $yeterlilikPuani += 25;
        elseif ($yemekhaneKapasite >= $tahminiPersonel * 0.5) $yeterlilikPuani += 15;
        elseif ($yemekhaneKapasite > 0) $yeterlilikPuani += 10;

        // Soyunma odası yeterliliği
        if ($soyunmaKapasite >= $tahminiPersonel) $yeterlilikPuani += 25;
        elseif ($soyunmaKapasite >= $tahminiPersonel * 0.7) $yeterlilikPuani += 15;
        elseif ($soyunmaKapasite > 0) $yeterlilikPuani += 10;

        // Duş yeterliliği
        $gerekliDus = ceil($tahminiPersonel / 10); // Her 10 kişiye 1 duş
        if ($dusKapasite >= $gerekliDus) $yeterlilikPuani += 25;
        elseif ($dusKapasite >= $gerekliDus * 0.7) $yeterlilikPuani += 15;
        elseif ($dusKapasite > 0) $yeterlilikPuani += 10;

        // Sosyal alan yeterliliği
        $gerekliSosyalAlan = $tahminiPersonel * 2; // Kişi başı 2m²
        if ($sosyalAlan >= $gerekliSosyalAlan) $yeterlilikPuani += 25;
        elseif ($sosyalAlan >= $gerekliSosyalAlan * 0.7) $yeterlilikPuani += 15;
        elseif ($sosyalAlan > 0) $yeterlilikPuani += 10;

        return match (true) {
            $yeterlilikPuani >= 80 => 'Çok Yeterli',
            $yeterlilikPuani >= 60 => 'Yeterli',
            $yeterlilikPuani >= 40 => 'Orta',
            $yeterlilikPuani >= 20 => 'Yetersiz',
            default => 'Çok Yetersiz'
        };
    }
}