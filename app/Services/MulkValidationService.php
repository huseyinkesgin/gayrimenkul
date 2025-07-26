<?php

namespace App\Services;

use App\Models\Mulk\BaseMulk;
use App\Enums\MulkKategorisi;
use Illuminate\Validation\Rule;

class MulkValidationService
{
    /**
     * Mülk tipine göre validation kuralları döndür
     */
    public static function getRulesForType(string $mulkType): array
    {
        $baseRules = BaseMulk::getBaseValidationRules();
        $specificRules = self::getSpecificRulesForType($mulkType);
        
        return array_merge($baseRules, $specificRules);
    }

    /**
     * Mülk tipine özel validation kuralları
     */
    private static function getSpecificRulesForType(string $mulkType): array
    {
        return match ($mulkType) {
            'arsa' => self::getArsaRules(),
            'fabrika' => self::getFabrikaRules(),
            'depo' => self::getDepoRules(),
            'daire' => self::getDaireRules(),
            'villa' => self::getVillaRules(),
            'butik_otel' => self::getButikOtelRules(),
            default => []
        };
    }

    /**
     * Arsa validation kuralları
     */
    private static function getArsaRules(): array
    {
        return [
            'imar_durumu' => 'nullable|string|max:100',
            'kaks' => 'nullable|numeric|min:0|max:10',
            'gabari' => 'nullable|numeric|min:0|max:100',
            'ada_no' => 'nullable|string|max:50',
            'parsel_no' => 'nullable|string|max:50',
        ];
    }

    /**
     * Fabrika validation kuralları
     */
    private static function getFabrikaRules(): array
    {
        return [
            'kapali_alan' => 'nullable|numeric|min:0',
            'acik_alan' => 'nullable|numeric|min:0',
            'uretim_alani' => 'nullable|numeric|min:0',
            'ofis_alani' => 'nullable|numeric|min:0',
            'yukseklik' => 'nullable|numeric|min:0|max:50',
            'vinc_kapasitesi' => 'nullable|numeric|min:0',
            'elektrik_gucü' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Depo validation kuralları
     */
    private static function getDepoRules(): array
    {
        return [
            'kapali_alan' => 'nullable|numeric|min:0',
            'acik_alan' => 'nullable|numeric|min:0',
            'ofis_alani' => 'nullable|numeric|min:0',
            'ellecleme_alani' => 'nullable|numeric|min:0',
            'yukseklik' => 'nullable|numeric|min:0|max:30',
            'rampa_sayisi' => 'nullable|integer|min:0|max:20',
            'dock_sayisi' => 'nullable|integer|min:0|max:50',
        ];
    }

    /**
     * Daire validation kuralları
     */
    private static function getDaireRules(): array
    {
        return [
            'oda_sayisi' => 'nullable|integer|min:1|max:10',
            'salon_sayisi' => 'nullable|integer|min:1|max:5',
            'banyo_sayisi' => 'nullable|integer|min:1|max:5',
            'wc_sayisi' => 'nullable|integer|min:0|max:5',
            'balkon_sayisi' => 'nullable|integer|min:0|max:10',
            'asansor_var_mi' => 'nullable|boolean',
            'kat_no' => 'nullable|integer|min:-5|max:100',
            'bina_kat_sayisi' => 'nullable|integer|min:1|max:100',
            'bina_yasi' => 'nullable|integer|min:0|max:200',
            'isitma_tipi' => 'nullable|string|max:50',
        ];
    }

    /**
     * Villa validation kuralları
     */
    private static function getVillaRules(): array
    {
        return [
            'oda_sayisi' => 'nullable|integer|min:1|max:20',
            'salon_sayisi' => 'nullable|integer|min:1|max:10',
            'banyo_sayisi' => 'nullable|integer|min:1|max:10',
            'wc_sayisi' => 'nullable|integer|min:0|max:10',
            'balkon_sayisi' => 'nullable|integer|min:0|max:20',
            'bahce_alani' => 'nullable|numeric|min:0',
            'kat_sayisi' => 'nullable|integer|min:1|max:10',
            'havuz_var_mi' => 'nullable|boolean',
            'garaj_var_mi' => 'nullable|boolean',
            'arac_kapasitesi' => 'nullable|integer|min:0|max:20',
            'isitma_tipi' => 'nullable|string|max:50',
        ];
    }

    /**
     * Butik otel validation kuralları
     */
    private static function getButikOtelRules(): array
    {
        return [
            'oda_sayisi' => 'nullable|integer|min:1|max:500',
            'yatak_kapasitesi' => 'nullable|integer|min:1|max:1000',
            'kat_sayisi' => 'nullable|integer|min:1|max:50',
            'restoran_var_mi' => 'nullable|boolean',
            'spa_var_mi' => 'nullable|boolean',
            'havuz_var_mi' => 'nullable|boolean',
            'otopark_kapasitesi' => 'nullable|integer|min:0|max:500',
            'yildiz_sayisi' => 'nullable|integer|min:1|max:5',
        ];
    }

    /**
     * Mülk kategorisine göre geçerli alt tipleri döndür
     */
    public static function getValidSubTypesForCategory(MulkKategorisi $kategori): array
    {
        return $kategori->subCategories();
    }

    /**
     * Mülk tipinin kategoriye uygun olup olmadığını kontrol et
     */
    public static function isValidTypeForCategory(string $mulkType, MulkKategorisi $kategori): bool
    {
        $validTypes = array_keys($kategori->subCategories());
        return in_array($mulkType, $validTypes);
    }

    /**
     * Özellik validation kuralları
     */
    public static function getPropertyValidationRules(): array
    {
        return [
            'properties' => 'nullable|array',
            'properties.*.name' => 'required|string|max:100',
            'properties.*.value' => 'required',
            'properties.*.type' => 'required|in:sayi,metin,boolean,liste',
            'properties.*.unit' => 'nullable|string|max:20',
        ];
    }

    /**
     * Adres validation kuralları
     */
    public static function getAddressValidationRules(): array
    {
        return [
            'addresses' => 'nullable|array',
            'addresses.*.adres_adi' => 'required|string|max:100',
            'addresses.*.adres_detay' => 'required|string|max:500',
            'addresses.*.sehir_id' => 'required|exists:sehir,id',
            'addresses.*.ilce_id' => 'required|exists:ilce,id',
            'addresses.*.semt_id' => 'nullable|exists:semt,id',
            'addresses.*.mahalle_id' => 'nullable|exists:mahalle,id',
            'addresses.*.posta_kodu' => 'nullable|string|size:5',
            'addresses.*.varsayilan_mi' => 'boolean',
        ];
    }

    /**
     * Resim validation kuralları
     */
    public static function getImageValidationRules(): array
    {
        return [
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
        ];
    }

    /**
     * Döküman validation kuralları
     */
    public static function getDocumentValidationRules(): array
    {
        return [
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,doc,docx,dwg,dxf|max:10240', // 10MB
        ];
    }

    /**
     * Tüm validation kurallarını birleştir
     */
    public static function getAllRulesForType(string $mulkType): array
    {
        return array_merge(
            self::getRulesForType($mulkType),
            self::getPropertyValidationRules(),
            self::getAddressValidationRules(),
            self::getImageValidationRules(),
            self::getDocumentValidationRules()
        );
    }

    /**
     * Custom validation mesajları
     */
    public static function getValidationMessages(): array
    {
        return [
            'baslik.required' => 'Mülk başlığı zorunludur.',
            'baslik.max' => 'Mülk başlığı en fazla 255 karakter olabilir.',
            'fiyat.numeric' => 'Fiyat sayısal bir değer olmalıdır.',
            'fiyat.min' => 'Fiyat 0\'dan küçük olamaz.',
            'metrekare.numeric' => 'Metrekare sayısal bir değer olmalıdır.',
            'metrekare.min' => 'Metrekare 0\'dan küçük olamaz.',
            'durum.required' => 'Mülk durumu zorunludur.',
            'durum.in' => 'Geçersiz mülk durumu.',
            'oda_sayisi.integer' => 'Oda sayısı tam sayı olmalıdır.',
            'oda_sayisi.min' => 'Oda sayısı en az 1 olmalıdır.',
            'banyo_sayisi.integer' => 'Banyo sayısı tam sayı olmalıdır.',
            'yukseklik.numeric' => 'Yükseklik sayısal bir değer olmalıdır.',
            'rampa_sayisi.integer' => 'Rampa sayısı tam sayı olmalıdır.',
            'addresses.*.sehir_id.required' => 'Şehir seçimi zorunludur.',
            'addresses.*.ilce_id.required' => 'İlçe seçimi zorunludur.',
            'images.*.image' => 'Yüklenen dosya bir resim olmalıdır.',
            'images.*.max' => 'Resim boyutu en fazla 5MB olabilir.',
            'documents.*.file' => 'Geçerli bir dosya yükleyin.',
            'documents.*.max' => 'Döküman boyutu en fazla 10MB olabilir.',
        ];
    }
}