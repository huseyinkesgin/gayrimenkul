<?php

namespace App\Enums;

enum NotKategorisi: string
{
    case GENEL = 'genel';
    case GORUSME = 'gorusme';
    case TAKIP = 'takip';
    case UYARI = 'uyari';
    case BILGI = 'bilgi';
    case KARAR = 'karar';

    /**
     * Enum etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::GENEL => 'Genel',
            self::GORUSME => 'Görüşme',
            self::TAKIP => 'Takip',
            self::UYARI => 'Uyarı',
            self::BILGI => 'Bilgi',
            self::KARAR => 'Karar',
        };
    }

    /**
     * Enum açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::GENEL => 'Genel amaçlı notlar',
            self::GORUSME => 'Görüşme kayıtları',
            self::TAKIP => 'Takip gerektiren konular',
            self::UYARI => 'Dikkat edilmesi gereken durumlar',
            self::BILGI => 'Bilgilendirme notları',
            self::KARAR => 'Alınan kararlar',
        };
    }

    /**
     * Kategori rengini döndür
     */
    public function color(): string
    {
        return match ($this) {
            self::GENEL => 'gray',
            self::GORUSME => 'blue',
            self::TAKIP => 'yellow',
            self::UYARI => 'red',
            self::BILGI => 'green',
            self::KARAR => 'purple',
        };
    }

    /**
     * Varsayılan öncelik seviyesini döndür
     */
    public function defaultPriority(): int
    {
        return match ($this) {
            self::UYARI => 8,
            self::KARAR => 7,
            self::TAKIP => 6,
            self::GORUSME => 5,
            self::BILGI => 4,
            self::GENEL => 3,
        };
    }

    /**
     * Tüm enum değerlerini array olarak döndür
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'color' => $case->color(),
            'defaultPriority' => $case->defaultPriority(),
        ], self::cases());
    }
}