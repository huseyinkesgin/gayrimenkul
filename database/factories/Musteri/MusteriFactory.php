<?php

namespace Database\Factories\Musteri;

use App\Models\Musteri\Musteri;
use App\Models\Kisi\Kisi;
use App\Enums\MusteriTipi;
use Illuminate\Database\Eloquent\Factories\Factory;

class MusteriFactory extends Factory
{
    protected $model = Musteri::class;

    public function definition(): array
    {
        return [
            'kisi_id' => Kisi::factory(),
            'tip' => $this->faker->randomElement(MusteriTipi::cases()),
            'musteri_no' => 'MST-' . now()->year . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'kayit_tarihi' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'kaynak' => $this->faker->randomElement([
                'Website', 'Referans', 'Sosyal Medya', 'Gazete İlanı', 
                'Radyo', 'TV', 'Açık Kapı', 'Telefon', 'E-posta'
            ]),
            'referans_musteri_id' => null, // Sonradan set edilecek
            'potansiyel_deger' => $this->faker->optional(0.7)->numberBetween(100000, 10000000),
            'para_birimi' => $this->faker->randomElement(['TRY', 'USD', 'EUR']),
            'notlar' => $this->faker->optional(0.6)->paragraph(),
            'aktif_mi' => $this->faker->boolean(85),
            'siralama' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Bireysel müşteri state
     */
    public function bireysel(): static
    {
        return $this->state(fn (array $attributes) => [
            'tip' => MusteriTipi::BIREYSEL,
        ]);
    }

    /**
     * Kurumsal müşteri state
     */
    public function kurumsal(): static
    {
        return $this->state(fn (array $attributes) => [
            'tip' => MusteriTipi::KURUMSAL,
        ]);
    }

    /**
     * Aktif müşteri state
     */
    public function aktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'aktif_mi' => true,
            'kayit_tarihi' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Yüksek potansiyelli müşteri state
     */
    public function yuksekPotansiyel(): static
    {
        return $this->state(fn (array $attributes) => [
            'potansiyel_deger' => $this->faker->numberBetween(2000000, 10000000),
            'para_birimi' => $this->faker->randomElement(['USD', 'EUR']),
        ]);
    }

    /**
     * Referanslı müşteri state
     */
    public function referansli(): static
    {
        return $this->state(fn (array $attributes) => [
            'kaynak' => 'Referans',
            'referans_musteri_id' => Musteri::factory(),
        ]);
    }

    /**
     * VIP müşteri state
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'potansiyel_deger' => $this->faker->numberBetween(5000000, 20000000),
            'para_birimi' => $this->faker->randomElement(['USD', 'EUR']),
            'kaynak' => 'Referans',
            'aktif_mi' => true,
        ]);
    }

    /**
     * Yeni müşteri state
     */
    public function yeni(): static
    {
        return $this->state(fn (array $attributes) => [
            'kayit_tarihi' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'aktif_mi' => true,
        ]);
    }

    /**
     * Eski müşteri state
     */
    public function eski(): static
    {
        return $this->state(fn (array $attributes) => [
            'kayit_tarihi' => $this->faker->dateTimeBetween('-5 years', '-2 years'),
            'aktif_mi' => $this->faker->boolean(60),
        ]);
    }
}