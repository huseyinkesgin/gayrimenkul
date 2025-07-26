<?php

namespace Database\Factories\Mulk\Arsa;

use App\Models\Mulk\Arsa\KonutArsasi;

class KonutArsasiFactory extends ArsaFactory
{
    protected $model = KonutArsasi::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'baslik' => 'Konut Arsası - ' . $this->faker->city(),
            'metrekare' => $this->faker->numberBetween(300, 2000),
            'fiyat' => $this->faker->numberBetween(300000, 2000000),
        ]);
    }

    /**
     * Villa arsası state
     */
    public function villaArsasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'metrekare' => $this->faker->numberBetween(800, 3000),
            'fiyat' => $this->faker->numberBetween(800000, 3000000),
            'baslik' => 'Villa Arsası - ' . $this->faker->city(),
        ]);
    }

    /**
     * Deniz manzaralı state
     */
    public function denizManzarali(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 2, // %100 daha pahalı
            'baslik' => 'Deniz Manzaralı Konut Arsası - ' . $this->faker->city(),
        ]);
    }
}