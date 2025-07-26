<?php

namespace App\Enums;

enum IliskiTipi: string
{
    case ILGILENIYOR = 'ilgileniyor';
    case TEKLIF_VERDI = 'teklif_verdi';
    case GORUSTU = 'gorustu';
    case SATIN_ALDI = 'satin_aldi';
    case KIRAYA_VERDI = 'kiraya_verdi';
    case KIRAYA_ALDI = 'kiraya_aldi';
    case DEGERLENDIRIYOR = 'degerlendiriyor';
    case MUZAKERE_EDIYOR = 'muzakere_ediyor';
    case SOZLESME_IMZALADI = 'sozlesme_imzaladi';
    case IPTAL_ETTI = 'iptal_etti';

    /**
     * İlişki tipi etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::ILGILENIYOR => 'İlgileniyor',
            self::TEKLIF_VERDI => 'Teklif Verdi',
            self::GORUSTU => 'Görüştü',
            self::SATIN_ALDI => 'Satın Aldı',
            self::KIRAYA_VERDI => 'Kiraya Verdi',
            self::KIRAYA_ALDI => 'Kiraya Aldı',
            self::DEGERLENDIRIYOR => 'Değerlendiriyor',
            self::MUZAKERE_EDIYOR => 'Müzakere Ediyor',
            self::SOZLESME_IMZALADI => 'Sözleşme İmzaladı',
            self::IPTAL_ETTI => 'İptal Etti',
        };
    }

    /**
     * İlişki tipi açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::ILGILENIYOR => 'Müşteri mülkle ilgileniyor',
            self::TEKLIF_VERDI => 'Müşteri teklif verdi',
            self::GORUSTU => 'Müşteri ile görüşme yapıldı',
            self::SATIN_ALDI => 'Müşteri mülkü satın aldı',
            self::KIRAYA_VERDI => 'Müşteri mülkü kiraya verdi',
            self::KIRAYA_ALDI => 'Müşteri mülkü kiraya aldı',
            self::DEGERLENDIRIYOR => 'Müşteri mülkü değerlendiriyor',
            self::MUZAKERE_EDIYOR => 'Müşteri fiyat müzakeresi yapıyor',
            self::SOZLESME_IMZALADI => 'Müşteri sözleşme imzaladı',
            self::IPTAL_ETTI => 'Müşteri işlemi iptal etti',
        ];
    }

    /**
     * İlişki tipi rengini döndür
     */
    public function color(): string
    {
        return match ($this) {
            self::ILGILENIYOR => 'blue',
            self::TEKLIF_VERDI => 'orange',
            self::GORUSTU => 'purple',
            self::SATIN_ALDI => 'green',
            self::KIRAYA_VERDI => 'emerald',
            self::KIRAYA_ALDI => 'teal',
            self::DEGERLENDIRIYOR => 'yellow',
            self::MUZAKERE_EDIYOR => 'amber',
            self::SOZLESME_IMZALADI => 'lime',
            self::IPTAL_ETTI => 'red',
        ];
    }

    /**
     * İlişki tipi ikonunu döndür
     */
    public function icon(): string
    {
        return match ($this) {
            self::ILGILENIYOR => 'heroicon-o-eye',
            self::TEKLIF_VERDI => 'heroicon-o-currency-dollar',
            self::GORUSTU => 'heroicon-o-chat-bubble-left-right',
            self::SATIN_ALDI => 'heroicon-o-check-circle',
            self::KIRAYA_VERDI => 'heroicon-o-arrow-up-circle',
            self::KIRAYA_ALDI => 'heroicon-o-arrow-down-circle',
            self::DEGERLENDIRIYOR => 'heroicon-o-magnifying-glass',
            self::MUZAKERE_EDIYOR => 'heroicon-o-scale',
            self::SOZLESME_IMZALADI => 'heroicon-o-document-check',
            self::IPTAL_ETTI => 'heroicon-o-x-circle',
        ];
    }

    /**
     * İlişki tipi öncelik puanını döndür
     */
    public function priorityScore(): int
    {
        return match ($this) {
            self::SOZLESME_IMZALADI => 100,
            self::SATIN_ALDI, self::KIRAYA_ALDI => 90,
            self::MUZAKERE_EDIYOR => 80,
            self::TEKLIF_VERDI => 70,
            self::DEGERLENDIRIYOR => 60,
            self::GORUSTU => 50,
            self::KIRAYA_VERDI => 40,
            self::ILGILENIYOR => 30,
            self::IPTAL_ETTI => 10,
        };
    }

    /**
     * Aktif ilişki tipleri
     */
    public static function activeTypes(): array
    {
        return [
            self::ILGILENIYOR,
            self::TEKLIF_VERDI,
            self::GORUSTU,
            self::DEGERLENDIRIYOR,
            self::MUZAKERE_EDIYOR,
        ];
    }

    /**
     * Tamamlanmış ilişki tipleri
     */
    public static function completedTypes(): array
    {
        return [
            self::SATIN_ALDI,
            self::KIRAYA_VERDI,
            self::KIRAYA_ALDI,
            self::SOZLESME_IMZALADI,
        ];
    }

    /**
     * İptal edilmiş ilişki tipleri
     */
    public static function cancelledTypes(): array
    {
        return [
            self::IPTAL_ETTI,
        ];
    }

    /**
     * Satış ile ilgili tipler
     */
    public static function salesTypes(): array
    {
        return [
            self::ILGILENIYOR,
            self::TEKLIF_VERDI,
            self::GORUSTU,
            self::SATIN_ALDI,
            self::DEGERLENDIRIYOR,
            self::MUZAKERE_EDIYOR,
            self::SOZLESME_IMZALADI,
        ];
    }

    /**
     * Kiralama ile ilgili tipler
     */
    public static function rentalTypes(): array
    {
        return [
            self::KIRAYA_VERDI,
            self::KIRAYA_ALDI,
        ];
    }

    /**
     * Bu tip aktif mi?
     */
    public function isActive(): bool
    {
        return in_array($this, self::activeTypes());
    }

    /**
     * Bu tip tamamlanmış mı?
     */
    public function isCompleted(): bool
    {
        return in_array($this, self::completedTypes());
    }

    /**
     * Bu tip iptal edilmiş mi?
     */
    public function isCancelled(): bool
    {
        return in_array($this, self::cancelledTypes());
    }

    /**
     * Bu tip satış ile ilgili mi?
     */
    public function isSales(): bool
    {
        return in_array($this, self::salesTypes());
    }

    /**
     * Bu tip kiralama ile ilgili mi?
     */
    public function isRental(): bool
    {
        return in_array($this, self::rentalTypes());
    }

    /**
     * Takip gerektiren tipler
     */
    public static function requiresFollowUp(): array
    {
        return [
            self::ILGILENIYOR,
            self::TEKLIF_VERDI,
            self::GORUSTU,
            self::DEGERLENDIRIYOR,
            self::MUZAKERE_EDIYOR,
        ];
    }

    /**
     * Bu tip takip gerektirir mi?
     */
    public function requiresFollowUp(): bool
    {
        return in_array($this, self::requiresFollowUp());
    }

    /**
     * Sonraki olası adımlar
     */
    public function nextPossibleSteps(): array
    {
        return match ($this) {
            self::ILGILENIYOR => [
                self::GORUSTU,
                self::DEGERLENDIRIYOR,
                self::IPTAL_ETTI,
            ],
            self::GORUSTU => [
                self::DEGERLENDIRIYOR,
                self::TEKLIF_VERDI,
                self::IPTAL_ETTI,
            ],
            self::DEGERLENDIRIYOR => [
                self::TEKLIF_VERDI,
                self::MUZAKERE_EDIYOR,
                self::IPTAL_ETTI,
            ],
            self::TEKLIF_VERDI => [
                self::MUZAKERE_EDIYOR,
                self::SATIN_ALDI,
                self::KIRAYA_ALDI,
                self::IPTAL_ETTI,
            ],
            self::MUZAKERE_EDIYOR => [
                self::SOZLESME_IMZALADI,
                self::SATIN_ALDI,
                self::KIRAYA_ALDI,
                self::IPTAL_ETTI,
            ],
            self::SOZLESME_IMZALADI => [
                self::SATIN_ALDI,
                self::KIRAYA_ALDI,
            ],
            default => [],
        };
    }

    /**
     * Tüm ilişki tiplerini array olarak döndür
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'color' => $case->color(),
            'icon' => $case->icon(),
            'priority_score' => $case->priorityScore(),
            'is_active' => $case->isActive(),
            'is_completed' => $case->isCompleted(),
            'is_cancelled' => $case->isCancelled(),
            'is_sales' => $case->isSales(),
            'is_rental' => $case->isRental(),
            'requires_follow_up' => $case->requiresFollowUp(),
            'next_possible_steps' => array_map(fn($step) => $step->value, $case->nextPossibleSteps()),
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