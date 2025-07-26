<?php

namespace Database\Factories\Mulk\TuristikTesis;

use App\Models\Mulk\TuristikTesis\ButikOtel;

class ButikOtelFactory extends TuristikTesisFactory
{
    protected $model = ButikOtel::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'baslik' => 'Butik Otel - ' . $this->faker->city(),
            'metrekare' => $this->faker->numberBetween(800, 3000),
            'fiyat' => $this->faker->numberBetween(2000000, 12000000),
        ]);
    }

    /**
     * Tarihi bina state
     */
    public function tarihiBina(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.5,
            'baslik' => 'Tarihi Butik Otel - ' . $this->faker->city(),
        ]);
    }

    /**
     * Özel tasarım state
     */
    public function ozelTasarim(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.3,
            'baslik' => 'Özel Tasarım Butik Otel - ' . $this->faker->city(),
        ]);
    }
}