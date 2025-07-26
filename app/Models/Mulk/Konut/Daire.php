<?php

namespace App\Models\Mulk\Konut;

class Daire extends Konut
{
    public function getMulkType(): string
    {
        return 'daire';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'site_adi',
            'blok_adi',
            'daire_no',
            'aidat_miktari',
            'aidat_dahil_mi',
            'site_içi_konum',
            'asansor_kati_mi',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'site_adi' => 'nullable|string|max:200',
            'blok_adi' => 'nullable|string|max:100',
            'daire_no' => 'nullable|string|max:20',
            'aidat_miktari' => 'nullable|numeric|min:0',
            'aidat_dahil_mi' => 'nullable|boolean',
            'site_içi_konum' => 'nullable|string|max:200',
            'asansor_kati_mi' => 'nullable|boolean',
        ]);
    }
}