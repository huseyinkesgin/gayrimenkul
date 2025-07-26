<?php

namespace App\Enums;

enum HatirlatmaTipi: string
{
    case ARAMA = 'arama';
    case TOPLANTI = 'toplanti';
    case EMAIL = 'email';
    case ZIYARET = 'ziyaret';
    case SMS = 'sms';
    case GOREV = 'gorev';
    case DIGER = 'diger';

    /**
     * Enum etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::ARAMA => 'Telefon Araması',
            self::TOPLANTI => 'Toplantı',
            self::EMAIL => 'E-posta',
            self::ZIYARET => 'Ziyaret',
            self::SMS => 'SMS',
            self::GOREV => 'Görev',
            self::DIGER => 'Diğer',
        };
    }

    /**
     * Enum açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::ARAMA => 'Müşteri ile telefon görüşmesi yapılacak',
            self::TOPLANTI => 'Yüz yüze veya online toplantı yapılacak',
            self::EMAIL => 'E-posta gönderilecek',
            self::ZIYARET => 'Müşteri ziyaret edilecek veya ofise davet edilecek',
            self::SMS => 'SMS mesajı gönderilecek',
            self::GOREV => 'Belirli bir görev tamamlanacak',
            self::DIGER => 'Diğer hatırlatma türleri',
        };
    }

    /**
     * Kategori rengini döndür
     */
    public function color(): string
    {
        return match ($this) {
            self::ARAMA => 'green',
            self::TOPLANTI => 'blue',
            self::EMAIL => 'purple',
            self::ZIYARET => 'orange',
            self::SMS => 'yellow',
            self::GOREV => 'red',
            self::DIGER => 'gray',
        };
    }

    /**
     * İkon adını döndür
     */
    public function icon(): string
    {
        return match ($this) {
            self::ARAMA => 'phone',
            self::TOPLANTI => 'users',
            self::EMAIL => 'envelope',
            self::ZIYARET => 'map-pin',
            self::SMS => 'chat-bubble-left',
            self::GOREV => 'clipboard-document-check',
            self::DIGER => 'bell',
        };
    }

    /**
     * Varsayılan süreyi döndür (dakika)
     */
    public function defaultDuration(): int
    {
        return match ($this) {
            self::ARAMA => 15,
            self::TOPLANTI => 60,
            self::EMAIL => 5,
            self::ZIYARET => 120,
            self::SMS => 2,
            self::GOREV => 30,
            self::DIGER => 15,
        };
    }

    /**
     * Öncelik seviyesini döndür
     */
    public function priority(): int
    {
        return match ($this) {
            self::TOPLANTI => 10,
            self::ZIYARET => 9,
            self::ARAMA => 8,
            self::GOREV => 7,
            self::EMAIL => 6,
            self::SMS => 5,
            self::DIGER => 4,
        };
    }

    /**
     * Otomatik bildirim gönderilsin mi
     */
    public function autoNotification(): bool
    {
        return match ($this) {
            self::TOPLANTI, self::ZIYARET, self::ARAMA => true,
            default => false,
        };
    }

    /**
     * Bildirim zamanlamasını döndür (dakika önceden)
     */
    public function notificationTiming(): array
    {
        return match ($this) {
            self::TOPLANTI => [1440, 60, 15], // 1 gün, 1 saat, 15 dakika önceden
            self::ZIYARET => [1440, 120], // 1 gün, 2 saat önceden
            self::ARAMA => [60, 15], // 1 saat, 15 dakika önceden
            self::GOREV => [1440], // 1 gün önceden
            default => [60], // 1 saat önceden
        };
    }

    /**
     * Bu tip için gerekli alanları döndür
     */
    public function requiredFields(): array
    {
        return match ($this) {
            self::ARAMA => ['telefon_numarasi'],
            self::TOPLANTI => ['konum', 'katilimcilar'],
            self::EMAIL => ['email_adresi', 'konu'],
            self::ZIYARET => ['adres', 'konum'],
            self::SMS => ['telefon_numarasi'],
            self::GOREV => ['gorev_detayi'],
            self::DIGER => [],
        };
    }

    /**
     * Bu tip için şablon mesajları döndür
     */
    public function templateMessages(): array
    {
        return match ($this) {
            self::ARAMA => [
                'Merhaba {musteri_adi}, size ulaşmaya çalışıyorum.',
                'Gayrimenkul konusunda görüşmek için arayacağım.',
            ],
            self::TOPLANTI => [
                'Toplantı hatırlatması: {tarih} tarihinde {saat} saatinde.',
                'Yarınki toplantımızı unutmayın.',
            ],
            self::EMAIL => [
                'Size önemli bilgiler göndereceğim.',
                'Portföy güncellemesi e-postası gönderilecek.',
            ],
            self::ZIYARET => [
                'Size ziyaret için geleceğim.',
                'Ofisimize bekliyoruz.',
            ],
            self::SMS => [
                'Kısa mesaj gönderilecek.',
                'Hızlı bilgilendirme yapılacak.',
            ],
            self::GOREV => [
                'Bu görev tamamlanmalı.',
                'Takip edilmesi gereken konu.',
            ],
            self::DIGER => [
                'Genel hatırlatma.',
            ],
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
            'icon' => $case->icon(),
            'defaultDuration' => $case->defaultDuration(),
            'priority' => $case->priority(),
            'autoNotification' => $case->autoNotification(),
            'notificationTiming' => $case->notificationTiming(),
            'requiredFields' => $case->requiredFields(),
            'templateMessages' => $case->templateMessages(),
        ], self::cases());
    }

    /**
     * Öncelik sırasına göre sıralanmış tipleri döndür
     */
    public static function byPriority(): array
    {
        $cases = self::cases();
        usort($cases, fn($a, $b) => $b->priority() <=> $a->priority());
        return $cases;
    }

    /**
     * Otomatik bildirim gerektiren tipleri döndür
     */
    public static function autoNotificationTypes(): array
    {
        return array_filter(self::cases(), fn($case) => $case->autoNotification());
    }

    /**
     * Şablon mesajı döndür
     */
    public function getRandomTemplate(): string
    {
        $templates = $this->templateMessages();
        return $templates[array_rand($templates)] ?? 'Hatırlatma';
    }
}