<?php

namespace Database\Factories\Mulk\Arsa;

use App\Models\Mulk\Arsa\TicariArsa;

class TicariArsaFactory extends ArsaFactory
{
    protected $model = TicariArsa::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'baslik' => 'Ticari Arsa - ' . $this->faker->city(),
            'metrekare' => $this->faker->numberBetween(500, 3000),
            'fiyat' => $this->faker->numberBetween(800000, 4000000),
        ]);
    }

    /**
     * Ana cadde cepheli state
     */
    public function anaCaddeCepheli(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.5, // %50 daha pahalı
            'baslik' => 'Ana Cadde Cepheli Ticari Arsa - ' . $this->faker->city(),
        ]);
    }

    /**
     * Yüksek ticari potansiyel state
     */
    public function yuksekTicariPotansiyel(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.3,
            'baslik' => 'Yüksek Potansiyelli Ticari Arsa - ' . $this->faker->city(),
        ]);
    }
}