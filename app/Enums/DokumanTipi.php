<?php

namespace App\Enums;

enum DokumanTipi: string
{
    case TAPU = 'tapu';
    case AUTOCAD = 'autocad';
    case PROJE_RESMI = 'proje_resmi';
    case RUHSAT = 'ruhsat';
    case IMAR_PLANI = 'imar_plani';
    case YAPI_KULLANIM = 'yapi_kullanim';
    case ISYERI_ACMA = 'isyeri_acma';
    case CEVRE_IZNI = 'cevre_izni';
    case YANGIN_RAPORU = 'yangin_raporu';
    case DIGER = 'diger';

    /**
     * Enum etiketini döndür
     */
    public function label(): string
    {
        return match ($this) {
            self::TAPU => 'Tapu',
            self::AUTOCAD => 'AutoCAD Dosyası',
            self::PROJE_RESMI => 'Proje Resmi',
            self::RUHSAT => 'Ruhsat',
            self::IMAR_PLANI => 'İmar Planı',
            self::YAPI_KULLANIM => 'Yapı Kullanım İzni',
            self::ISYERI_ACMA => 'İşyeri Açma Ruhsatı',
            self::CEVRE_IZNI => 'Çevre İzni',
            self::YANGIN_RAPORU => 'Yangın Raporu',
            self::DIGER => 'Diğer',
        };
    }

    /**
     * Enum açıklamasını döndür
     */
    public function description(): string
    {
        return match ($this) {
            self::TAPU => 'Mülkiyet belgesi',
            self::AUTOCAD => 'Teknik çizim dosyası',
            self::PROJE_RESMI => 'Proje görselleştirme',
            self::RUHSAT => 'Yasal izin belgesi',
            self::IMAR_PLANI => 'İmar durumu belgesi',
            self::YAPI_KULLANIM => 'Yapı kullanım izin belgesi',
            self::ISYERI_ACMA => 'İşyeri açma ruhsat belgesi',
            self::CEVRE_IZNI => 'Çevresel etki değerlendirme belgesi',
            self::YANGIN_RAPORU => 'Yangın güvenlik raporu',
            self::DIGER => 'Diğer döküman türleri',
        };
    }

    /**
     * İzin verilen dosya türlerini döndür
     */
    public function allowedMimeTypes(): array
    {
        return match ($this) {
            self::TAPU => ['application/pdf', 'image/jpeg', 'image/png'],
            self::AUTOCAD => ['application/acad', 'application/dwg', 'application/dxf', 'application/x-autocad'],
            self::PROJE_RESMI => ['image/jpeg', 'image/png', 'application/pdf', 'image/tiff'],
            self::RUHSAT => ['application/pdf', 'image/jpeg', 'image/png'],
            self::IMAR_PLANI => ['application/pdf', 'image/jpeg', 'image/png', 'image/tiff'],
            self::YAPI_KULLANIM => ['application/pdf', 'image/jpeg', 'image/png'],
            self::ISYERI_ACMA => ['application/pdf', 'image/jpeg', 'image/png'],
            self::CEVRE_IZNI => ['application/pdf', 'image/jpeg', 'image/png'],
            self::YANGIN_RAPORU => ['application/pdf', 'image/jpeg', 'image/png'],
            self::DIGER => [
                'application/pdf', 'image/jpeg', 'image/png', 
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ],
        };
    }

    /**
     * Maksimum dosya boyutunu döndür (MB)
     */
    public function maxFileSize(): int
    {
        return match ($this) {
            self::AUTOCAD => 50,
            self::PROJE_RESMI => 25,
            self::IMAR_PLANI => 20,
            default => 10,
        };
    }

    /**
     * Mülk tipine göre uygun döküman tiplerini döndür
     */
    public static function forMulkType(string $mulkType): array
    {
        return match ($mulkType) {
            'arsa' => [
                self::TAPU,
                self::IMAR_PLANI,
                self::CEVRE_IZNI,
                self::DIGER
            ],
            'isyeri' => [
                self::TAPU,
                self::AUTOCAD,
                self::PROJE_RESMI,
                self::RUHSAT,
                self::YAPI_KULLANIM,
                self::ISYERI_ACMA,
                self::YANGIN_RAPORU,
                self::DIGER
            ],
            'konut' => [
                self::TAPU,
                self::PROJE_RESMI,
                self::YAPI_KULLANIM,
                self::DIGER
            ],
            'turistik_tesis' => [
                self::TAPU,
                self::AUTOCAD,
                self::PROJE_RESMI,
                self::RUHSAT,
                self::YAPI_KULLANIM,
                self::ISYERI_ACMA,
                self::CEVRE_IZNI,
                self::YANGIN_RAPORU,
                self::DIGER
            ],
            default => [self::DIGER]
        };
    }

    /**
     * Zorunlu dökümanları döndür
     */
    public function isRequired(): bool
    {
        return match ($this) {
            self::TAPU => true,
            default => false,
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
        ], self::cases());
    }
}