<?php

namespace Database\Factories;

use App\Models\MusteriMulkIliskisi;
use App\Models\Musteri\Musteri;
use App\Models\Mulk\BaseMulk;
use App\Models\User;
use App\Enums\IliskiTipi;
use App\Enums\IliskiDurumu;
use Illuminate\Database\Eloquent\Factories\Factory;

class MusteriMulkIliskisiFactory extends Factory
{
    protected $model = MusteriMulkIliskisi::class;

    public function definition(): array
    {
        $baslangicTarihi = $this->faker->dateTimeBetween('-1 year', 'now');
        $ilgiSeviyesi = $this->faker->numberBetween(1, 10);
        $aciliyetSeviyesi = $this->faker->numberBetween(1, 10);

        return [
            'musteri_id' => Musteri::factory(),
            'mulk_id' => BaseMulk::factory(),
            'mulk_type' => $this->faker->randomElement([
                'App\\Models\\Mulk\\Arsa\\TicariArsa',
                'App\\Models\\Mulk\\Isyeri\\Ofis',
                'App\\Models\\Mulk\\Konut\\Daire',
                'App\\Models\\Mulk\\TuristikTesis\\Hotel',
            ]),
            'iliski_tipi' => $this->faker->randomElement(IliskiTipi::cases()),
            'baslangic_tarihi' => $baslangicTarihi,
            'bitis_tarihi' => $this->faker->optional(0.2)->dateTimeBetween($baslangicTarihi, '+6 months'),
            'durum' => $this->faker->randomElement(IliskiDurumu::cases()),
            'ilgi_seviyesi' => $ilgiSeviyesi,
            'teklif_miktari' => $this->faker->optional(0.4)->randomFloat(2, 100000, 5000000),
            'teklif_para_birimi' => $this->faker->randomElement(['TRY', 'USD', 'EUR']),
            'son_teklif_tarihi' => $this->faker->optional(0.4)->dateTimeBetween('-3 months', 'now'),
            'beklenen_karar_tarihi' => $this->faker->optional(0.6)->dateTimeBetween('now', '+2 months'),
            'karar_verme_sebebi' => $this->faker->optional(0.3)->paragraph(),
            'rekabet_durumu' => $this->faker->optional(0.5)->sentence(),
            'avantajlar' => $this->faker->optional(0.6)->randomElements([
                'Merkezi konum',
                'Uygun fiyat',
                'Yeni yapı',
                'Geniş alan',
                'Otopark mevcut',
                'Güvenlik sistemi',
                'Deniz manzarası',
            ], $this->faker->numberBetween(1, 4)),
            'dezavantajlar' => $this->faker->optional(0.4)->randomElements([
                'Yüksek fiyat',
                'Eski yapı',
                'Dar alan',
                'Otopark yok',
                'Gürültülü çevre',
                'Ulaşım sorunu',
            ], $this->faker->numberBetween(1, 3)),
            'ozel_istekler' => $this->faker->optional(0.5)->randomElements([
                'Mobilyalı teslim',
                'Tadilat yapılsın',
                'Klima takılsın',
                'Boyama yapılsın',
                'Temizlik yapılsın',
            ], $this->faker->numberBetween(1, 3)),
            'finansman_durumu' => $this->faker->optional(0.7)->randomElement([
                'Nakit ödeme',
                'Kredi kullanacak',
                'Kısmi nakit + kredi',
                'Takas',
                'Belirsiz',
            ]),
            'aciliyet_seviyesi' => $aciliyetSeviyesi,
            'referans_kaynak' => $this->faker->optional(0.6)->randomElement([
                'İnternet',
                'Arkadaş tavsiyesi',
                'Gazete ilanı',
                'Sosyal medya',
                'Mevcut müşteri',
                'Emlak fuarı',
            ]),
            'takip_sikligi' => $this->faker->optional(0.8)->numberBetween(1, 30),
            'son_aktivite_tarihi' => $this->faker->optional(0.9)->dateTimeBetween('-1 month', 'now'),
            'sonraki_adim' => $this->faker->optional(0.7)->sentence(),
            'sorumlu_personel_id' => User::factory(),
            'notlar' => $this->faker->optional(0.8)->paragraph(),
            'etiketler' => $this->faker->optional(0.5)->randomElements([
                'VIP', 'Acil', 'Potansiyel', 'Sıcak', 'Soğuk', 'Takip Gerekli'
            ], $this->faker->numberBetween(1, 3)),
            'ozel_alanlar' => $this->faker->optional(0.3)->randomElement([
                [
                    'ozel_not' => $this->faker->sentence(),
                    'ozel_kod' => $this->faker->bothify('??###'),
                ],
                [
                    'kampanya_kodu' => $this->faker->bothify('KAMP###'),
                    'indirim_orani' => $this->faker->numberBetween(5, 20),
                ],
            ]),
            'aktif_mi' => $this->faker->boolean(90),
            'siralama' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * İlgilenen müşteri
     */
    public function ilgileniyor(): static
    {
        return $this->state(fn (array $attributes) => [
            'iliski_tipi' => IliskiTipi::ILGILENIYOR,
            'durum' => IliskiDurumu::AKTIF,
            'ilgi_seviyesi' => $this->faker->numberBetween(5, 8),
        ]);
    }

    /**
     * Görüşen müşteri
     */
    public function gorustu(): static
    {
        return $this->state(fn (array $attributes) => [
            'iliski_tipi' => IliskiTipi::GORUSTU,
            'durum' => IliskiDurumu::AKTIF,
            'ilgi_seviyesi' => $this->faker->numberBetween(6, 9),
            'son_aktivite_tarihi' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Değerlendiren müşteri
     */
    public function degerlendiriyor(): static
    {
        return $this->state(fn (array $attributes) => [
            'iliski_tipi' => IliskiTipi::DEGERLENDIRIYOR,
            'durum' => IliskiDurumu::BEKLEMEDE,
            'ilgi_seviyesi' => $this->faker->numberBetween(7, 9),
            'beklenen_karar_tarihi' => $this->faker->dateTimeBetween('+1 day', '+2 weeks'),
        ]);
    }

    /**
     * Teklif veren müşteri
     */
    public function teklifVerdi(): static
    {
        return $this->state(fn (array $attributes) => [
            'iliski_tipi' => IliskiTipi::TEKLIF_VERDI,
            'durum' => IliskiDurumu::BEKLEMEDE,
            'ilgi_seviyesi' => $this->faker->numberBetween(8, 10),
            'teklif_miktari' => $this->faker->randomFloat(2, 500000, 3000000),
            'son_teklif_tarihi' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'beklenen_karar_tarihi' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }

    /**
     * Müzakere eden müşteri
     */
    public function muzakereEdiyor(): static
    {
        return $this->state(fn (array $attributes) => [
            'iliski_tipi' => IliskiTipi::MUZAKERE_EDIYOR,
            'durum' => IliskiDurumu::AKTIF,
            'ilgi_seviyesi' => $this->faker->numberBetween(8, 10),
            'teklif_miktari' => $this->faker->randomFloat(2, 500000, 3000000),
            'aciliyet_seviyesi' => $this->faker->numberBetween(7, 10),
        ]);
    }

    /**
     * Satın alan müşteri
     */
    public function satinAldi(): static
    {
        return $this->state(fn (array $attributes) => [
            'iliski_tipi' => IliskiTipi::SATIN_ALDI,
            'durum' => IliskiDurumu::TAMAMLANDI,
            'ilgi_seviyesi' => 10,
            'teklif_miktari' => $this->faker->randomFloat(2, 500000, 3000000),
            'bitis_tarihi' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * İptal eden müşteri
     */
    public function iptalEtti(): static
    {
        return $this->state(fn (array $attributes) => [
            'iliski_tipi' => IliskiTipi::IPTAL_ETTI,
            'durum' => IliskiDurumu::IPTAL,
            'bitis_tarihi' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'karar_verme_sebebi' => $this->faker->sentence(),
        ]);
    }

    /**
     * Yüksek ilgi seviyesi
     */
    public function yuksekIlgi(): static
    {
        return $this->state(fn (array $attributes) => [
            'ilgi_seviyesi' => $this->faker->numberBetween(8, 10),
            'aciliyet_seviyesi' => $this->faker->numberBetween(6, 10),
        ]);
    }

    /**
     * Düşük ilgi seviyesi
     */
    public function dusukIlgi(): static
    {
        return $this->state(fn (array $attributes) => [
            'ilgi_seviyesi' => $this->faker->numberBetween(1, 4),
            'aciliyet_seviyesi' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * Yüksek teklif miktarı
     */
    public function yuksekTeklif(): static
    {
        return $this->state(fn (array $attributes) => [
            'teklif_miktari' => $this->faker->randomFloat(2, 2000000, 10000000),
            'teklif_para_birimi' => 'TRY',
            'son_teklif_tarihi' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Acil durum
     */
    public function acil(): static
    {
        return $this->state(fn (array $attributes) => [
            'aciliyet_seviyesi' => $this->faker->numberBetween(8, 10),
            'beklenen_karar_tarihi' => $this->faker->dateTimeBetween('now', '+1 week'),
            'etiketler' => ['Acil', 'Hızlı Karar'],
        ]);
    }

    /**
     * VIP müşteri ilişkisi
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'ilgi_seviyesi' => $this->faker->numberBetween(8, 10),
            'teklif_miktari' => $this->faker->randomFloat(2, 3000000, 15000000),
            'etiketler' => ['VIP', 'Özel İlgi'],
            'ozel_istekler' => [
                'VIP hizmet',
                'Özel danışman',
                'Hızlı işlem',
            ],
        ]);
    }

    /**
     * Finansman sorunu olan
     */
    public function finansmanSorunu(): static
    {
        return $this->state(fn (array $attributes) => [
            'finansman_durumu' => 'Kredi sorunu',
            'dezavantajlar' => ['Finansman sorunu', 'Kredi onayı bekliyor'],
            'etiketler' => ['Finansman Sorunu'],
        ]);
    }

    /**
     * Nakit ödeme yapacak
     */
    public function nakitOdeme(): static
    {
        return $this->state(fn (array $attributes) => [
            'finansman_durumu' => 'Nakit ödeme',
            'avantajlar' => ['Nakit ödeme', 'Hızlı işlem'],
            'etiketler' => ['Nakit', 'Hızlı İşlem'],
        ]);
    }

    /**
     * Uzun süredir takip edilen
     */
    public function uzunSureTakip(): static
    {
        return $this->state(fn (array $attributes) => [
            'baslangic_tarihi' => $this->faker->dateTimeBetween('-1 year', '-6 months'),
            'son_aktivite_tarihi' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
            'etiketler' => ['Uzun Süre', 'Takip Gerekli'],
        ]);
    }

    /**
     * Son aktivitesi yakın
     */
    public function sonAktiviteYakin(): static
    {
        return $this->state(fn (array $attributes) => [
            'son_aktivite_tarihi' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'sonraki_adim' => 'Takip görüşmesi yapılacak',
        ]);
    }

    /**
     * Etiketli ilişki
     */
    public function withTags(array $tags = null): static
    {
        return $this->state(fn (array $attributes) => [
            'etiketler' => $tags ?? ['Önemli', 'Takip Gerekli', 'Potansiyel'],
        ]);
    }

    /**
     * Avantajlı ilişki
     */
    public function withAdvantages(array $advantages = null): static
    {
        return $this->state(fn (array $attributes) => [
            'avantajlar' => $advantages ?? [
                'Merkezi konum',
                'Uygun fiyat',
                'Yeni yapı',
                'Geniş alan',
            ],
        ]);
    }

    /**
     * Dezavantajlı ilişki
     */
    public function withDisadvantages(array $disadvantages = null): static
    {
        return $this->state(fn (array $attributes) => [
            'dezavantajlar' => $disadvantages ?? [
                'Yüksek fiyat',
                'Eski yapı',
                'Ulaşım sorunu',
            ],
        ]);
    }

    /**
     * Özel istekli ilişki
     */
    public function withSpecialRequests(array $requests = null): static
    {
        return $this->state(fn (array $attributes) => [
            'ozel_istekler' => $requests ?? [
                'Mobilyalı teslim',
                'Tadilat yapılsın',
                'Klima takılsın',
            ],
        ]);
    }
}