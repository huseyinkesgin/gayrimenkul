<?php

namespace App\Models\Mulk\Arsa;

class KonutArsasi extends Arsa
{
    /**
     * Mülk tipini döndür
     */
    public function getMulkType(): string
    {
        return 'konut_arsasi';
    }

    /**
     * Konut arsası için ek geçerli özellikler
     */
    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'konut_tipi_uygunlugu',
            'manzara_durumu',
            'cevre_yesil_alan_orani',
            'okul_mesafesi',
            'hastane_mesafesi',
            'alisveris_merkezi_mesafesi',
            'park_mesafesi',
            'cami_mesafesi',
            'toplu_tasima_durumu',
            'guvenlik_durumu',
            'komsu_konut_kalitesi',
            'gelecek_imar_planlari',
            'cocuk_oyun_alani_mesafesi',
            'spor_tesisi_mesafesi',
            'gurultu_seviyesi',
        ]);
    }

    /**
     * Konut arsası için ek validation kuralları
     */
    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'konut_tipi_uygunlugu' => 'nullable|in:villa,apartman,ikiz_villa,mustakil_ev,site_ici',
            'manzara_durumu' => 'nullable|in:deniz,orman,sehir,dag,yok',
            'cevre_yesil_alan_orani' => 'nullable|numeric|min:0|max:100',
            'okul_mesafesi' => 'nullable|numeric|min:0',
            'hastane_mesafesi' => 'nullable|numeric|min:0',
            'alisveris_merkezi_mesafesi' => 'nullable|numeric|min:0',
            'park_mesafesi' => 'nullable|numeric|min:0',
            'cami_mesafesi' => 'nullable|numeric|min:0',
            'toplu_tasima_durumu' => 'nullable|in:cok_iyi,iyi,orta,kotu',
            'guvenlik_durumu' => 'nullable|in:cok_guvenli,guvenli,orta,guvenli_degil',
            'komsu_konut_kalitesi' => 'nullable|in:lux,orta_ust,orta,alt',
            'gelecek_imar_planlari' => 'nullable|string|max:500',
            'cocuk_oyun_alani_mesafesi' => 'nullable|numeric|min:0',
            'spor_tesisi_mesafesi' => 'nullable|numeric|min:0',
            'gurultu_seviyesi' => 'nullable|in:sessiz,orta,gurultulu',
        ]);
    }

    /**
     * Konut tipi uygunluğu etiketi
     */
    public function getKonutTipiUygunluguLabelAttribute(): string
    {
        $tip = $this->getProperty('konut_tipi_uygunlugu');
        
        return match ($tip) {
            'villa' => 'Villa',
            'apartman' => 'Apartman',
            'ikiz_villa' => 'İkiz Villa',
            'mustakil_ev' => 'Müstakil Ev',
            'site_ici' => 'Site İçi',
            default => 'Belirtilmemiş'
        };
    }

    /**
     * Manzara durumu etiketi
     */
    public function getManzaraDurumuLabelAttribute(): string
    {
        $manzara = $this->getProperty('manzara_durumu');
        
        return match ($manzara) {
            'deniz' => 'Deniz Manzarası',
            'orman' => 'Orman Manzarası',
            'sehir' => 'Şehir Manzarası',
            'dag' => 'Dağ Manzarası',
            'yok' => 'Manzara Yok',
            default => 'Belirtilmemiş'
        };
    }

    /**
     * Yaşam kalitesi değerlendirmesi
     */
    public function getYasamKalitesiDegerlendirmesiAttribute(): array
    {
        $puan = 0;
        $detaylar = [];

        // Manzara durumu
        $manzara = $this->getProperty('manzara_durumu');
        $manzaraPuan = match ($manzara) {
            'deniz' => 25,
            'orman' => 20,
            'dag' => 18,
            'sehir' => 10,
            'yok' => 0,
            default => 0
        };
        if ($manzaraPuan > 0) {
            $puan += $manzaraPuan;
            $detaylar[] = "Manzara: {$this->manzara_durumu_label} (+{$manzaraPuan})";
        }

        // Çevre yeşil alan oranı
        $yesilAlan = $this->getProperty('cevre_yesil_alan_orani');
        if ($yesilAlan !== null) {
            $yesilAlanPuan = match (true) {
                $yesilAlan >= 50 => 20,
                $yesilAlan >= 30 => 15,
                $yesilAlan >= 15 => 10,
                $yesilAlan >= 5 => 5,
                default => 0
            };
            if ($yesilAlanPuan > 0) {
                $puan += $yesilAlanPuan;
                $detaylar[] = "Yeşil alan: %{$yesilAlan} (+{$yesilAlanPuan})";
            }
        }

        // Okul mesafesi
        $okulMesafe = $this->getProperty('okul_mesafesi');
        if ($okulMesafe !== null) {
            $okulPuan = match (true) {
                $okulMesafe <= 500 => 15,
                $okulMesafe <= 1000 => 10,
                $okulMesafe <= 2000 => 5,
                default => 0
            };
            if ($okulPuan > 0) {
                $puan += $okulPuan;
                $detaylar[] = "Okul mesafesi: {$okulMesafe}m (+{$okulPuan})";
            }
        }

        // Hastane mesafesi
        $hastaneMesafe = $this->getProperty('hastane_mesafesi');
        if ($hastaneMesafe !== null) {
            $hastanePuan = match (true) {
                $hastaneMesafe <= 2000 => 10,
                $hastaneMesafe <= 5000 => 7,
                $hastaneMesafe <= 10000 => 3,
                default => 0
            };
            if ($hastanePuan > 0) {
                $puan += $hastanePuan;
                $detaylar[] = "Hastane mesafesi: {$hastaneMesafe}m (+{$hastanePuan})";
            }
        }

        // Toplu taşıma durumu
        $topluTasima = $this->getProperty('toplu_tasima_durumu');
        $topluTasimaPuan = match ($topluTasima) {
            'cok_iyi' => 15,
            'iyi' => 12,
            'orta' => 8,
            'kotu' => 3,
            default => 0
        };
        if ($topluTasimaPuan > 0) {
            $puan += $topluTasimaPuan;
            $detaylar[] = "Toplu taşıma: {$topluTasima} (+{$topluTasimaPuan})";
        }

        // Güvenlik durumu
        $guvenlik = $this->getProperty('guvenlik_durumu');
        $guvenlikPuan = match ($guvenlik) {
            'cok_guvenli' => 15,
            'guvenli' => 12,
            'orta' => 8,
            'guvenli_degil' => 0,
            default => 0
        };
        if ($guvenlikPuan > 0) {
            $puan += $guvenlikPuan;
            $detaylar[] = "Güvenlik: {$guvenlik} (+{$guvenlikPuan})";
        }

        // Gürültü seviyesi (negatif etki)
        $gurultu = $this->getProperty('gurultu_seviyesi');
        $gurultuPuan = match ($gurultu) {
            'sessiz' => 10,
            'orta' => 5,
            'gurultulu' => -10,
            default => 0
        };
        if ($gurultuPuan != 0) {
            $puan += $gurultuPuan;
            $detaylar[] = "Gürültü seviyesi: {$gurultu} ({$gurultuPuan})";
        }

        return [
            'puan' => max(0, min($puan, 100)),
            'seviye' => match (true) {
                $puan >= 80 => 'Mükemmel',
                $puan >= 65 => 'Çok İyi',
                $puan >= 50 => 'İyi',
                $puan >= 35 => 'Orta',
                $puan >= 20 => 'Düşük',
                default => 'Yetersiz'
            },
            'detaylar' => $detaylar
        ];
    }

    /**
     * Sosyal tesis yakınlığı
     */
    public function getSosyalTesisYakinligiAttribute(): array
    {
        $tesisler = [];

        $park = $this->getProperty('park_mesafesi');
        if ($park !== null) {
            $tesisler['park'] = [
                'mesafe' => $park,
                'durum' => $park <= 500 ? 'Çok Yakın' : ($park <= 1000 ? 'Yakın' : 'Uzak')
            ];
        }

        $cocukOyun = $this->getProperty('cocuk_oyun_alani_mesafesi');
        if ($cocukOyun !== null) {
            $tesisler['cocuk_oyun_alani'] = [
                'mesafe' => $cocukOyun,
                'durum' => $cocukOyun <= 300 ? 'Çok Yakın' : ($cocukOyun <= 600 ? 'Yakın' : 'Uzak')
            ];
        }

        $spor = $this->getProperty('spor_tesisi_mesafesi');
        if ($spor !== null) {
            $tesisler['spor_tesisi'] = [
                'mesafe' => $spor,
                'durum' => $spor <= 1000 ? 'Çok Yakın' : ($spor <= 2000 ? 'Yakın' : 'Uzak')
            ];
        }

        $alisveris = $this->getProperty('alisveris_merkezi_mesafesi');
        if ($alisveris !== null) {
            $tesisler['alisveris_merkezi'] = [
                'mesafe' => $alisveris,
                'durum' => $alisveris <= 2000 ? 'Çok Yakın' : ($alisveris <= 5000 ? 'Yakın' : 'Uzak')
            ];
        }

        return $tesisler;
    }

    /**
     * Komşu konut kalitesi etiketi
     */
    public function getKomsuKonutKalitesiLabelAttribute(): string
    {
        $kalite = $this->getProperty('komsu_konut_kalitesi');
        
        return match ($kalite) {
            'lux' => 'Lüks',
            'orta_ust' => 'Orta Üst',
            'orta' => 'Orta',
            'alt' => 'Alt Segment',
            default => 'Belirtilmemiş'
        };
    }
}