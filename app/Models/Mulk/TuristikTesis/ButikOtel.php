<?php

namespace App\Models\Mulk\TuristikTesis;

class ButikOtel extends TuristikTesis
{
    public function getMulkType(): string
    {
        return 'butik_otel';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'tema_konsepti',
            'tasarim_stili',
            'özel_hizmetler',
            'kişiselleştirilmiş_hizmet',
            'sanat_eserleri_var_mi',
            'antika_eşyalar_var_mi',
            'özel_mutfak',
            'şarap_mahzeni',
            'kütüphane_var_mi',
            'çalışma_alanı_var_mi',
            'özel_etkinlik_alanı',
            'bahçe_var_mi',
            'teras_var_mi',
            'şömine_var_mi',
            'jakuzi_var_mi',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'tema_konsepti' => 'nullable|string|max:200',
            'tasarim_stili' => 'nullable|string|max:200',
            'özel_hizmetler' => 'nullable|string|max:500',
            'kişiselleştirilmiş_hizmet' => 'nullable|boolean',
            'sanat_eserleri_var_mi' => 'nullable|boolean',
            'antika_eşyalar_var_mi' => 'nullable|boolean',
            'özel_mutfak' => 'nullable|string|max:200',
            'şarap_mahzeni' => 'nullable|boolean',
            'kütüphane_var_mi' => 'nullable|boolean',
            'çalışma_alanı_var_mi' => 'nullable|boolean',
            'özel_etkinlik_alanı' => 'nullable|boolean',
            'bahçe_var_mi' => 'nullable|boolean',
            'teras_var_mi' => 'nullable|boolean',
            'şömine_var_mi' => 'nullable|boolean',
            'jakuzi_var_mi' => 'nullable|boolean',
        ]);
    }
}