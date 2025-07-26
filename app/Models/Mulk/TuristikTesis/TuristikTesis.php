<?php

namespace App\Models\Mulk\TuristikTesis;

use App\Models\Mulk\BaseMulk;
use App\Enums\MulkKategorisi;

abstract class TuristikTesis extends BaseMulk
{
    public function getMulkKategorisi(): MulkKategorisi
    {
        return MulkKategorisi::TURISTIK_TESIS;
    }

    public function getValidProperties(): array
    {
        return [
            'oda_sayisi',
            'yatak_kapasitesi',
            'kat_sayisi',
            'resepsiyon_var_mi',
            'restoran_var_mi',
            'restoran_kapasitesi',
            'bar_var_mi',
            'kafe_var_mi',
            'havuz_var_mi',
            'havuz_tipi',
            'spa_var_mi',
            'fitness_var_mi',
            'toplanti_salonu_var_mi',
            'konferans_salonu_var_mi',
            'etkinlik_alani_var_mi',
            'cocuk_oyun_alani_var_mi',
            'animasyon_hizmeti',
            'otopark_kapasitesi',
            'vale_hizmeti',
            'oda_servisi',
            'camasir_hizmeti',
            'temizlik_hizmeti',
            'wifi_var_mi',
            'klima_var_mi',
            'isitma_sistemi',
            'jenerator_var_mi',
            'guvenlik_sistemi',
            'yangin_sistemi',
            'asansor_var_mi',
            'engelli_erişimi',
            'pet_kabul_ediyor_mu',
            'sigara_içme_alani',
            'yildiz_sayisi',
            'turizm_belgesi',
            'işletme_ruhsati',
            'çevre_izni',
            'belediye_ruhsati',
        ];
    }

    public function getSpecificValidationRules(): array
    {
        return [
            'oda_sayisi' => 'nullable|integer|min:1|max:1000',
            'yatak_kapasitesi' => 'nullable|integer|min:1|max:2000',
            'kat_sayisi' => 'nullable|integer|min:1|max:50',
            'resepsiyon_var_mi' => 'nullable|boolean',
            'restoran_var_mi' => 'nullable|boolean',
            'restoran_kapasitesi' => 'nullable|integer|min:0|max:1000',
            'bar_var_mi' => 'nullable|boolean',
            'kafe_var_mi' => 'nullable|boolean',
            'havuz_var_mi' => 'nullable|boolean',
            'havuz_tipi' => 'nullable|in:açık,kapalı,her_ikisi',
            'spa_var_mi' => 'nullable|boolean',
            'fitness_var_mi' => 'nullable|boolean',
            'toplanti_salonu_var_mi' => 'nullable|boolean',
            'konferans_salonu_var_mi' => 'nullable|boolean',
            'etkinlik_alani_var_mi' => 'nullable|boolean',
            'cocuk_oyun_alani_var_mi' => 'nullable|boolean',
            'animasyon_hizmeti' => 'nullable|boolean',
            'otopark_kapasitesi' => 'nullable|integer|min:0|max:1000',
            'vale_hizmeti' => 'nullable|boolean',
            'oda_servisi' => 'nullable|boolean',
            'camasir_hizmeti' => 'nullable|boolean',
            'temizlik_hizmeti' => 'nullable|boolean',
            'wifi_var_mi' => 'nullable|boolean',
            'klima_var_mi' => 'nullable|boolean',
            'isitma_sistemi' => 'nullable|string|max:100',
            'jenerator_var_mi' => 'nullable|boolean',
            'guvenlik_sistemi' => 'nullable|string|max:200',
            'yangin_sistemi' => 'nullable|string|max:200',
            'asansor_var_mi' => 'nullable|boolean',
            'engelli_erişimi' => 'nullable|boolean',
            'pet_kabul_ediyor_mu' => 'nullable|boolean',
            'sigara_içme_alani' => 'nullable|boolean',
            'yildiz_sayisi' => 'nullable|integer|min:1|max:5',
            'turizm_belgesi' => 'nullable|string|max:200',
            'işletme_ruhsati' => 'nullable|string|max:200',
            'çevre_izni' => 'nullable|string|max:200',
            'belediye_ruhsati' => 'nullable|string|max:200',
        ];
    }

    /**
     * Hizmet kalitesi puanı
     */
    public function getHizmetKalitesiPuaniAttribute(): array
    {
        $puan = 0;
        $detaylar = [];

        $hizmetler = [
            'resepsiyon_var_mi' => ['puan' => 10, 'ad' => 'Resepsiyon'],
            'restoran_var_mi' => ['puan' => 15, 'ad' => 'Restoran'],
            'bar_var_mi' => ['puan' => 8, 'ad' => 'Bar'],
            'havuz_var_mi' => ['puan' => 12, 'ad' => 'Havuz'],
            'spa_var_mi' => ['puan' => 10, 'ad' => 'Spa'],
            'fitness_var_mi' => ['puan' => 8, 'ad' => 'Fitness'],
            'oda_servisi' => ['puan' => 10, 'ad' => 'Oda Servisi'],
            'vale_hizmeti' => ['puan' => 7, 'ad' => 'Vale Hizmeti'],
            'animasyon_hizmeti' => ['puan' => 8, 'ad' => 'Animasyon'],
            'wifi_var_mi' => ['puan' => 5, 'ad' => 'WiFi'],
            'engelli_erişimi' => ['puan' => 7, 'ad' => 'Engelli Erişimi'],
        ];

        foreach ($hizmetler as $hizmet => $bilgi) {
            if ($this->getProperty($hizmet, false)) {
                $puan += $bilgi['puan'];
                $detaylar[] = $bilgi['ad'] . ' (+' . $bilgi['puan'] . ')';
            }
        }

        return [
            'puan' => $puan,
            'seviye' => match (true) {
                $puan >= 80 => 'Mükemmel',
                $puan >= 60 => 'Çok İyi',
                $puan >= 40 => 'İyi',
                $puan >= 20 => 'Orta',
                default => 'Temel'
            },
            'detaylar' => $detaylar
        ];
    }

    /**
     * Kapasite verimliliği
     */
    public function getKapasiteVerimliligiAttribute(): array
    {
        $odaSayisi = $this->getProperty('oda_sayisi', 0);
        $yatakKapasitesi = $this->getProperty('yatak_kapasitesi', 0);
        $restoranKapasitesi = $this->getProperty('restoran_kapasitesi', 0);
        $otoparkKapasitesi = $this->getProperty('otopark_kapasitesi', 0);

        $verimlilik = [];

        // Oda başına yatak oranı
        if ($odaSayisi > 0 && $yatakKapasitesi > 0) {
            $odaBasinaYatak = $yatakKapasitesi / $odaSayisi;
            $verimlilik['oda_yatak_orani'] = [
                'oran' => round($odaBasinaYatak, 1),
                'degerlendirme' => match (true) {
                    $odaBasinaYatak >= 2.5 => 'Çok İyi',
                    $odaBasinaYatak >= 2.0 => 'İyi',
                    $odaBasinaYatak >= 1.5 => 'Orta',
                    default => 'Düşük'
                }
            ];
        }

        // Restoran kapasitesi oranı
        if ($yatakKapasitesi > 0 && $restoranKapasitesi > 0) {
            $restoranOrani = ($restoranKapasitesi / $yatakKapasitesi) * 100;
            $verimlilik['restoran_orani'] = [
                'oran' => round($restoranOrani, 1),
                'degerlendirme' => match (true) {
                    $restoranOrani >= 80 => 'Çok Yeterli',
                    $restoranOrani >= 60 => 'Yeterli',
                    $restoranOrani >= 40 => 'Orta',
                    default => 'Yetersiz'
                }
            ];
        }

        // Otopark oranı
        if ($odaSayisi > 0 && $otoparkKapasitesi > 0) {
            $otoparkOrani = ($otoparkKapasitesi / $odaSayisi) * 100;
            $verimlilik['otopark_orani'] = [
                'oran' => round($otoparkOrani, 1),
                'degerlendirme' => match (true) {
                    $otoparkOrani >= 100 => 'Çok Yeterli',
                    $otoparkOrani >= 70 => 'Yeterli',
                    $otoparkOrani >= 50 => 'Orta',
                    default => 'Yetersiz'
                }
            ];
        }

        return $verimlilik;
    }
}