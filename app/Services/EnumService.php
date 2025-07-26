<?php

namespace App\Services;

use App\Enums\MulkKategorisi;
use App\Enums\MusteriKategorisi;
use App\Enums\ResimKategorisi;
use App\Enums\DokumanTipi;
use App\Enums\HatirlatmaTipi;
use App\Enums\NotKategorisi;

class EnumService
{
    /**
     * Tüm enum'ları döndür
     */
    public static function getAllEnums(): array
    {
        return [
            'mulk_kategorisi' => MulkKategorisi::toArray(),
            'musteri_kategorisi' => MusteriKategorisi::toArray(),
            'resim_kategorisi' => ResimKategorisi::toArray(),
            'dokuman_tipi' => DokumanTipi::toArray(),
            'hatirlatma_tipi' => HatirlatmaTipi::toArray(),
            'not_kategorisi' => NotKategorisi::toArray(),
        ];
    }

    /**
     * Belirli bir enum'ı döndür
     */
    public static function getEnum(string $enumName): array
    {
        return match ($enumName) {
            'mulk_kategorisi' => MulkKategorisi::toArray(),
            'musteri_kategorisi' => MusteriKategorisi::toArray(),
            'resim_kategorisi' => ResimKategorisi::toArray(),
            'dokuman_tipi' => DokumanTipi::toArray(),
            'hatirlatma_tipi' => HatirlatmaTipi::toArray(),
            'not_kategorisi' => NotKategorisi::toArray(),
            default => [],
        };
    }

    /**
     * Enum değerini label'a çevir
     */
    public static function getLabel(string $enumName, string $value): string
    {
        $enum = self::getEnumInstance($enumName, $value);
        return $enum?->label() ?? $value;
    }

    /**
     * Enum değerini description'a çevir
     */
    public static function getDescription(string $enumName, string $value): string
    {
        $enum = self::getEnumInstance($enumName, $value);
        return $enum?->description() ?? '';
    }

    /**
     * Enum değerini color'a çevir
     */
    public static function getColor(string $enumName, string $value): string
    {
        $enum = self::getEnumInstance($enumName, $value);
        return $enum?->color() ?? 'gray';
    }

    /**
     * Mülk kategorisine göre desteklenen resim kategorilerini döndür
     */
    public static function getSupportedImageCategories(string $mulkKategorisi): array
    {
        try {
            $enum = MulkKategorisi::from($mulkKategorisi);
            return array_map(fn($cat) => $cat->value, $enum->supportedImageCategories());
        } catch (\ValueError) {
            return [];
        }
    }

    /**
     * Mülk kategorisine göre desteklenen döküman tiplerini döndür
     */
    public static function getSupportedDocumentTypes(string $mulkKategorisi): array
    {
        try {
            $enum = MulkKategorisi::from($mulkKategorisi);
            return array_map(fn($type) => $type->value, $enum->supportedDocumentTypes());
        } catch (\ValueError) {
            return [];
        }
    }

    /**
     * Mülk kategorisinin galeri gerektirip gerektirmediğini kontrol et
     */
    public static function requiresGallery(string $mulkKategorisi): bool
    {
        try {
            $enum = MulkKategorisi::from($mulkKategorisi);
            return $enum->requiresGallery();
        } catch (\ValueError) {
            return false;
        }
    }

    /**
     * Müşteri kategorisinin aktif olup olmadığını kontrol et
     */
    public static function isActiveCustomerCategory(string $musteriKategorisi): bool
    {
        try {
            $enum = MusteriKategorisi::from($musteriKategorisi);
            return $enum->isActive();
        } catch (\ValueError) {
            return false;
        }
    }

    /**
     * Hatırlatma tipinin otomatik bildirim gerektirip gerektirmediğini kontrol et
     */
    public static function requiresAutoNotification(string $hatirlatmaTipi): bool
    {
        try {
            $enum = HatirlatmaTipi::from($hatirlatmaTipi);
            return $enum->autoNotification();
        } catch (\ValueError) {
            return false;
        }
    }

    /**
     * Hatırlatma tipi için varsayılan süreyi döndür
     */
    public static function getDefaultDuration(string $hatirlatmaTipi): int
    {
        try {
            $enum = HatirlatmaTipi::from($hatirlatmaTipi);
            return $enum->defaultDuration();
        } catch (\ValueError) {
            return 15;
        }
    }

    /**
     * Not kategorisi için varsayılan önceliği döndür
     */
    public static function getDefaultNotePriority(string $notKategorisi): int
    {
        try {
            $enum = NotKategorisi::from($notKategorisi);
            return $enum->defaultPriority();
        } catch (\ValueError) {
            return 5;
        }
    }

    /**
     * Select2 için uygun format döndür
     */
    public static function getForSelect(string $enumName): array
    {
        $enumData = self::getEnum($enumName);
        
        return array_map(function ($item) {
            return [
                'id' => $item['value'],
                'text' => $item['label'],
                'description' => $item['description'] ?? '',
                'color' => $item['color'] ?? 'gray',
            ];
        }, $enumData);
    }

    /**
     * Enum instance'ını döndür
     */
    private static function getEnumInstance(string $enumName, string $value)
    {
        try {
            return match ($enumName) {
                'mulk_kategorisi' => MulkKategorisi::from($value),
                'musteri_kategorisi' => MusteriKategorisi::from($value),
                'resim_kategorisi' => ResimKategorisi::from($value),
                'dokuman_tipi' => DokumanTipi::from($value),
                'hatirlatma_tipi' => HatirlatmaTipi::from($value),
                'not_kategorisi' => NotKategorisi::from($value),
                default => null,
            };
        } catch (\ValueError) {
            return null;
        }
    }

    /**
     * Enum'ın geçerli bir değer olup olmadığını kontrol et
     */
    public static function isValidValue(string $enumName, string $value): bool
    {
        return self::getEnumInstance($enumName, $value) !== null;
    }

    /**
     * Tüm enum değerlerini cache'le
     */
    public static function getCachedEnums(): array
    {
        return cache()->remember('all_enums', 3600, function () {
            return self::getAllEnums();
        });
    }
}