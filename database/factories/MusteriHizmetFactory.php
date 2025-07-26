<?php

namespace Database\Factories;

use App\Models\MusteriHizmet;
use App\Models\Musteri\Musteri;
use App\Models\User;
use App\Models\Mulk\BaseMulk;
use App\Enums\HizmetTipi;
use App\Enums\HizmetSonucu;
use App\Enums\DegerlendirmeTipi;
use Illuminate\Database\Eloquent\Factories\Factory;

class MusteriHizmetFactory extends Factory
{
    protected $model = MusteriHizmet::class;

    public function definition(): array
    {
        $hizmetTarihi = $this->faker->dateTimeBetween('-6 months', 'now');
        $bitisTarihi = $this->faker->boolean(70) ? 
            $this->faker->dateTimeBetween($hizmetTarihi, $hizmetTarihi->format('Y-m-d') . ' +4 hours') : 
            null;

        return [
            'musteri_id' => Musteri::factory(),
            'personel_id' => User::factory(),
            'hizmet_tipi' => $this->faker->randomElement(HizmetTipi::cases()),
            'hizmet_tarihi' => $hizmetTarihi,
            'bitis_tarihi' => $bitisTarihi,
            'lokasyon' => $this->faker->optional(0.6)->address(),
            'katilimcilar' => $this->faker->optional(0.4)->randomElements([
                $this->faker->name(),
                $this->faker->name(),
                $this->faker->name(),
            ], $this->faker->numberBetween(1, 3)),
            'aciklama' => $this->faker->optional(0.8)->paragraph(),
            'sonuc' => $this->faker->optional(0.7)->paragraph(),
            'sonuc_tipi' => $this->faker->optional(0.7)->randomElement(HizmetSonucu::cases()),
            'degerlendirme' => $this->faker->optional(0.5)->randomElement([
                [
                    'tip' => DegerlendirmeTipi::OLUMLU->value,
                    'puan' => $this->faker->numberBetween(7, 10),
                    'notlar' => $this->faker->sentence(),
                    'tarih' => now()->toISOString(),
                ],
                [
                    'tip' => DegerlendirmeTipi::OLUMSUZ->value,
                    'puan' => $this->faker->numberBetween(1, 4),
                    'notlar' => $this->faker->sentence(),
                    'tarih' => now()->toISOString(),
                ],
                [
                    'tip' => DegerlendirmeTipi::NOTR->value,
                    'puan' => $this->faker->numberBetween(4, 7),
                    'notlar' => $this->faker->sentence(),
                    'tarih' => now()->toISOString(),
                ],
            ]),
            'sure_dakika' => $this->faker->optional(0.8)->numberBetween(15, 240),
            'mulk_id' => $this->faker->optional(0.3)->randomElement([
                BaseMulk::factory(),
                null
            ]),
            'mulk_type' => function (array $attributes) {
                return $attributes['mulk_id'] ? $this->faker->randomElement([
                    'App\\Models\\Mulk\\Arsa\\TicariArsa',
                    'App\\Models\\Mulk\\Isyeri\\Ofis',
                    'App\\Models\\Mulk\\Konut\\Daire',
                ]) : null;
            },
            'takip_tarihi' => $this->faker->optional(0.3)->dateTimeBetween('now', '+1 month'),
            'takip_notu' => $this->faker->optional(0.3)->sentence(),
            'maliyet' => $this->faker->optional(0.2)->randomFloat(2, 50, 1000),
            'para_birimi' => $this->faker->randomElement(['TRY', 'USD', 'EUR']),
            'etiketler' => $this->faker->optional(0.4)->randomElements([
                'Acil', 'Önemli', 'Takip Gerekli', 'Başarılı', 'Zor Müşteri', 'VIP'
            ], $this->faker->numberBetween(1, 3)),
            'dosyalar' => $this->faker->optional(0.2)->randomElements([
                [
                    'name' => 'dokuman1.pdf',
                    'path' => '/uploads/hizmetler/dokuman1.pdf',
                    'uploaded_at' => now()->toISOString(),
                ],
                [
                    'name' => 'resim1.jpg',
                    'path' => '/uploads/hizmetler/resim1.jpg',
                    'uploaded_at' => now()->toISOString(),
                ],
            ], $this->faker->numberBetween(1, 2)),
            'aktif_mi' => $this->faker->boolean(95),
            'siralama' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Telefon görüşmesi hizmeti
     */
    public function telefon(): static
    {
        return $this->state(fn (array $attributes) => [
            'hizmet_tipi' => HizmetTipi::TELEFON,
            'sure_dakika' => $this->faker->numberBetween(5, 60),
            'lokasyon' => null,
        ]);
    }

    /**
     * Toplantı hizmeti
     */
    public function toplanti(): static
    {
        return $this->state(fn (array $attributes) => [
            'hizmet_tipi' => HizmetTipi::TOPLANTI,
            'sure_dakika' => $this->faker->numberBetween(30, 180),
            'lokasyon' => $this->faker->address(),
            'katilimcilar' => $this->faker->randomElements([
                $this->faker->name(),
                $this->faker->name(),
                $this->faker->name(),
            ], $this->faker->numberBetween(2, 3)),
        ]);
    }

    /**
     * Email hizmeti
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'hizmet_tipi' => HizmetTipi::EMAIL,
            'sure_dakika' => $this->faker->numberBetween(5, 30),
            'lokasyon' => null,
            'katilimcilar' => null,
        ]);
    }

    /**
     * Ziyaret hizmeti
     */
    public function ziyaret(): static
    {
        return $this->state(fn (array $attributes) => [
            'hizmet_tipi' => HizmetTipi::ZIYARET,
            'sure_dakika' => $this->faker->numberBetween(60, 240),
            'lokasyon' => $this->faker->address(),
        ]);
    }

    /**
     * Başarılı hizmet
     */
    public function basarili(): static
    {
        return $this->state(fn (array $attributes) => [
            'sonuc_tipi' => HizmetSonucu::BASARILI,
            'degerlendirme' => [
                'tip' => DegerlendirmeTipi::OLUMLU->value,
                'puan' => $this->faker->numberBetween(8, 10),
                'notlar' => 'Başarılı görüşme gerçekleştirildi.',
                'tarih' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Başarısız hizmet
     */
    public function basarisiz(): static
    {
        return $this->state(fn (array $attributes) => [
            'sonuc_tipi' => HizmetSonucu::BASARISIZ,
            'degerlendirme' => [
                'tip' => DegerlendirmeTipi::OLUMSUZ->value,
                'puan' => $this->faker->numberBetween(1, 3),
                'notlar' => 'Görüşme başarısız oldu.',
                'tarih' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Takip gereken hizmet
     */
    public function takipGerekli(): static
    {
        return $this->state(fn (array $attributes) => [
            'sonuc_tipi' => HizmetSonucu::TAKIP_GEREKLI,
            'takip_tarihi' => $this->faker->dateTimeBetween('+1 day', '+1 week'),
            'takip_notu' => 'Müşteri ile tekrar görüşülmeli.',
        ]);
    }

    /**
     * Mülk ile ilgili hizmet
     */
    public function withProperty(): static
    {
        return $this->state(fn (array $attributes) => [
            'mulk_id' => BaseMulk::factory(),
            'mulk_type' => 'App\\Models\\Mulk\\Konut\\Daire',
        ]);
    }

    /**
     * Yüksek maliyetli hizmet
     */
    public function yuksekMaliyet(): static
    {
        return $this->state(fn (array $attributes) => [
            'maliyet' => $this->faker->randomFloat(2, 500, 2000),
            'para_birimi' => 'TRY',
        ]);
    }

    /**
     * Etiketli hizmet
     */
    public function withTags(array $tags = null): static
    {
        return $this->state(fn (array $attributes) => [
            'etiketler' => $tags ?? ['Önemli', 'VIP', 'Acil'],
        ]);
    }

    /**
     * Dosyalı hizmet
     */
    public function withFiles(): static
    {
        return $this->state(fn (array $attributes) => [
            'dosyalar' => [
                [
                    'name' => 'toplanti_notlari.pdf',
                    'path' => '/uploads/hizmetler/toplanti_notlari.pdf',
                    'uploaded_at' => now()->toISOString(),
                ],
                [
                    'name' => 'mulk_fotograflari.zip',
                    'path' => '/uploads/hizmetler/mulk_fotograflari.zip',
                    'uploaded_at' => now()->toISOString(),
                ],
            ],
        ]);
    }

    /**
     * Son 30 gün içinde yapılan hizmet
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'hizmet_tarihi' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Bugün yapılan hizmet
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'hizmet_tarihi' => today()->addHours($this->faker->numberBetween(9, 17)),
        ]);
    }
}