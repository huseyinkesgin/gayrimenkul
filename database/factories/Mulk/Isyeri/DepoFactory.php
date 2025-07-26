<?php

namespace Database\Factories\Mulk\Isyeri;

use App\Models\Mulk\Isyeri\Depo;

class DepoFactory extends IsyeriFactory
{
    protected $model = Depo::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'baslik' => 'Depo - ' . $this->faker->city(),
            'metrekare' => $this->faker->numberBetween(500, 5000),
            'fiyat' => $this->faker->numberBetween(800000, 8000000),
        ]);
    }

    /**
     * Soğuk hava deposu state
     */
    public function sogukHavaDeposu(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.6,
            'baslik' => 'Soğuk Hava Deposu - ' . $this->faker->city(),
        ]);
    }

    /**
     * Çok rampali state
     */
    public function cokRampali(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.2,
            'baslik' => 'Çok Rampalı Depo - ' . $this->faker->city(),
        ]);
    }

    /**
     * Yüksek raf sistemi state
     */
    public function yuksekRafSistemi(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.15,
        ]);
    }
}