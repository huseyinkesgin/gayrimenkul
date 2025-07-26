<?php

namespace App\Enums;

enum MusteriTipi: string
{
    case BIREYSEL = 'bireysel';
    case KURUMSAL = 'kurumsal';

    /**
     * Tip etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::BIREYSEL => 'Bireysel',
            self::KURUMSAL => 'Kurumsal',
        };
    }

    /**
     * Tip açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::BIREYSEL => 'Bireysel müşteri (gerçek kişi)',
            self::KURUMSAL => 'Kurumsal müşteri (tüzel kişi)',
        };
    }

    /**
     * Tip rengini döndür
     */
    public function color(): string
    {
        return match ($this) {
            self::BIREYSEL => 'blue',
            self::KURUMSAL => 'green',
        };
    }

    /**
     * Tip ikonunu döndür
     */
    public function icon(): string
    {
        return match ($this) {
            self::BIREYSEL => 'heroicon-o-user',
            self::KURUMSAL => 'heroicon-o-building-office',
        };
    }

    /**
     * Tüm tipleri array olarak döndür
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
}