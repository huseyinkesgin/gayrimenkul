<?php

namespace App\Models\Mulk\Isyeri;

class Magaza extends Isyeri
{
    public function getMulkType(): string
    {
        return 'magaza';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'vitrin_uzunlugu',
            'depo_alani',
            'kasa_alani',
            'deneme_kabini_sayisi',
            'klima_var_mi',
            'muzik_sistemi_var_mi',
            'guvenlik_kamerasi_var_mi',
            'alarm_sistemi_var_mi',
            'ana_cadde_uzerinde_mi',
            'yaya_trafigi_yogunlugu',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'vitrin_uzunlugu' => 'nullable|numeric|min:0|max:50',
            'depo_alani' => 'nullable|numeric|min:0',
            'kasa_alani' => 'nullable|numeric|min:0',
            'deneme_kabini_sayisi' => 'nullable|integer|min:0|max:20',
            'klima_var_mi' => 'nullable|boolean',
            'muzik_sistemi_var_mi' => 'nullable|boolean',
            'guvenlik_kamerasi_var_mi' => 'nullable|boolean',
            'alarm_sistemi_var_mi' => 'nullable|boolean',
            'ana_cadde_uzerinde_mi' => 'nullable|boolean',
            'yaya_trafigi_yogunlugu' => 'nullable|in:düşük,orta,yüksek,çok_yüksek',
        ]);
    }
}