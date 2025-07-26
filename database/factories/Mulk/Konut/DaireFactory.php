<?php

namespace Database\Factories\Mulk\Konut;

use App\Models\Mulk\Konut\Daire;

class DaireFactory extends KonutFactory
{
    protected $model = Daire::class;

    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'baslik' => $this->faker->randomElement(['2+1', '3+1', '4+1', '1+1']) . ' Daire - ' . $this->faker->city(),
            'metrekare' => $this->faker->numberBetween(80, 250),
            'fiyat' => $this->faker->numberBetween(500000, 2500000),
        ]);
    }

    /**
     * Site içi daire state
     */
    public function siteIci(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.15,
            'baslik' => str_replace('Daire', 'Site İçi Daire', $attributes['baslik']),
        ]);
    }

    /**
     * Asansörlü state
     */
    public function asansorlu(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiyat' => $attributes['fiyat'] * 1.1,
        ]);
    }

    /**
     * Geniş daire state
     */
    public function genisDaire(): static
    {
        return $this->state(fn (array $attributes) => [
            'metrekare' => $this->faker->numberBetween(180, 350),
            'fiyat' => $this->faker->numberBetween(1200000, 4000000),
            'baslik' => str_replace(['2+1', '3+1'], ['4+1', '5+1'], $attributes['baslik']),
        ]);
    }
}