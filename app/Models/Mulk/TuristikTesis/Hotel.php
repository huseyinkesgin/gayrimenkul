<?php

namespace App\Models\Mulk\TuristikTesis;

class Hotel extends TuristikTesis
{
    public function getMulkType(): string
    {
        return 'hotel';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'otel_zinciri',
            'franchise_mi',
            'all_inclusive_mi',
            'konsiyerj_hizmeti',
            'room_service_24_saat',
            'banket_salonu',
            'düğün_salonu',
            'iş_merkezi',
            'çamaşırhane',
            'kuru_temizleme',
            'havaalanı_transferi',
            'tur_organizasyonu',
            'araç_kiralama',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'otel_zinciri' => 'nullable|string|max:200',
            'franchise_mi' => 'nullable|boolean',
            'all_inclusive_mi' => 'nullable|boolean',
            'konsiyerj_hizmeti' => 'nullable|boolean',
            'room_service_24_saat' => 'nullable|boolean',
            'banket_salonu' => 'nullable|boolean',
            'düğün_salonu' => 'nullable|boolean',
            'iş_merkezi' => 'nullable|boolean',
            'çamaşırhane' => 'nullable|boolean',
            'kuru_temizleme' => 'nullable|boolean',
            'havaalanı_transferi' => 'nullable|boolean',
            'tur_organizasyonu' => 'nullable|boolean',
            'araç_kiralama' => 'nullable|boolean',
        ]);
    }
}