<?php

namespace App\Enums;

enum TalepDurumu: string
{
    case AKTIF = 'aktif';
    case BEKLEMEDE = 'beklemede';
    case ESLESTI = 'eslesti';
    case TAMAMLANDI = 'tamamlandi';
    case IPTAL_EDILDI = 'iptal_edildi';
    case ARŞIVLENDI = 'arsivlendi';

    /**
     * Durum etiketini döndür
     */
    public function label(): string
    {
        return match($this) {
            self::AKTIF => 'Aktif',
            self::BEKLEMEDE => 'Beklemede',
            self::ESLESTI => 'Eşleşti',
            self::TAMAMLANDI => 'Tamamlandı',
            self::IPTAL_EDILDI => 'İptal Edildi',
            self::ARŞIVLENDI => 'Arşivlendi',
        };
    }

    /**
     * Durum açıklamasını döndür
     */
    public function description(): string
    {
        return match($this) {
            self::AKTIF => 'Talep aktif olarak aranıyor',
            self::BEKLEMEDE => 'Müşteri kararını bekliyor',
            self::ESLESTI => 'Uygun portföy bulundu',
            self::TAMAMLANDI => 'Talep başarıyla tamamlandı',
            self::IPTAL_EDILDI => 'Müşteri talebi iptal etti',
            self::ARŞIVLENDI => 'Talep arşivlendi',
        };
    }

    /**
     * Durum rengini döndür
     */
    public function color(): string
    {
        return match($this) {
            self::AKTIF => 'green',
            self::BEKLEMEDE => 'yellow',
            self::ESLESTI => 'blue',
            self::TAMAMLANDI => 'purple',
            self::IPTAL_EDILDI => 'red',
            self::ARŞIVLENDI => 'gray',
        };
    }

    /**
     * Durum ikonunu döndür
     */
    public function icon(): string
    {
        return match($this) {
            self::AKTIF => 'fas fa-search',
            self::BEKLEMEDE => 'fas fa-clock',
            self::ESLESTI => 'fas fa-handshake',
            self::TAMAMLANDI => 'fas fa-check-circle',
            self::IPTAL_EDILDI => 'fas fa-times-circle',
            self::ARŞIVLENDI => 'fas fa-archive',
        };
    }

    /**
     * Aktif durumlar (eşleştirme için)
     */
    public static function aktifDurumlar(): array
    {
        return [
            self::AKTIF,
            self::BEKLEMEDE,
            self::ESLESTI,
        ];
    }

    /**
     * Pasif durumlar
     */
    public static function pasifDurumlar(): array
    {
        return [
            self::TAMAMLANDI,
            self::IPTAL_EDILDI,
            self::ARŞIVLENDI,
        ];
    }

    /**
     * Durum aktif mi?
     */
    public function isAktif(): bool
    {
        return in_array($this, self::aktifDurumlar());
    }

    /**
     * Durum pasif mi?
     */
    public function isPasif(): bool
    {
        return in_array($this, self::pasifDurumlar());
    }
}