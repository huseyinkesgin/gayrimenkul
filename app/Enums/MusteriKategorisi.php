<?php

namespace App\Enums;

enum MusteriKategorisi: string
{
    case SATICI = 'satici';
    case ALICI = 'alici';
    case MAL_SAHIBI = 'mal_sahibi';
    case PARTNER = 'partner';
    case TEDARIKCI = 'tedarikci';

    /**
     * Kategori etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::SATICI => 'Satıcı',
            self::ALICI => 'Alıcı',
            self::MAL_SAHIBI => 'Mal Sahibi',
            self::PARTNER => 'Partner',
            self::TEDARIKCI => 'Tedarikçi',
        };
    }

    /**
     * Kategori açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::SATICI => 'Mülk satışı yapan müşteri',
            self::ALICI => 'Mülk satın almak isteyen müşteri',
            self::MAL_SAHIBI => 'Mülkün sahibi olan müşteri',
            self::PARTNER => 'İş ortağı müşteri',
            self::TEDARIKCI => 'Hizmet/ürün tedarik eden müşteri',
        };
    }

    /**
     * Kategori rengini döndür
     */
    public function color(): string
    {
        return match ($this) {
            self::SATICI => 'blue',
            self::ALICI => 'green',
            self::MAL_SAHIBI => 'purple',
            self::PARTNER => 'orange',
            self::TEDARIKCI => 'gray',
        };
    }

    /**
     * Kategori ikonunu döndür
     */
    public function icon(): string
    {
        return match ($this) {
            self::SATICI => 'heroicon-o-arrow-up-circle',
            self::ALICI => 'heroicon-o-arrow-down-circle',
            self::MAL_SAHIBI => 'heroicon-o-home',
            self::PARTNER => 'heroicon-o-handshake',
            self::TEDARIKCI => 'heroicon-o-truck',
        };
    }

    /**
     * Tüm kategorileri array olarak döndür
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'color' => $case->color(),
            'icon' => $case->icon(),
        ], self::cases());
    }

    /**
     * Değere göre enum döndür
     */
    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Etikete göre enum döndür
     */
    public static function fromLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->label() === $label) {
                return $case;
            }
        }
        return null;
    }

    /**
     * Satış ile ilgili kategoriler
     */
    public static function salesRelated(): array
    {
        return [self::SATICI, self::ALICI, self::MAL_SAHIBI];
    }

    /**
     * İş ile ilgili kategoriler
     */
    public static function businessRelated(): array
    {
        return [self::PARTNER, self::TEDARIKCI];
    }

    /**
     * Bu kategorinin satış ile ilgili olup olmadığını kontrol et
     */
    public function isSalesRelated(): bool
    {
        return in_array($this, self::salesRelated());
    }

    /**
     * Bu kategorinin iş ile ilgili olup olmadığını kontrol et
     */
    public function isBusinessRelated(): bool
    {
        return in_array($this, self::businessRelated());
    }
}