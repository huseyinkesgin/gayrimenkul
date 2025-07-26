<?php

namespace App\Enums;

enum DegerlendirmeTipi: string
{
    case OLUMLU = 'olumlu';
    case OLUMSUZ = 'olumsuz';
    case NOTR = 'notr';

    /**
     * Değerlendirme tipi etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::OLUMLU => 'Olumlu',
            self::OLUMSUZ => 'Olumsuz',
            self::NOTR => 'Nötr',
        };
    }

    /**
     * Değerlendirme tipi açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::OLUMLU => 'Müşteri memnun, hizmet başarılı',
            self::OLUMSUZ => 'Müşteri memnun değil, hizmet başarısız',
            self::NOTR => 'Müşteri tepkisi nötr, orta seviye',
        };
    }

    /**
     * Değerlendirme tipi rengini döndür
     */
    public function color(): string
    {
        return match ($this) {
            self::OLUMLU => 'green',
            self::OLUMSUZ => 'red',
            self::NOTR => 'yellow',
        };
    }

    /**
     * Değerlendirme tipi ikonunu döndür
     */
    public function icon(): string
    {
        return match ($this) {
            self::OLUMLU => 'heroicon-o-face-smile',
            self::OLUMSUZ => 'heroicon-o-face-frown',
            self::NOTR => 'heroicon-o-minus',
        };
    }

    /**
     * Puan aralığına göre değerlendirme tipi öner
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 7 => self::OLUMLU,
            $score >= 4 => self::NOTR,
            default => self::OLUMSUZ,
        };
    }

    /**
     * Değerlendirme tipine göre önerilen puan aralığı
     */
    public function suggestedScoreRange(): array
    {
        return match ($this) {
            self::OLUMLU => ['min' => 7, 'max' => 10],
            self::NOTR => ['min' => 4, 'max' => 6],
            self::OLUMSUZ => ['min' => 1, 'max' => 3],
        };
    }

    /**
     * Tüm değerlendirme tiplerini array olarak döndür
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'color' => $case->color(),
            'icon' => $case->icon(),
            'suggested_score_range' => $case->suggestedScoreRange(),
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