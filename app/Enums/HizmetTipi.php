<?php

namespace App\Enums;

enum HizmetTipi: string
{
    case TELEFON = 'telefon';
    case TOPLANTI = 'toplanti';
    case EMAIL = 'email';
    case ZIYARET = 'ziyaret';
    case SMS = 'sms';
    case WHATSAPP = 'whatsapp';
    case VIDEO_GORUSME = 'video_gorusme';
    case SUNUM = 'sunum';
    case DEGERLENDIRME = 'degerlendirme';
    case DIGER = 'diger';

    /**
     * Hizmet tipi etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::TELEFON => 'Telefon Görüşmesi',
            self::TOPLANTI => 'Toplantı',
            self::EMAIL => 'E-posta',
            self::ZIYARET => 'Ziyaret',
            self::SMS => 'SMS',
            self::WHATSAPP => 'WhatsApp',
            self::VIDEO_GORUSME => 'Video Görüşme',
            self::SUNUM => 'Sunum',
            self::DEGERLENDIRME => 'Değerlendirme',
            self::DIGER => 'Diğer',
        };
    }

    /**
     * Hizmet tipi açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::TELEFON => 'Müşteri ile telefon görüşmesi yapıldı',
            self::TOPLANTI => 'Müşteri ile yüz yüze toplantı gerçekleştirildi',
            self::EMAIL => 'Müşteri ile e-posta iletişimi kuruldu',
            self::ZIYARET => 'Müşteri ziyaret edildi veya müşteri ziyarete geldi',
            self::SMS => 'Müşteri ile SMS iletişimi kuruldu',
            self::WHATSAPP => 'Müşteri ile WhatsApp üzerinden iletişim kuruldu',
            self::VIDEO_GORUSME => 'Müşteri ile video görüşme yapıldı',
            self::SUNUM => 'Müşteriye sunum yapıldı',
            self::DEGERLENDIRME => 'Müşteri değerlendirmesi yapıldı',
            self::DIGER => 'Diğer hizmet türü',
        };
    }

    /**
     * Hizmet tipi rengini döndür
     */
    public function color(): string
    {
        return match ($this) {
            self::TELEFON => 'blue',
            self::TOPLANTI => 'green',
            self::EMAIL => 'purple',
            self::ZIYARET => 'orange',
            self::SMS => 'yellow',
            self::WHATSAPP => 'emerald',
            self::VIDEO_GORUSME => 'indigo',
            self::SUNUM => 'pink',
            self::DEGERLENDIRME => 'red',
            self::DIGER => 'gray',
        };
    }

    /**
     * Hizmet tipi ikonunu döndür
     */
    public function icon(): string
    {
        return match ($this) {
            self::TELEFON => 'heroicon-o-phone',
            self::TOPLANTI => 'heroicon-o-users',
            self::EMAIL => 'heroicon-o-envelope',
            self::ZIYARET => 'heroicon-o-map-pin',
            self::SMS => 'heroicon-o-chat-bubble-left',
            self::WHATSAPP => 'heroicon-o-chat-bubble-left-right',
            self::VIDEO_GORUSME => 'heroicon-o-video-camera',
            self::SUNUM => 'heroicon-o-presentation-chart-bar',
            self::DEGERLENDIRME => 'heroicon-o-star',
            self::DIGER => 'heroicon-o-ellipsis-horizontal',
        };
    }

    /**
     * Süre gerektiren hizmet tipleri
     */
    public static function requiresDuration(): array
    {
        return [
            self::TELEFON,
            self::TOPLANTI,
            self::VIDEO_GORUSME,
            self::SUNUM,
        ];
    }

    /**
     * Lokasyon gerektiren hizmet tipleri
     */
    public static function requiresLocation(): array
    {
        return [
            self::TOPLANTI,
            self::ZIYARET,
            self::SUNUM,
        ];
    }

    /**
     * Katılımcı gerektiren hizmet tipleri
     */
    public static function requiresParticipants(): array
    {
        return [
            self::TOPLANTI,
            self::SUNUM,
            self::DEGERLENDIRME,
        ];
    }

    /**
     * Bu hizmet tipi süre gerektirir mi?
     */
    public function needsDuration(): bool
    {
        return in_array($this, self::requiresDuration());
    }

    /**
     * Bu hizmet tipi lokasyon gerektirir mi?
     */
    public function needsLocation(): bool
    {
        return in_array($this, self::requiresLocation());
    }

    /**
     * Bu hizmet tipi katılımcı gerektirir mi?
     */
    public function needsParticipants(): bool
    {
        return in_array($this, self::requiresParticipants());
    }

    /**
     * İletişim hizmetleri
     */
    public static function communicationTypes(): array
    {
        return [
            self::TELEFON,
            self::EMAIL,
            self::SMS,
            self::WHATSAPP,
            self::VIDEO_GORUSME,
        ];
    }

    /**
     * Fiziksel hizmetler
     */
    public static function physicalTypes(): array
    {
        return [
            self::TOPLANTI,
            self::ZIYARET,
            self::SUNUM,
        ];
    }

    /**
     * Bu hizmet tipi iletişim hizmeti mi?
     */
    public function isCommunication(): bool
    {
        return in_array($this, self::communicationTypes());
    }

    /**
     * Bu hizmet tipi fiziksel hizmet mi?
     */
    public function isPhysical(): bool
    {
        return in_array($this, self::physicalTypes());
    }

    /**
     * Tüm hizmet tiplerini array olarak döndür
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'color' => $case->color(),
            'icon' => $case->icon(),
            'requires_duration' => $case->needsDuration(),
            'requires_location' => $case->needsLocation(),
            'requires_participants' => $case->needsParticipants(),
            'is_communication' => $case->isCommunication(),
            'is_physical' => $case->isPhysical(),
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