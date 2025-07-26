<?php

namespace Database\Factories\Mulk\Arsa;

use App\Models\Mulk\Arsa\Arsa;
use Database\Factories\Mulk\BaseMulkFactory;

class ArsaFactory extends BaseMulkFactory
{
    protected $model = Arsa::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'metrekare' => $this->faker->numberBetween(200, 5000),
            'fiyat' => $this->faker->numberBetween(200000, 3000000),
        ]);
    }

    /**
     * İmarlı arsa state
     */
    public function imarli(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $this->faker->numberBetween(500000, 2000000),
        ]);
    }

    /**
     * Büyük arsa state
     */
    public function buyukArsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'metrekare' => $this->faker->numberBetween(2000, 10000),
            'fiyat' => $this->faker->numberBetween(1000000, 5000000),
        ]);
    }

    /**
     * Köşe arsa state
     */
    public function koseArsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.2, // %20 daha pahalı
        ]);
    }
}