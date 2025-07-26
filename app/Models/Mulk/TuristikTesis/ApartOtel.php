<?php

namespace App\Models\Mulk\TuristikTesis;

class ApartOtel extends TuristikTesis
{
    public function getMulkType(): string
    {
        return 'apart_otel';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'mutfakli_oda_orani',
            'günlük_temizlik',
            'çamaşır_makinesi_var_mi',
            'bulaşık_makinesi_var_mi',
            'balkonlu_oda_orani',
            'aile_odası_sayisi',
            'uzun_konaklama_uygun',
            'aylık_kiralama',
            'evcil_hayvan_kabul',
            'market_yakınlığı',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'mutfakli_oda_orani' => 'nullable|numeric|min:0|max:100',
            'günlük_temizlik' => 'nullable|boolean',
            'çamaşır_makinesi_var_mi' => 'nullable|boolean',
            'bulaşık_makinesi_var_mi' => 'nullable|boolean',
            'balkonlu_oda_orani' => 'nullable|numeric|min:0|max:100',
            'aile_odası_sayisi' => 'nullable|integer|min:0',
            'uzun_konaklama_uygun' => 'nullable|boolean',
            'aylık_kiralama' => 'nullable|boolean',
            'evcil_hayvan_kabul' => 'nullable|boolean',
            'market_yakınlığı' => 'nullable|numeric|min:0',
        ]);
    }
}