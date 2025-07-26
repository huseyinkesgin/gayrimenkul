<?php

namespace App\Models\Mulk\Konut;

use App\Models\Mulk\BaseMulk;
use App\Enums\MulkKategorisi;

abstract class Konut extends BaseMulk
{
    public function getMulkKategorisi(): MulkKategorisi
    {
        return MulkKategorisi::KONUT;
    }

    public function getValidProperties(): array
    {
        return [
            'oda_sayisi',
            'salon_sayisi',
            'banyo_sayisi',
            'wc_sayisi',
            'balkon_sayisi',
            'balkon_alani',
            'asansor_var_mi',
            'otopark_var_mi',
            'otopark_tipi',
            'isitma_tipi',
            'yakıt_tipi',
            'klima_var_mi',
            'dogalgaz_var_mi',
            'telefon_var_mi',
            'internet_var_mi',
            'uydu_var_mi',
            'kablo_tv_var_mi',
            'guvenlik_var_mi',
            'kapici_var_mi',
            'havuz_var_mi',
            'sauna_var_mi',
            'spor_salonu_var_mi',
            'cocuk_oyun_alani_var_mi',
            'bahce_var_mi',
            'teras_var_mi',
            'cati_katı_var_mi',
            'bodrum_var_mi',
            'depo_var_mi',
            'mutfak_tipi',
            'banyo_tipi',
            'zemin_tipi',
            'pencere_tipi',
            'kapi_tipi',
            'boyali_mi',
            'tadilat_durumu',
            'esyali_mi',
            'manzara_durumu',
            'kat_no',
            'bina_kat_sayisi',
            'bina_yasi',
            'yapim_yili',
            'tapu_durumu',
            'krediye_uygun_mu',
        ];
    }

    public function getSpecificValidationRules(): array
    {
        return [
            'oda_sayisi' => 'nullable|integer|min:1|max:20',
            'salon_sayisi' => 'nullable|integer|min:1|max:10',
            'banyo_sayisi' => 'nullable|integer|min:1|max:10',
            'wc_sayisi' => 'nullable|integer|min:0|max:10',
            'balkon_sayisi' => 'nullable|integer|min:0|max:20',
            'balkon_alani' => 'nullable|numeric|min:0',
            'asansor_var_mi' => 'nullable|boolean',
            'otopark_var_mi' => 'nullable|boolean',
            'otopark_tipi' => 'nullable|in:acik,kapali,mekanik',
            'isitma_tipi' => 'nullable|string|max:100',
            'yakıt_tipi' => 'nullable|in:dogalgaz,elektrik,kömür,fuel,güneş',
            'klima_var_mi' => 'nullable|boolean',
            'dogalgaz_var_mi' => 'nullable|boolean',
            'telefon_var_mi' => 'nullable|boolean',
            'internet_var_mi' => 'nullable|boolean',
            'uydu_var_mi' => 'nullable|boolean',
            'kablo_tv_var_mi' => 'nullable|boolean',
            'guvenlik_var_mi' => 'nullable|boolean',
            'kapici_var_mi' => 'nullable|boolean',
            'havuz_var_mi' => 'nullable|boolean',
            'sauna_var_mi' => 'nullable|boolean',
            'spor_salonu_var_mi' => 'nullable|boolean',
            'cocuk_oyun_alani_var_mi' => 'nullable|boolean',
            'bahce_var_mi' => 'nullable|boolean',
            'teras_var_mi' => 'nullable|boolean',
            'cati_katı_var_mi' => 'nullable|boolean',
            'bodrum_var_mi' => 'nullable|boolean',
            'depo_var_mi' => 'nullable|boolean',
            'mutfak_tipi' => 'nullable|in:amerikan,kapalı,açık',
            'banyo_tipi' => 'nullable|in:küvet,duşakabin,jakuzi',
            'zemin_tipi' => 'nullable|string|max:100',
            'pencere_tipi' => 'nullable|string|max:100',
            'kapi_tipi' => 'nullable|string|max:100',
            'boyali_mi' => 'nullable|boolean',
            'tadilat_durumu' => 'nullable|in:sıfır,az_kullanılmış,orta,eski,tadilat_gerekli',
            'esyali_mi' => 'nullable|boolean',
            'manzara_durumu' => 'nullable|in:deniz,orman,sehir,dag,yok',
            'kat_no' => 'nullable|integer|min:-5|max:100',
            'bina_kat_sayisi' => 'nullable|integer|min:1|max:100',
            'bina_yasi' => 'nullable|integer|min:0|max:200',
            'yapim_yili' => 'nullable|integer|min:1900|max:' . (date('Y') + 5),
            'tapu_durumu' => 'nullable|string|max:100',
            'krediye_uygun_mu' => 'nullable|boolean',
        ];
    }

    /**
     * Oda salon bilgisi
     */
    public function getOdaSalonBilgisiAttribute(): string
    {
        $oda = $this->getProperty('oda_sayisi', 0);
        $salon = $this->getProperty('salon_sayisi', 1);
        
        return $oda . '+' . $salon;
    }

    /**
     * Sosyal tesis durumu
     */
    public function getSosyalTesisDurumuAttribute(): array
    {
        return [
            'havuz' => $this->getProperty('havuz_var_mi', false),
            'sauna' => $this->getProperty('sauna_var_mi', false),
            'spor_salonu' => $this->getProperty('spor_salonu_var_mi', false),
            'cocuk_oyun_alani' => $this->getProperty('cocuk_oyun_alani_var_mi', false),
        ];
    }

    /**
     * Güvenlik durumu
     */
    public function getGuvenlikDurumuAttribute(): array
    {
        return [
            'guvenlik' => $this->getProperty('guvenlik_var_mi', false),
            'kapici' => $this->getProperty('kapici_var_mi', false),
        ];
    }
}