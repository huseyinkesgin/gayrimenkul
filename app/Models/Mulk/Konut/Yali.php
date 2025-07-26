<?php

namespace App\Models\Mulk\Konut;

class Yali extends Konut
{
    public function getMulkType(): string
    {
        return 'yali';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'deniz_cephesi_uzunlugu',
            'iskele_var_mi',
            'tekne_yanaşma_yeri',
            'deniz_manzarasi_orani',
            'sahil_tipi',
            'dalga_kırıcı_var_mi',
            'deniz_sporları_imkani',
            'balık_tutma_imkani',
            'tarihi_deger',
            'restorasyon_durumu',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'deniz_cephesi_uzunlugu' => 'nullable|numeric|min:0',
            'iskele_var_mi' => 'nullable|boolean',
            'tekne_yanaşma_yeri' => 'nullable|boolean',
            'deniz_manzarasi_orani' => 'nullable|numeric|min:0|max:100',
            'sahil_tipi' => 'nullable|in:kumsal,kayalık,beton',
            'dalga_kırıcı_var_mi' => 'nullable|boolean',
            'deniz_sporları_imkani' => 'nullable|boolean',
            'balık_tutma_imkani' => 'nullable|boolean',
            'tarihi_deger' => 'nullable|boolean',
            'restorasyon_durumu' => 'nullable|in:gerekli_değil,kısmi,kapsamlı',
        ]);
    }
}