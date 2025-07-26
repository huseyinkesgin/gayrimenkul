<?php

namespace Database\Factories\Mulk\Isyeri;

use App\Models\Mulk\Isyeri\Fabrika;

class FabrikaFactory extends IsyeriFactory
{
    protected $model = Fabrika::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'baslik' => 'Fabrika - ' . $this->faker->city(),
            'metrekare' => $this->faker->numberBetween(1000, 10000),
            'fiyat' => $this->faker->numberBetween(1500000, 15000000),
        ]);
    }

    /**
     * Büyük üretim kapasiteli state
     */
    public function buyukUretimKapasiteli(): static
    {
        return $this->state(fn (array $attributes) => [
            'metrekare' => $this->faker->numberBetween(5000, 20000),
            'fiyat' => $this->faker->numberBetween(8000000, 30000000),
            'baslik' => 'Büyük Kapasiteli Fabrika - ' . $this->faker->city(),
        ]);
    }

    /**
     * Çevre dostu state
     */
    public function cevreDostu(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.25,
            'baslik' => 'Çevre Dostu Fabrika - ' . $this->faker->city(),
        ]);
    }

    /**
     * Yüksek teknoloji state
     */
    public function yuksekTeknoloji(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.4,
            'baslik' => 'Yüksek Teknoloji Fabrikası - ' . $this->faker->city(),
        ]);
    }
}