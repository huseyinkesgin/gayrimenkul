<?php

namespace App\Models\Mulk\TuristikTesis;

class Motel extends TuristikTesis
{
    public function getMulkType(): string
    {
        return 'motel';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'karayolu_mesafesi',
            'benzin_istasyonu_mesafesi',
            'kamyon_park_yeri',
            'büyük_araç_park_yeri',
            '24_saat_resepsiyon',
            'self_check_in',
            'kafe_restoran_24_saat',
            'araç_yıkama_hizmeti',
            'lastik_tamiri',
            'mekanik_hizmeti',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'karayolu_mesafesi' => 'nullable|numeric|min:0',
            'benzin_istasyonu_mesafesi' => 'nullable|numeric|min:0',
            'kamyon_park_yeri' => 'nullable|integer|min:0',
            'büyük_araç_park_yeri' => 'nullable|integer|min:0',
            '24_saat_resepsiyon' => 'nullable|boolean',
            'self_check_in' => 'nullable|boolean',
            'kafe_restoran_24_saat' => 'nullable|boolean',
            'araç_yıkama_hizmeti' => 'nullable|boolean',
            'lastik_tamiri' => 'nullable|boolean',
            'mekanik_hizmeti' => 'nullable|boolean',
        ]);
    }
}