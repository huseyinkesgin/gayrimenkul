<?php

namespace Database\Factories\Mulk\Konut;

use App\Models\Mulk\Konut\Villa;

class VillaFactory extends KonutFactory
{
    protected $model = Villa::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'baslik' => 'Villa - ' . $this->faker->city(),
            'metrekare' => $this->faker->numberBetween(200, 800),
            'fiyat' => $this->faker->numberBetween(1500000, 8000000),
        ]);
    }

    /**
     * Havuzlu villa state
     */
    public function havuzlu(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.3,
            'baslik' => 'Havuzlu Villa - ' . $this->faker->city(),
        ]);
    }

    /**
     * Büyük bahçeli state
     */
    public function buyukBahceli(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.2,
            'baslik' => 'Büyük Bahçeli Villa - ' . $this->faker->city(),
        ]);
    }

    /**
     * Müstakil villa state
     */
    public function mustakil(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.4,
            'metrekare' => $this->faker->numberBetween(300, 1000),
            'baslik' => 'Müstakil Villa - ' . $this->faker->city(),
        ]);
    }
}