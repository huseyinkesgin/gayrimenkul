<?php

namespace App\Enums;

enum IliskiDurumu: string
{
    case AKTIF = 'aktif';
    case PASIF = 'pasif';
    case TAMAMLANDI = 'tamamlandi';
    case BEKLEMEDE = 'beklemede';
    case IPTAL_EDILDI = 'iptal_edildi';
    case DONDURULDU = 'donduruldu';

    /**
     * İlişki durumu etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::AKTIF => 'Aktif',
            self::PASIF => 'Pasif',
            self::TAMAMLANDI => 'Tamamlandı',
            self::BEKLEMEDE => 'Beklemede',
            self::IPTAL_EDILDI => 'İptal Edildi',
            self::DONDURULDU => 'Donduruldu',
        };
    }

    /**
     * İlişki durumu açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::AKTIF => 'İlişki aktif olarak devam ediyor',
            self::PASIF => 'İlişki pasif durumda',
            self::TAMAMLANDI => 'İlişki başarıyla tamamlandı',
            self::BEKLEMEDE => 'İlişki beklemede, müşteri kararını vermeyi bekliyor',
            self::IPTAL_EDILDI => 'İlişki iptal edildi',
            self::DONDURULDU => 'İlişki geçici olarak donduruldu',
        ];
    }

    /**
     * İlişki durumu rengini döndür
     */
    public function color(): string
    {
        return match ($this) {
            self::AKTIF => 'green',
            self::PASIF => 'gray',
            self::TAMAMLANDI => 'blue',
            self::BEKLEMEDE => 'yellow',
            self::IPTAL_EDILDI => 'red',
            self::DONDURULDU => 'orange',
        };
    }

    /**
     * İlişki durumu ikonunu döndür
     */
    public function icon(): string
    {
        return match ($this) {
            self::AKTIF => 'heroicon-o-play-circle',
            self::PASIF => 'heroicon-o-pause-circle',
            self::TAMAMLANDI => 'heroicon-o-check-circle',
            self::BEKLEMEDE => 'heroicon-o-clock',
            self::IPTAL_EDILDI => 'heroicon-o-x-circle',
            self::DONDURULDU => 'heroicon-o-stop-circle',
        ];
    }

    /**
     * Aktif durumlar
     */
    public static function activeStates(): array
    {
        return [
            self::AKTIF,
            self::BEKLEMEDE,
        ];
    }

    /**
     * Tamamlanmış durumlar
     */
    public static function completedStates(): array
    {
        return [
            self::TAMAMLANDI,
        ];
    }

    /**
     * İnaktif durumlar
     */
    public static function inactiveStates(): array
    {
        return [
            self::PASIF,
            self::IPTAL_EDILDI,
            self::DONDURULDU,
        ];
    }

    /**
     * Takip gerektiren durumlar
     */
    public static function requiresFollowUp(): array
    {
        return [
            self::AKTIF,
            self::BEKLEMEDE,
            self::DONDURULDU,
        ];
    }

    /**
     * Bu durum aktif mi?
     */
    public function isActive(): bool
    {
        return in_array($this, self::activeStates());
    }

    /**
     * Bu durum tamamlanmış mı?
     */
    public function isCompleted(): bool
    {
        return in_array($this, self::completedStates());
    }

    /**
     * Bu durum inaktif mi?
     */
    public function isInactive(): bool
    {
        return in_array($this, self::inactiveStates());
    }

    /**
     * Bu durum takip gerektirir mi?
     */
    public function requiresFollowUp(): bool
    {
        return in_array($this, self::requiresFollowUp());
    }

    /**
     * Sonraki olası durumlar
     */
    public function nextPossibleStates(): array
    {
        return match ($this) {
            self::AKTIF => [
                self::PASIF,
                self::TAMAMLANDI,
                self::BEKLEMEDE,
                self::IPTAL_EDILDI,
                self::DONDURULDU,
            ],
            self::PASIF => [
                self::AKTIF,
                self::IPTAL_EDILDI,
            ],
            self::BEKLEMEDE => [
                self::AKTIF,
                self::TAMAMLANDI,
                self::IPTAL_EDILDI,
            ],
            self::DONDURULDU => [
                self::AKTIF,
                self::IPTAL_EDILDI,
            ],
            self::TAMAMLANDI => [],
            self::IPTAL_EDILDI => [
                self::AKTIF,
            ],
        };
    }

    /**
     * Durum değişikliği geçerli mi?
     */
    public function canChangeTo(self $newState): bool
    {
        return in_array($newState, $this->nextPossibleStates());
    }

    /**
     * Tüm durumları array olarak döndür
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'color' => $case->color(),
            'icon' => $case->icon(),
            'is_active' => $case->isActive(),
            'is_completed' => $case->isCompleted(),
            'is_inactive' => $case->isInactive(),
            'requires_follow_up' => $case->requiresFollowUp(),
            'next_possible_states' => array_map(fn($state) => $state->value, $case->nextPossibleStates()),
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