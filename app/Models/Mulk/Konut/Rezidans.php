<?php

namespace App\Models\Mulk\Konut;

class Rezidans extends Konut
{
    public function getMulkType(): string
    {
        return 'rezidans';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'rezidans_adi',
            'konsiyerj_hizmeti',
            'vale_hizmeti',
            'temizlik_hizmeti',
            'camasir_hizmeti',
            'yemek_servisi',
            'spa_hizmeti',
            'fitness_merkezi',
            'is_merkezi',
            'toplanti_salonu',
            'etkinlik_alani',
            'misafir_dairesi',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'rezidans_adi' => 'nullable|string|max:200',
            'konsiyerj_hizmeti' => 'nullable|boolean',
            'vale_hizmeti' => 'nullable|boolean',
            'temizlik_hizmeti' => 'nullable|boolean',
            'camasir_hizmeti' => 'nullable|boolean',
            'yemek_servisi' => 'nullable|boolean',
            'spa_hizmeti' => 'nullable|boolean',
            'fitness_merkezi' => 'nullable|boolean',
            'is_merkezi' => 'nullable|boolean',
            'toplanti_salonu' => 'nullable|boolean',
            'etkinlik_alani' => 'nullable|boolean',
            'misafir_dairesi' => 'nullable|boolean',
        ]);
    }
}