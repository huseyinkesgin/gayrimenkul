<?php

namespace Database\Factories\Mulk\Arsa;

use App\Models\Mulk\Arsa\SanayiArsasi;

class SanayiArsasiFactory extends ArsaFactory
{
    protected $model = SanayiArsasi::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'baslik' => 'Sanayi Arsası - ' . $this->faker->city(),
            'metrekare' => $this->faker->numberBetween(1000, 10000),
            'fiyat' => $this->faker->numberBetween(500000, 3000000),
        ]);
    }

    /**
     * Organize sanayi bölgesi state
     */
    public function organizeSanayiBolgesi(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.4,
            'baslik' => 'OSB İçi Sanayi Arsası - ' . $this->faker->city(),
        ]);
    }

    /**
     * Büyük sanayi arsası state
     */
    public function buyukSanayiArsasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'metrekare' => $this->faker->numberBetween(5000, 20000),
            'fiyat' => $this->faker->numberBetween(2000000, 8000000),
        ]);
    }
}