<?php

namespace App\Models\Mulk\Konut;

class Villa extends Konut
{
    public function getMulkType(): string
    {
        return 'villa';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'bahce_alani',
            'havuz_alani',
            'garaj_kapasitesi',
            'kameriye_var_mi',
            'barbekü_alani_var_mi',
            'çardak_var_mi',
            'sera_var_mi',
            'meyve_agaclari_var_mi',
            'güvenlik_sistemi_tipi',
            'çevre_duvarı_var_mi',
            'elektrikli_kapı_var_mi',
            'jenerator_var_mi',
            'su_deposu_var_mi',
            'arazi_eğimi',
            'deniz_mesafesi',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'bahce_alani' => 'nullable|numeric|min:0',
            'havuz_alani' => 'nullable|numeric|min:0',
            'garaj_kapasitesi' => 'nullable|integer|min:0|max:20',
            'kameriye_var_mi' => 'nullable|boolean',
            'barbekü_alani_var_mi' => 'nullable|boolean',
            'çardak_var_mi' => 'nullable|boolean',
            'sera_var_mi' => 'nullable|boolean',
            'meyve_agaclari_var_mi' => 'nullable|boolean',
            'güvenlik_sistemi_tipi' => 'nullable|string|max:100',
            'çevre_duvarı_var_mi' => 'nullable|boolean',
            'elektrikli_kapı_var_mi' => 'nullable|boolean',
            'jenerator_var_mi' => 'nullable|boolean',
            'su_deposu_var_mi' => 'nullable|boolean',
            'arazi_eğimi' => 'nullable|in:düz,hafif_eğimli,eğimli,dik',
            'deniz_mesafesi' => 'nullable|numeric|min:0',
        ]);
    }
}