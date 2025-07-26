<?php

namespace Database\Factories\Mulk;

use App\Models\Mulk\BaseMulk;
use Illuminate\Database\Eloquent\Factories\Factory;

abstract class BaseMulkFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'baslik' => $this->faker->sentence(3),
            'aciklama' => $this->faker->paragraph(3),
            'fiyat' => $this->faker->numberBetween(100000, 5000000),
            'para_birimi' => $this->faker->randomElement(['TRY', 'USD', 'EUR']),
            'metrekare' => $this->faker->numberBetween(50, 1000),
            'durum' => $this->faker->randomElement(['aktif', 'pasif', 'satildi', 'kiralandi']),
            'yayinlanma_tarihi' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'aktif_mi' => $this->faker->boolean(85),
            'siralama' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Aktif mülk state
     */
    public function aktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'durum' => 'aktif',
            'aktif_mi' => true,
            'yayinlanma_tarihi' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Satılmış mülk state
     */
    public function satilmis(): static
    {
        return $this->state(fn (array $attributes) => [
            'durum' => 'satildi',
            'aktif_mi' => false,
        ]);
    }

    /**
     * Yüksek fiyatlı mülk state
     */
    public function yuksekFiyatli(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $this->faker->numberBetween(2000000, 10000000),
            'para_birimi' => $this->faker->randomElement(['USD', 'EUR']),
        ]);
    }

    /**
     * Büyük metrekare state
     */
    public function buyukMetrekare(): static
    {
        return $this->state(fn (array $attributes) => [
            'metrekare' => $this->faker->numberBetween(500, 2000),
        ]);
    }

    /**
     * Yayınlanmamış state
     */
    public function yayinlanmamis(): static
    {
        return $this->state(fn (array $attributes) => [
            'yayinlanma_tarihi' => null,
            'durum' => 'pasif',
        ]);
    }
}