<?php

namespace App\Enums;

enum ResimKategorisi: string
{
    case GALERI = 'galeri';
    case AVATAR = 'avatar';
    case LOGO = 'logo';
    case UYDU = 'uydu';
    case OZNITELIK = 'oznitelik';
    case BUYUKSEHIR = 'buyuksehir';
    case EGIM = 'egim';
    case EIMAR = 'eimar';
    case KAPAK_RESMI = 'kapak_resmi';
    case IC_MEKAN = 'ic_mekan';
    case DIS_MEKAN = 'dis_mekan';
    case DETAY = 'detay';
    case PLAN = 'plan';
    case CEPHE = 'cephe';
    case MANZARA = 'manzara';

    /**
     * Enum etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::GALERI => 'Galeri',
            self::AVATAR => 'Avatar',
            self::LOGO => 'Logo',
            self::UYDU => 'Uydu Resmi',
            self::OZNITELIK => 'Öznitelik Resmi',
            self::BUYUKSEHIR => 'Büyükşehir Resmi',
            self::EGIM => 'Eğim Resmi',
            self::EIMAR => 'E-İmar Resmi',
            self::KAPAK_RESMI => 'Kapak Resmi',
            self::IC_MEKAN => 'İç Mekan',
            self::DIS_MEKAN => 'Dış Mekan',
            self::DETAY => 'Detay',
            self::PLAN => 'Plan',
            self::CEPHE => 'Cephe',
            self::MANZARA => 'Manzara',
        };
        
    }

    /**
     * Enum açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::GALERI => 'Mülk galeri fotoğrafları',
            self::AVATAR => 'Kullanıcı profil resmi',
            self::LOGO => 'Firma logosu',
            self::UYDU => 'Uydu görüntüsü',
            self::OZNITELIK => 'Öznitelik haritası',
            self::BUYUKSEHIR => 'Büyükşehir haritası',
            self::EGIM => 'Eğim haritası',
            self::EIMAR => 'E-İmar planı',
            self::KAPAK_RESMI => 'Mülkün ana kapak resmi',
            self::IC_MEKAN => 'İç mekan fotoğrafları',
            self::DIS_MEKAN => 'Dış mekan fotoğrafları',
            self::DETAY => 'Detay fotoğrafları',
            self::PLAN => 'Plan ve çizim resimleri',
            self::CEPHE => 'Bina cephe fotoğrafları',
            self::MANZARA => 'Manzara fotoğrafları',
        };
    }

    /**
     * Hangi mülk tiplerinde kullanılabileceğini döndür
     */
    public function applicableToPropertyTypes(): array
    {
        return match ($this) {
            self::GALERI => ['konut', 'isyeri', 'turistik_tesis'],
            self::KAPAK_RESMI => ['konut', 'isyeri', 'turistik_tesis'],
            self::IC_MEKAN => ['konut', 'isyeri', 'turistik_tesis'],
            self::DIS_MEKAN => ['konut', 'isyeri', 'turistik_tesis'],
            self::DETAY => ['konut', 'isyeri', 'turistik_tesis'],
            self::CEPHE => ['konut', 'isyeri', 'turistik_tesis'],
            self::MANZARA => ['konut', 'isyeri', 'turistik_tesis', 'arsa'],
            self::PLAN => ['konut', 'isyeri', 'turistik_tesis'],
            self::UYDU, self::OZNITELIK, self::BUYUKSEHIR, self::EGIM, self::EIMAR => ['arsa', 'isyeri'],
            self::AVATAR => ['user', 'musteri', 'personel'],
            self::LOGO => ['firma'],
        };
    }

    /**
     * Maksimum dosya boyutunu döndür (MB)
     */
    public function maxFileSize(): int
    {
        return match ($this) {
            self::AVATAR, self::LOGO => 2,
            self::GALERI, self::KAPAK_RESMI, self::IC_MEKAN, self::DIS_MEKAN, self::DETAY, self::CEPHE, self::MANZARA => 8,
            self::PLAN => 15,
            self::UYDU, self::OZNITELIK, self::BUYUKSEHIR, self::EGIM, self::EIMAR => 20,
            default => 10,
        };
    }

    /**
     * İzin verilen dosya türlerini döndür
     */
    public function allowedMimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    }

    /**
     * Kategori rengini döndür
     */
    public function color(): string
    {
        return match ($this) {
            self::GALERI => 'blue',
            self::KAPAK_RESMI => 'emerald',
            self::IC_MEKAN => 'cyan',
            self::DIS_MEKAN => 'teal',
            self::DETAY => 'sky',
            self::CEPHE => 'slate',
            self::MANZARA => 'lime',
            self::PLAN => 'violet',
            self::AVATAR => 'green',
            self::LOGO => 'purple',
            self::UYDU => 'orange',
            self::OZNITELIK => 'red',
            self::BUYUKSEHIR => 'yellow',
            self::EGIM => 'indigo',
            self::EIMAR => 'pink',
        };
    }

    /**
     * Harita kategorisi mi kontrol et
     */
    public function isMapCategory(): bool
    {
        return in_array($this, [
            self::UYDU,
            self::OZNITELIK,
            self::BUYUKSEHIR,
            self::EGIM,
            self::EIMAR
        ]);
    }

    /**
     * Profil kategorisi mi kontrol et
     */
    public function isProfileCategory(): bool
    {
        return in_array($this, [self::AVATAR, self::LOGO]);
    }

    /**
     * Galeri kategorisi mi kontrol et
     */
    public function isGalleryCategory(): bool
    {
        return in_array($this, [
            self::GALERI,
            self::KAPAK_RESMI,
            self::IC_MEKAN,
            self::DIS_MEKAN,
            self::DETAY,
            self::CEPHE,
            self::MANZARA
        ]);
    }

    /**
     * Teknik döküman kategorisi mi kontrol et
     */
    public function isTechnicalCategory(): bool
    {
        return in_array($this, [
            self::PLAN,
            self::UYDU,
            self::OZNITELIK,
            self::BUYUKSEHIR,
            self::EGIM,
            self::EIMAR
        ]);
    }

    /**
     * Özel işleme gerektiren kategori mi
     */
    public function requiresSpecialProcessing(): bool
    {
        return in_array($this, [
            self::AVATAR,
            self::LOGO,
            self::KAPAK_RESMI,
            self::PLAN
        ]);
    }

    /**
     * Onay gerektiren kategori mi
     */
    public function requiresApproval(): bool
    {
        return in_array($this, [
            self::GALERI,
            self::KAPAK_RESMI,
            self::IC_MEKAN,
            self::DIS_MEKAN,
            self::CEPHE,
            self::MANZARA
        ]);
    }

    /**
     * Watermark gerektiren kategori mi
     */
    public function requiresWatermark(): bool
    {
        return in_array($this, [
            self::GALERI,
            self::KAPAK_RESMI,
            self::IC_MEKAN,
            self::DIS_MEKAN,
            self::DETAY,
            self::CEPHE,
            self::MANZARA
        ]);
    }

    /**
     * Kategori için önerilen boyutlar
     */
    public function recommendedDimensions(): array
    {
        return match ($this) {
            self::AVATAR => ['width' => 300, 'height' => 300],
            self::LOGO => ['width' => 500, 'height' => 200],
            self::KAPAK_RESMI => ['width' => 1920, 'height' => 1080],
            self::GALERI, self::IC_MEKAN, self::DIS_MEKAN, self::DETAY, self::CEPHE, self::MANZARA => ['width' => 1600, 'height' => 1200],
            self::PLAN => ['width' => 2048, 'height' => 1536],
            default => ['width' => 1024, 'height' => 768],
        };
    }

    /**
     * Kategori için kalite ayarı
     */
    public function qualitySetting(): int
    {
        return match ($this) {
            self::AVATAR, self::LOGO => 85,
            self::KAPAK_RESMI => 95,
            self::GALERI, self::IC_MEKAN, self::DIS_MEKAN, self::DETAY, self::CEPHE, self::MANZARA => 90,
            self::PLAN, self::UYDU, self::OZNITELIK, self::BUYUKSEHIR, self::EGIM, self::EIMAR => 100,
            default => 85,
        };
    }

    /**
     * Kategori için sıralama önceliği
     */
    public function sortPriority(): int
    {
        return match ($this) {
            self::KAPAK_RESMI => 1,
            self::GALERI => 2,
            self::IC_MEKAN => 3,
            self::DIS_MEKAN => 4,
            self::CEPHE => 5,
            self::MANZARA => 6,
            self::DETAY => 7,
            self::PLAN => 8,
            self::AVATAR => 9,
            self::LOGO => 10,
            default => 99,
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
            'maxFileSize' => $case->maxFileSize(),
            'recommendedDimensions' => $case->recommendedDimensions(),
            'qualitySetting' => $case->qualitySetting(),
            'sortPriority' => $case->sortPriority(),
            'isMapCategory' => $case->isMapCategory(),
            'isProfileCategory' => $case->isProfileCategory(),
            'isGalleryCategory' => $case->isGalleryCategory(),
            'isTechnicalCategory' => $case->isTechnicalCategory(),
            'requiresSpecialProcessing' => $case->requiresSpecialProcessing(),
            'requiresApproval' => $case->requiresApproval(),
            'requiresWatermark' => $case->requiresWatermark(),
        ], self::cases());
    }

    /**
     * Mülk tipine göre uygun kategorileri döndür
     */
    public static function forPropertyType(string $propertyType): array
    {
        return array_filter(self::cases(), function ($case) use ($propertyType) {
            return in_array($propertyType, $case->applicableToPropertyTypes());
        });
    }

    /**
     * Galeri kategorilerini döndür
     */
    public static function galleryCategories(): array
    {
        return array_filter(self::cases(), fn($case) => $case->isGalleryCategory());
    }

    /**
     * Harita kategorilerini döndür
     */
    public static function mapCategories(): array
    {
        return array_filter(self::cases(), fn($case) => $case->isMapCategory());
    }

    /**
     * Profil kategorilerini döndür
     */
    public static function profileCategories(): array
    {
        return array_filter(self::cases(), fn($case) => $case->isProfileCategory());
    }

    /**
     * Teknik döküman kategorilerini döndür
     */
    public static function technicalCategories(): array
    {
        return array_filter(self::cases(), fn($case) => $case->isTechnicalCategory());
    }

    /**
     * Onay gerektiren kategorileri döndür
     */
    public static function approvalRequiredCategories(): array
    {
        return array_filter(self::cases(), fn($case) => $case->requiresApproval());
    }

    /**
     * Özel işleme gerektiren kategorileri döndür
     */
    public static function specialProcessingCategories(): array
    {
        return array_filter(self::cases(), fn($case) => $case->requiresSpecialProcessing());
    }

    /**
     * Sıralama önceliğine göre kategorileri döndür
     */
    public static function sortedByPriority(): array
    {
        $cases = self::cases();
        usort($cases, fn($a, $b) => $a->sortPriority() <=> $b->sortPriority());
        return $cases;
    }
}