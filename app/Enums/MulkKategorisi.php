<?php

namespace App\Enums;

enum MulkKategorisi: string
{
    case ARSA = 'arsa';
    case ISYERI = 'isyeri';
    case KONUT = 'konut';
    case TURISTIK_TESIS = 'turistik_tesis';

    /**
     * Enum etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::ARSA => 'Arsa',
            self::ISYERI => 'İşyeri',
            self::KONUT => 'Konut',
            self::TURISTIK_TESIS => 'Turistik Tesis',
        };
    }

    /**
     * Enum açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::ARSA => 'İnşaat yapılabilir boş arazi',
            self::ISYERI => 'Ticari ve sanayi amaçlı gayrimenkuller',
            self::KONUT => 'Yaşam amaçlı gayrimenkuller',
            self::TURISTIK_TESIS => 'Turizm amaçlı tesisler',
        };
    }

    /**
     * Kategori rengini döndür
     */
    public function color(): string
    {
        return match ($this) {
            self::ARSA => 'green',
            self::ISYERI => 'blue',
            self::KONUT => 'orange',
            self::TURISTIK_TESIS => 'purple',
        };
    }

    /**
     * Alt kategorileri döndür
     */
    public function subCategories(): array
    {
        return match ($this) {
            self::ARSA => [
                'ticari_arsa' => 'Ticari Arsa',
                'sanayi_arsasi' => 'Sanayi Arsası',
                'konut_arsasi' => 'Konut Arsası',
                'diger_tarim' => 'Diğer Tarım',
                'ticari_konut' => 'Ticari + Konut Arsası',
                'tarla'=> 'Tarla'
            ],
            self::ISYERI => [
                'depo' => 'Depo',
                'fabrika' => 'Fabrika',
                'magaza' => 'Mağaza',
                'ofis' => 'Ofis',
                'dukkan' => 'Dükkan',
            ],
            self::KONUT => [
                'daire' => 'Daire',
                'rezidans' => 'Rezidans',
                'villa' => 'Villa',
                'yali' => 'Yalı',
                'yazlik' => 'Yazlık',
                'mustakil_ev' => 'Müstakil Ev'
            ],
            self::TURISTIK_TESIS => [
                'butik_otel' => 'Butik Otel',
                'apart_otel' => 'Apart Otel',
                'hotel' => 'Hotel',
                'motel' => 'Motel',
                'tatil_koyu' => 'Tatil Köyü',
            ],
        };
    }

    /**
     * Galeri gerekli mi kontrol et
     */
    public function requiresGallery(): bool
    {
        return match ($this) {
            self::KONUT, self::ISYERI, self::TURISTIK_TESIS => true,
            self::ARSA => false,
        };
    }

    /**
     * Hangi resim kategorilerini desteklediğini döndür
     */
    public function supportedImageCategories(): array
    {
        return match ($this) {
            self::ARSA => [
                ResimKategorisi::UYDU,
                ResimKategorisi::OZNITELIK,
                ResimKategorisi::BUYUKSEHIR,
                ResimKategorisi::EGIM,
                ResimKategorisi::EIMAR,
            ],
            self::ISYERI => [
                ResimKategorisi::GALERI,
                ResimKategorisi::UYDU,
                ResimKategorisi::OZNITELIK,
                ResimKategorisi::BUYUKSEHIR,
            ],
            self::KONUT, self::TURISTIK_TESIS => [
                ResimKategorisi::GALERI,
            ],
        };
    }

    /**
     * Hangi döküman tiplerini desteklediğini döndür
     */
    public function supportedDocumentTypes(): array
    {
        return match ($this) {
            self::ARSA => [
                DokumanTipi::TAPU,
                DokumanTipi::DIGER,
            ],
            self::ISYERI => [
                DokumanTipi::TAPU,
                DokumanTipi::AUTOCAD,
                DokumanTipi::PROJE_RESMI,
                DokumanTipi::RUHSAT,
                DokumanTipi::DIGER,
            ],
            self::KONUT, self::TURISTIK_TESIS => [
                DokumanTipi::TAPU,
                DokumanTipi::RUHSAT,
                DokumanTipi::DIGER,
            ],
        };
    }

    /**
     * İkon adını döndür
     */
    public function icon(): string
    {
        return match ($this) {
            self::ARSA => 'map',
            self::ISYERI => 'building-office',
            self::KONUT => 'home',
            self::TURISTIK_TESIS => 'building-storefront',
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
            'subCategories' => $case->subCategories(),
            'requiresGallery' => $case->requiresGallery(),
            'supportedImageCategories' => array_map(fn($cat) => $cat->value, $case->supportedImageCategories()),
            'supportedDocumentTypes' => array_map(fn($type) => $type->value, $case->supportedDocumentTypes()),
        ], self::cases());
    }

    /**
     * Alt kategori etiketini döndür
     */
    public static function getSubCategoryLabel(string $mainCategory, string $subCategory): string
    {
        $enum = self::from($mainCategory);
        $subCategories = $enum->subCategories();
        
        return $subCategories[$subCategory] ?? $subCategory;
    }
}