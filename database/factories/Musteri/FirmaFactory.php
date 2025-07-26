<?php

namespace Database\Factories\Musteri;

use App\Models\Musteri\Firma;
use Illuminate\Database\Eloquent\Factories\Factory;

class FirmaFactory extends Factory
{
    protected $model = Firma::class;

    public function definition(): array
    {
        return [
            'unvan' => $this->faker->company() . ' ' . $this->faker->randomElement(['Ltd. Şti.', 'A.Ş.', 'San. ve Tic. Ltd. Şti.']),
            'ticaret_unvani' => $this->faker->optional(0.7)->company(),
            'vergi_no' => $this->faker->unique()->numerify('##########'),
            'vergi_dairesi' => $this->faker->city() . ' Vergi Dairesi',
            'mersis_no' => $this->faker->optional(0.6)->numerify('################'),
            'faaliyet_kodu' => $this->faker->optional(0.8)->numerify('##.##'),
            'telefon' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'website' => $this->faker->optional(0.7)->domainName(),
            'kuruluş_tarihi' => $this->faker->optional(0.8)->dateTimeBetween('-30 years', '-1 year'),
            'çalışan_sayisi' => $this->faker->optional(0.8)->numberBetween(1, 500),
            'sermaye' => $this->faker->optional(0.7)->numberBetween(10000, 5000000),
            'para_birimi' => $this->faker->randomElement(['TRY', 'USD', 'EUR']),
            'sektor' => $this->faker->randomElement([
                'İnşaat', 'Gayrimenkul', 'Teknoloji', 'Üretim', 'Hizmet',
                'Ticaret', 'Turizm', 'Sağlık', 'Eğitim', 'Finans'
            ]),
            'notlar' => $this->faker->optional(0.5)->paragraph(),
            'aktif_mi' => $this->faker->boolean(90),
            'siralama' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Büyük işletme state
     */
    public function buyukIsletme(): static
    {
        return $this->state(fn (array $attributes) => [
            'çalışan_sayisi' => $this->faker->numberBetween(250, 2000),
            'sermaye' => $this->faker->numberBetween(1000000, 50000000),
            'para_birimi' => $this->faker->randomElement(['USD', 'EUR']),
        ]);
    }

    /**
     * KOBİ state
     */
    public function kobi(): static
    {
        return $this->state(fn (array $attributes) => [
            'çalışan_sayisi' => $this->faker->numberBetween(10, 249),
            'sermaye' => $this->faker->numberBetween(50000, 1000000),
        ]);
    }

    /**
     * Mikro işletme state
     */
    public function mikroIsletme(): static
    {
        return $this->state(fn (array $attributes) => [
            'çalışan_sayisi' => $this->faker->numberBetween(1, 9),
            'sermaye' => $this->faker->numberBetween(10000, 100000),
        ]);
    }

    /**
     * Teknoloji firması state
     */
    public function teknoloji(): static
    {
        return $this->state(fn (array $attributes) => [
            'sektor' => 'Teknoloji',
            'website' => $this->faker->domainName(),
            'çalışan_sayisi' => $this->faker->numberBetween(5, 200),
        ]);
    }

    /**
     * İnşaat firması state
     */
    public function insaat(): static
    {
        return $this->state(fn (array $attributes) => [
            'sektor' => 'İnşaat',
            'çalışan_sayisi' => $this->faker->numberBetween(20, 500),
            'sermaye' => $this->faker->numberBetween(500000, 10000000),
        ]);
    }

    /**
     * Yeni kurulan firma state
     */
    public function yeniKurulan(): static
    {
        return $this->state(fn (array $attributes) => [
            'kuruluş_tarihi' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'çalışan_sayisi' => $this->faker->numberBetween(1, 20),
            'sermaye' => $this->faker->numberBetween(10000, 200000),
        ]);
    }

    /**
     * Köklü firma state
     */
    public function koklu(): static
    {
        return $this->state(fn (array $attributes) => [
            'kuruluş_tarihi' => $this->faker->dateTimeBetween('-50 years', '-20 years'),
            'çalışan_sayisi' => $this->faker->numberBetween(100, 1000),
            'sermaye' => $this->faker->numberBetween(2000000, 20000000),
        ]);
    }
}