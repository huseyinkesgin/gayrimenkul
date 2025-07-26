<?php

namespace Database\Factories\Mulk\Konut;

use App\Models\Mulk\Konut\Konut;
use Database\Factories\Mulk\BaseMulkFactory;

class KonutFactory extends BaseMulkFactory
{
    protected $model = Konut::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'metrekare' => $this->faker->numberBetween(80, 500),
            'fiyat' => $this->faker->numberBetween(400000, 3000000),
        ]);
    }

    /**
     * Lüks konut state
     */
    public function luks(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $this->faker->numberBetween(2000000, 10000000),
            'metrekare' => $this->faker->numberBetween(200, 800),
            'para_birimi' => $this->faker->randomElement(['USD', 'EUR']),
        ]);
    }

    /**
     * Deniz manzaralı state
     */
    public function denizManzarali(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.5,
            'baslik' => 'Deniz Manzaralı ' . $attributes['baslik'],
        ]);
    }

    /**
     * Yeni yapım state
     */
    public function yeniYapim(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.2,
            'baslik' => 'Sıfır ' . $attributes['baslik'],
        ]);
    }
}