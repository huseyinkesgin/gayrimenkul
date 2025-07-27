<?php

namespace App\Enums;

enum HizmetSonucu: string
{
    case BASARILI = 'basarili';
    case KISMEN_BASARILI = 'kismen_basarili';
    case BASARISIZ = 'basarisiz';
    case ERTELENDI = 'ertelendi';
    case IPTAL_EDILDI = 'iptal_edildi';
    case TAKIP_GEREKLI = 'takip_gerekli';
    case TAMAMLANDI = 'tamamlandi';

    /**
     * Sonuç etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::BASARILI => 'Başarılı',
            self::KISMEN_BASARILI => 'Kısmen Başarılı',
            self::BASARISIZ => 'Başarısız',
            self::ERTELENDI => 'Ertelendi',
            self::IPTAL_EDILDI => 'İptal Edildi',
            self::TAKIP_GEREKLI => 'Takip Gerekli',
            self::TAMAMLANDI => 'Tamamlandı',
        };
    }

    /**
     * Sonuç açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::BASARILI => 'Hizmet başarıyla tamamlandı',
            self::KISMEN_BASARILI => 'Hizmet kısmen başarılı oldu',
            self::BASARISIZ => 'Hizmet başarısız oldu',
            self::ERTELENDI => 'Hizmet ertelendi',
            self::IPTAL_EDILDI => 'Hizmet iptal edildi',
            self::TAKIP_GEREKLI => 'Takip gerekli',
            self::TAMAMLANDI => 'Hizmet tamamlandı',
        };
    }

    /**
     * Sonuç rengini döndür
     */
    public function color(): string
    {
        return match ($this) {
            self::BASARILI => 'green',
            self::KISMEN_BASARILI => 'yellow',
            self::BASARISIZ => 'red',
            self::ERTELENDI => 'orange',
            self::IPTAL_EDILDI => 'gray',
            self::TAKIP_GEREKLI => 'blue',
            self::TAMAMLANDI => 'emerald',
        };
    }

    /**
     * Sonuç ikonunu döndür
     */
    public function icon(): string
    {
        return match ($this) {
            self::BASARILI => 'heroicon-o-check-circle',
            self::KISMEN_BASARILI => 'heroicon-o-exclamation-triangle',
            self::BASARISIZ => 'heroicon-o-x-circle',
            self::ERTELENDI => 'heroicon-o-clock',
            self::IPTAL_EDILDI => 'heroicon-o-no-symbol',
            self::TAKIP_GEREKLI => 'heroicon-o-arrow-path',
            self::TAMAMLANDI => 'heroicon-o-check-badge',
        };
    }

    /**
     * Olumlu sonuçlar
     */
    public static function positiveResults(): array
    {
        return [
            self::BASARILI,
            self::KISMEN_BASARILI,
            self::TAMAMLANDI,
        ];
    }

    /**
     * Olumsuz sonuçlar
     */
    public static function negativeResults(): array
    {
        return [
            self::BASARISIZ,
            self::IPTAL_EDILDI,
        ];
    }

    /**
     * Bekleyen sonuçlar
     */
    public static function pendingResults(): array
    {
        return [
            self::ERTELENDI,
            self::TAKIP_GEREKLI,
        ];
    }

    /**
     * Bu sonuç olumlu mu?
     */
    public function isPositive(): bool
    {
        return in_array($this, self::positiveResults());
    }

    /**
     * Bu sonuç olumsuz mu?
     */
    public function isNegative(): bool
    {
        return in_array($this, self::negativeResults());
    }

    /**
     * Bu sonuç beklemede mi?
     */
    public function isPending(): bool
    {
        return in_array($this, self::pendingResults());
    }

    /**
     * Takip gerektiren sonuçlar
     */
    public static function requiresFollowUp(): array
    {
        return [
            self::KISMEN_BASARILI,
            self::ERTELENDI,
            self::TAKIP_GEREKLI,
        ];
    }

    /**
     * Bu sonuç takip gerektirir mi?
     */
    public function needsFollowUp(): bool
    {
        return in_array($this, self::requiresFollowUp());
    }

    /**
     * Tüm sonuçları array olarak döndür
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'color' => $case->color(),
            'icon' => $case->icon(),
            'is_positive' => $case->isPositive(),
            'is_negative' => $case->isNegative(),
            'is_pending' => $case->isPending(),
            'requires_follow_up' => $case->needsFollowUp(),
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