<?php

namespace Database\Factories\Mulk\Isyeri;

use App\Models\Mulk\Isyeri\Ofis;

class OfisFactory extends IsyeriFactory
{
    protected $model = Ofis::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'baslik' => 'Ofis - ' . $this->faker->city(),
            'metrekare' => $this->faker->numberBetween(50, 1000),
            'fiyat' => $this->faker->numberBetween(200000, 3000000),
        ]);
    }

    /**
     * Plaza ofisi state
     */
    public function plazaOfisi(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.5,
            'baslik' => 'Plaza Ofisi - ' . $this->faker->city(),
        ]);
    }

    /**
     * Prestijli lokasyon state
     */
    public function prestijliLokasyon(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.8,
            'baslik' => 'Prestijli Lokasyon Ofis - ' . $this->faker->city(),
        ]);
    }

    /**
     * Geniş ofis state
     */
    public function genisOfis(): static
    {
        return $this->state(fn (array $attributes) => [
            'metrekare' => $this->faker->numberBetween(500, 2000),
            'fiyat' => $this->faker->numberBetween(1500000, 8000000),
        ]);
    }
}