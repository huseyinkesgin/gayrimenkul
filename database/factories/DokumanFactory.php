<?php

namespace Database\Factories;

use App\Models\Dokuman;
use App\Models\User;
use App\Models\Mulk\BaseMulk;
use App\Enums\DokumanTipi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dokuman>
 */
class DokumanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Dokuman::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dokumanTipleri = DokumanTipi::cases();
        $tip = $this->faker->randomElement($dokumanTipleri);
        
        return [
            'url' => $this->faker->url(),
            'documentable_type' => BaseMulk::class,
            'documentable_id' => $this->faker->uuid(),
            'dokuman_tipi' => $tip->value,
            'baslik' => $this->faker->sentence(3),
            'aciklama' => $this->faker->optional()->paragraph(),
            'dosya_adi' => $this->faker->word() . '.' . $this->getExtensionForType($tip),
            'orijinal_dosya_adi' => $this->faker->word() . '.' . $this->getExtensionForType($tip),
            'dosya_boyutu' => $this->faker->numberBetween(1024, 10485760), // 1KB - 10MB
            'mime_type' => $this->getMimeTypeForType($tip),
            'dosya_uzantisi' => $this->getExtensionForType($tip),
            'dosya_hash' => $this->faker->sha256(),
            'versiyon' => $this->faker->numberBetween(1, 5),
            'erisim_sayisi' => $this->faker->numberBetween(0, 100),
            'son_erisim_tarihi' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'metadata' => [
                'sayfa_sayisi' => $this->faker->optional()->numberBetween(1, 50),
                'boyutlar' => [
                    'genislik' => $this->faker->optional()->numberBetween(100, 2000),
                    'yukseklik' => $this->faker->optional()->numberBetween(100, 2000),
                ],
                'olusturma_tarihi' => $this->faker->optional()->dateTime()?->format('Y-m-d H:i:s'),
            ],
            'gizli_mi' => $this->faker->boolean(10),
            'erisim_izinleri' => $this->faker->optional()->randomElements([
                'read', 'write', 'delete'
            ], $this->faker->numberBetween(1, 3)),
            'olusturan_id' => User::factory(),
            'guncelleyen_id' => $this->faker->optional()->randomElement([User::factory()]),
            'aktif_mi' => $this->faker->boolean(90),
        ];
    }

    /**
     * Dokuman tipine göre dosya uzantısı döndür
     */
    private function getExtensionForType(DokumanTipi $tip): string
    {
        return match ($tip) {
            DokumanTipi::TAPU => 'pdf',
            DokumanTipi::AUTOCAD => 'dwg',
            DokumanTipi::PROJE_RESMI => $this->faker->randomElement(['jpg', 'png', 'pdf']),
            DokumanTipi::RUHSAT => 'pdf',
            DokumanTipi::IMAR_PLANI => 'pdf',
            DokumanTipi::YAPI_KULLANIM => 'pdf',
            DokumanTipi::ISYERI_ACMA => 'pdf',
            DokumanTipi::CEVRE_IZNI => 'pdf',
            DokumanTipi::YANGIN_RAPORU => 'pdf',
            DokumanTipi::DIGER => $this->faker->randomElement(['pdf', 'doc', 'docx', 'jpg', 'png']),
        };
    }

    /**
     * Dokuman tipine göre MIME tipi döndür
     */
    private function getMimeTypeForType(DokumanTipi $tip): string
    {
        $extension = $this->getExtensionForType($tip);
        
        return match ($extension) {
            'pdf' => 'application/pdf',
            'dwg' => 'application/acad',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
    }

    /**
     * PDF dokuman state'i
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'dokuman_tipi' => DokumanTipi::TAPU->value,
            'dosya_adi' => $this->faker->word() . '.pdf',
            'orijinal_dosya_adi' => $this->faker->word() . '.pdf',
            'mime_type' => 'application/pdf',
            'dosya_uzantisi' => 'pdf',
            'metadata' => [
                'sayfa_sayisi' => $this->faker->numberBetween(1, 20),
                'olusturma_tarihi' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * DWG dokuman state'i
     */
    public function dwg(): static
    {
        return $this->state(fn (array $attributes) => [
            'dokuman_tipi' => DokumanTipi::AUTOCAD->value,
            'dosya_adi' => $this->faker->word() . '.dwg',
            'orijinal_dosya_adi' => $this->faker->word() . '.dwg',
            'mime_type' => 'application/acad',
            'dosya_uzantisi' => 'dwg',
            'metadata' => [
                'boyutlar' => [
                    'genislik' => $this->faker->numberBetween(1000, 5000),
                    'yukseklik' => $this->faker->numberBetween(1000, 5000),
                ],
                'cad_versiyon' => 'AutoCAD 2021',
            ],
        ]);
    }

    /**
     * Büyük dosya state'i
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'dosya_boyutu' => $this->faker->numberBetween(50000000, 100000000), // 50MB - 100MB
        ]);
    }

    /**
     * Küçük dosya state'i
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'dosya_boyutu' => $this->faker->numberBetween(1024, 100000), // 1KB - 100KB
        ]);
    }

    /**
     * Yüksek erişim sayısı state'i
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'erisim_sayisi' => $this->faker->numberBetween(100, 1000),
            'son_erisim_tarihi' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Gizli dokuman state'i
     */
    public function confidential(): static
    {
        return $this->state(fn (array $attributes) => [
            'gizli_mi' => true,
        ]);
    }

    /**
     * Gizli dokuman state'i (alias)
     */
    public function gizli(): static
    {
        return $this->confidential();
    }

    /**
     * Tapu dokuman state'i
     */
    public function tapu(): static
    {
        return $this->state(fn (array $attributes) => [
            'dokuman_tipi' => DokumanTipi::TAPU->value,
            'dosya_adi' => $this->faker->word() . '.pdf',
            'orijinal_dosya_adi' => $this->faker->word() . '.pdf',
            'mime_type' => 'application/pdf',
            'dosya_uzantisi' => 'pdf',
        ]);
    }

    /**
     * AutoCAD dokuman state'i
     */
    public function autocad(): static
    {
        return $this->dwg(); // dwg() methodunu kullan
    }

    /**
     * Taslak dokuman state'i
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'versiyon' => 1,
            'aktif_mi' => false,
        ]);
    }

    /**
     * Onaylanmış dokuman state'i
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'aktif_mi' => true,
        ]);
    }

    /**
     * Eski versiyon state'i
     */
    public function oldVersion(): static
    {
        return $this->state(fn (array $attributes) => [
            'versiyon' => $this->faker->numberBetween(1, 3),
            'son_guncelleme_tarihi' => $this->faker->dateTimeBetween('-6 months', '-1 month'),
        ]);
    }

    /**
     * Yeni versiyon state'i
     */
    public function newVersion(): static
    {
        return $this->state(fn (array $attributes) => [
            'versiyon' => $this->faker->numberBetween(5, 10),
            'son_guncelleme_tarihi' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}