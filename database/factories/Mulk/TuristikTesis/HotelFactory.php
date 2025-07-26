<?php

namespace Database\Factories\Mulk\TuristikTesis;

use App\Models\Mulk\TuristikTesis\Hotel;

class HotelFactory extends TuristikTesisFactory
{
    protected $model = Hotel::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'baslik' => 'Hotel - ' . $this->faker->city(),
            'metrekare' => $this->faker->numberBetween(2000, 15000),
            'fiyat' => $this->faker->numberBetween(5000000, 50000000),
        ]);
    }

    /**
     * 5 yıldızlı hotel state
     */
    public function besYildizli(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 2.5,
            'metrekare' => $this->faker->numberBetween(8000, 25000),
            'baslik' => '5 Yıldızlı Hotel - ' . $this->faker->city(),
            'para_birimi' => $this->faker->randomElement(['USD', 'EUR']),
        ]);
    }

    /**
     * Şehir hoteli state
     */
    public function sehirHoteli(): static
    {
        return $this->state(fn (array $attributes) => [
            'metrekare' => $this->faker->numberBetween(1500, 8000),
            'fiyat' => $this->faker->numberBetween(3000000, 25000000),
            'baslik' => 'Şehir Hoteli - ' . $this->faker->city(),
        ]);
    }
}