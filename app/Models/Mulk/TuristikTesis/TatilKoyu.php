<?php

namespace App\Models\Mulk\TuristikTesis;

class TatilKoyu extends TuristikTesis
{
    public function getMulkType(): string
    {
        return 'tatil_koyu';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'arazi_alani',
            'sahil_uzunlugu',
            'özel_plaj_var_mi',
            'villa_sayisi',
            'bungalov_sayisi',
            'ana_restoran_sayisi',
            'a_la_carte_restoran_sayisi',
            'bar_sayisi',
            'havuz_sayisi',
            'aquapark_var_mi',
            'mini_club',
            'teen_club',
            'gece_kulübü',
            'amfi_tiyatro',
            'spor_alanları',
            'su_sporları',
            'dalış_merkezi',
            'golf_sahası',
            'tenis_kortu_sayisi',
            'basketbol_sahası',
            'voleybol_sahası',
            'futbol_sahası',
            'at_binme_tesisi',
            'bisiklet_kiralama',
            'araç_kiralama_ofisi',
            'sağlık_merkezi',
            'eczane',
            'kuaför_salonu',
            'alışveriş_merkezi',
            'konferans_merkezi',
            'düğün_organizasyonu',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'arazi_alani' => 'nullable|numeric|min:0',
            'sahil_uzunlugu' => 'nullable|numeric|min:0',
            'özel_plaj_var_mi' => 'nullable|boolean',
            'villa_sayisi' => 'nullable|integer|min:0',
            'bungalov_sayisi' => 'nullable|integer|min:0',
            'ana_restoran_sayisi' => 'nullable|integer|min:0',
            'a_la_carte_restoran_sayisi' => 'nullable|integer|min:0',
            'bar_sayisi' => 'nullable|integer|min:0',
            'havuz_sayisi' => 'nullable|integer|min:0',
            'aquapark_var_mi' => 'nullable|boolean',
            'mini_club' => 'nullable|boolean',
            'teen_club' => 'nullable|boolean',
            'gece_kulübü' => 'nullable|boolean',
            'amfi_tiyatro' => 'nullable|boolean',
            'spor_alanları' => 'nullable|string|max:500',
            'su_sporları' => 'nullable|string|max:500',
            'dalış_merkezi' => 'nullable|boolean',
            'golf_sahası' => 'nullable|boolean',
            'tenis_kortu_sayisi' => 'nullable|integer|min:0',
            'basketbol_sahası' => 'nullable|integer|min:0',
            'voleybol_sahası' => 'nullable|integer|min:0',
            'futbol_sahası' => 'nullable|integer|min:0',
            'at_binme_tesisi' => 'nullable|boolean',
            'bisiklet_kiralama' => 'nullable|boolean',
            'araç_kiralama_ofisi' => 'nullable|boolean',
            'sağlık_merkezi' => 'nullable|boolean',
            'eczane' => 'nullable|boolean',
            'kuaför_salonu' => 'nullable|boolean',
            'alışveriş_merkezi' => 'nullable|boolean',
            'konferans_merkezi' => 'nullable|boolean',
            'düğün_organizasyonu' => 'nullable|boolean',
        ]);
    }
}