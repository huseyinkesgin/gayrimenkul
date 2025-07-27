<?php

namespace App\Enums;

enum MulkKategorisi: string
{
    case ARSA = 'arsa';
    case ISYERI = 'isyeri';
    case KONUT = 'konut';
    case TURISTIK_TESIS = 'turistik_tesis';

    /**
     * Kategori etiketini döndür
     */
    public function label(): string
    {
        return match($this) {
            self::ARSA => 'Arsa',
            self::ISYERI => 'İşyeri',
            self::KONUT => 'Konut',
            self::TURISTIK_TESIS => 'Turistik Tesis',
        };
    }

    /**
     * Kategori açıklamasını döndür
     */
    public function description(): string
    {
        return match($this) {
            self::ARSA => 'İnşaat yapılabilir boş arazi',
            self::ISYERI => 'Ticari ve endüstriyel amaçlı gayrimenkuller',
            self::KONUT => 'Yaşam amaçlı konut tipleri',
            self::TURISTIK_TESIS => 'Turizm ve konaklama tesisleri',
        };
    }

    /**
     * Alt kategorileri döndür
     */
    public function altKategoriler(): array
    {
        return match($this) {
            self::ARSA => [
                'TicariArsa' => 'Ticari Arsa',
                'SanayiArsasi' => 'Sanayi Arsası',
                'KonutArsasi' => 'Konut Arsası',
            ],
            self::ISYERI => [
                'Depo' => 'Depo',
                'Fabrika' => 'Fabrika',
                'Magaza' => 'Mağaza',
                'Ofis' => 'Ofis',
                'Dukkan' => 'Dükkan',
            ],
            self::KONUT => [
                'Daire' => 'Daire',
                'Rezidans' => 'Rezidans',
                'Villa' => 'Villa',
                'Yali' => 'Yalı',
                'Yazlik' => 'Yazlık',
            ],
            self::TURISTIK_TESIS => [
                'ButikOtel' => 'Butik Otel',
                'ApartOtel' => 'Apart Otel',
                'Hotel' => 'Hotel',
                'Motel' => 'Motel',
                'TatilKoyu' => 'Tatil Köyü',
            ],
        };
    }

    /**
     * Kategori rengini döndür
     */
    public function color(): string
    {
        return match($this) {
            self::ARSA => 'brown',
            self::ISYERI => 'blue',
            self::KONUT => 'green',
            self::TURISTIK_TESIS => 'purple',
        };
    }

    /**
     * Kategori ikonunu döndür
     */
    public function icon(): string
    {
        return match($this) {
            self::ARSA => 'fas fa-map',
            self::ISYERI => 'fas fa-building',
            self::KONUT => 'fas fa-home',
            self::TURISTIK_TESIS => 'fas fa-hotel',
        };
    }
}