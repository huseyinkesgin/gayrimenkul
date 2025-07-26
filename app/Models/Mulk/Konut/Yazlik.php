<?php

namespace App\Models\Mulk\Konut;

class Yazlik extends Konut
{
    public function getMulkType(): string
    {
        return 'yazlik';
    }

    public function getValidProperties(): array
    {
        return array_merge(parent::getValidProperties(), [
            'deniz_mesafesi',
            'plaj_mesafesi',
            'merkez_mesafesi',
            'mevsimlik_mi',
            'kış_kullanımı_uygun_mu',
            'komşu_yazlık_yogunlugu',
            'doğal_çevre_durumu',
            'yürüyüş_yolu_mesafesi',
            'bisiklet_yolu_var_mi',
            'kamp_alani_mesafesi',
        ]);
    }

    public function getSpecificValidationRules(): array
    {
        return array_merge(parent::getSpecificValidationRules(), [
            'deniz_mesafesi' => 'nullable|numeric|min:0',
            'plaj_mesafesi' => 'nullable|numeric|min:0',
            'merkez_mesafesi' => 'nullable|numeric|min:0',
            'mevsimlik_mi' => 'nullable|boolean',
            'kış_kullanımı_uygun_mu' => 'nullable|boolean',
            'komşu_yazlık_yogunlugu' => 'nullable|in:düşük,orta,yüksek',
            'doğal_çevre_durumu' => 'nullable|in:mükemmel,iyi,orta,kötü',
            'yürüyüş_yolu_mesafesi' => 'nullable|numeric|min:0',
            'bisiklet_yolu_var_mi' => 'nullable|boolean',
            'kamp_alani_mesafesi' => 'nullable|numeric|min:0',
        ]);
    }
}