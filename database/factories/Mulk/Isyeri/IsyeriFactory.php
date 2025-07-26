<?php

namespace Database\Factories\Mulk\Isyeri;

use App\Models\Mulk\Isyeri\Isyeri;
use Database\Factories\Mulk\BaseMulkFactory;

class IsyeriFactory extends BaseMulkFactory
{
    protected $model = Isyeri::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'metrekare' => $this->faker->numberBetween(100, 2000),
            'fiyat' => $this->faker->numberBetween(300000, 5000000),
        ]);
    }

    /**
     * Yüksek tavan state
     */
    public function yuksekTavan(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.15,
        ]);
    }

    /**
     * Geniş açık alan state
     */
    public function genisAcikAlan(): static
    {
        return $this->state(fn (array $attributes) => [
            'metrekare' => $this->faker->numberBetween(1000, 5000),
            'fiyat' => $attributes['fiyat'] * 1.2,
        ]);
    }

    /**
     * Merkezi konum state
     */
    public function merkeziKonum(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.3,
        ]);
    }
}