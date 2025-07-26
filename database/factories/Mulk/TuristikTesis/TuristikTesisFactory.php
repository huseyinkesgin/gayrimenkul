<?php

namespace Database\Factories\Mulk\TuristikTesis;

use App\Models\Mulk\TuristikTesis\TuristikTesis;
use Database\Factories\Mulk\BaseMulkFactory;

class TuristikTesisFactory extends BaseMulkFactory
{
    protected $model = TuristikTesis::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'metrekare' => $this->faker->numberBetween(500, 5000),
            'fiyat' => $this->faker->numberBetween(2000000, 20000000),
            'para_birimi' => $this->faker->randomElement(['TRY', 'USD', 'EUR']),
        ]);
    }

    /**
     * Deniz kenarı state
     */
    public function denizKenari(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.8,
            'baslik' => 'Deniz Kenarı ' . $attributes['baslik'],
        ]);
    }

    /**
     * Yüksek kapasite state
     */
    public function yuksekKapasite(): static
    {
        return $this->state(fn (array $attributes) => [
            'metrekare' => $this->faker->numberBetween(3000, 10000),
            'fiyat' => $this->faker->numberBetween(10000000, 50000000),
        ]);
    }

    /**
     * Lüks tesis state
     */
    public function luksTesis(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 2,
            'para_birimi' => $this->faker->randomElement(['USD', 'EUR']),
            'baslik' => 'Lüks ' . $attributes['baslik'],
        ]);
    }
}