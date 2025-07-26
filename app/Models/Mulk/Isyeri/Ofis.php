<?php

namespace App\Models\Mulk\Isyeri;

class Ofis extends Isyeri
{
    public function getMulkType(): string
    {
        return 'ofis';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'oda_sayisi',
            'toplanti_odasi_sayisi',
            'mutfak_var_mi',
            'balkon_var_mi',
            'manzara_durumu',
            'mobilyali_mi',
            'internet_hizi',
            'klima_var_mi',
            'ses_izolasyonu',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'oda_sayisi' => 'nullable|integer|min:1|max:50',
            'toplanti_odasi_sayisi' => 'nullable|integer|min:0|max:20',
            'mutfak_var_mi' => 'nullable|boolean',
            'balkon_var_mi' => 'nullable|boolean',
            'manzara_durumu' => 'nullable|in:deniz,sehir,orman,yok',
            'mobilyali_mi' => 'nullable|boolean',
            'internet_hizi' => 'nullable|numeric|min:0',
            'klima_var_mi' => 'nullable|boolean',
            'ses_izolasyonu' => 'nullable|boolean',
        ]);
    }
}