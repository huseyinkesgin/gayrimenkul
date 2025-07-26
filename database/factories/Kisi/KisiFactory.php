<?php

namespace Database\Factories\Kisi;

use App\Models\Kisi\Kisi;
use Illuminate\Database\Eloquent\Factories\Factory;

class KisiFactory extends Factory
{
    protected $model = Kisi::class;

    public function definition(): array
    {
        $cinsiyet = $this->faker->randomElement(['Erkek', 'Kadın']);
        
        return [
            'ad' => $cinsiyet === 'Erkek' ? $this->faker->firstNameMale() : $this->faker->firstNameFemale(),
            'soyad' => $this->faker->lastName(),
            'tc_kimlik_no' => $this->faker->unique()->numerify('###########'),
            'dogum_tarihi' => $this->faker->dateTimeBetween('-80 years', '-18 years'),
            'cinsiyet' => $cinsiyet,
            'dogum_yeri' => $this->faker->city(),
            'medeni_hali' => $this->faker->randomElement(['Bekar', 'Evli', 'Dul']),
            'email' => $this->faker->unique()->safeEmail(),
            'telefon' => $this->faker->phoneNumber(),
            'aktif_mi' => $this->faker->boolean(95),
        ];
    }

    /**
     * Erkek state
     */
    public function erkek(): static
    {
        return $this->state(fn (array $attributes) => [
            'cinsiyet' => 'Erkek',
            'ad' => $this->faker->firstNameMale(),
        ]);
    }

    /**
     * Kadın state
     */
    public function kadin(): static
    {
        return $this->state(fn (array $attributes) => [
            'cinsiyet' => 'Kadın',
            'ad' => $this->faker->firstNameFemale(),
        ]);
    }

    /**
     * Genç state (18-35 yaş)
     */
    public function genc(): static
    {
        return $this->state(fn (array $attributes) => [
            'dogum_tarihi' => $this->faker->dateTimeBetween('-35 years', '-18 years'),
            'medeni_hali' => $this->faker->randomElement(['Bekar', 'Evli']),
        ]);
    }

    /**
     * Orta yaş state (35-55 yaş)
     */
    public function ortaYas(): static
    {
        return $this->state(fn (array $attributes) => [
            'dogum_tarihi' => $this->faker->dateTimeBetween('-55 years', '-35 years'),
            'medeni_hali' => $this->faker->randomElement(['Evli', 'Dul']),
        ]);
    }

    /**
     * Yaşlı state (55+ yaş)
     */
    public function yasli(): static
    {
        return $this->state(fn (array $attributes) => [
            'dogum_tarihi' => $this->faker->dateTimeBetween('-80 years', '-55 years'),
            'medeni_hali' => $this->faker->randomElement(['Evli', 'Dul']),
        ]);
    }

    /**
     * Evli state
     */
    public function evli(): static
    {
        return $this->state(fn (array $attributes) => [
            'medeni_hali' => 'Evli',
        ]);
    }

    /**
     * Bekar state
     */
    public function bekar(): static
    {
        return $this->state(fn (array $attributes) => [
            'medeni_hali' => 'Bekar',
        ]);
    }
}