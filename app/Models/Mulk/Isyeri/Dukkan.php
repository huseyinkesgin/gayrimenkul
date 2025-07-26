<?php

namespace App\Models\Mulk\Isyeri;

class Dukkan extends Isyeri
{
    public function getMulkType(): string
    {
        return 'dukkan';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'vitrin_var_mi',
            'depo_alani',
            'wc_var_mi',
            'su_var_mi',
            'dogalgaz_var_mi',
            'internet_var_mi',
            'telefon_var_mi',
            'komsu_isyerleri',
            'cadde_tipi',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'vitrin_var_mi' => 'nullable|boolean',
            'depo_alani' => 'nullable|numeric|min:0',
            'wc_var_mi' => 'nullable|boolean',
            'su_var_mi' => 'nullable|boolean',
            'dogalgaz_var_mi' => 'nullable|boolean',
            'internet_var_mi' => 'nullable|boolean',
            'telefon_var_mi' => 'nullable|boolean',
            'komsu_isyerleri' => 'nullable|string|max:500',
            'cadde_tipi' => 'nullable|in:ana_cadde,yan_sokak,pasaj,avm',
        ]);
    }
}