<?php

namespace Database\Factories;

use App\Models\Dokuman;
use App\Enums\DokumanTipi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DokumanFactory extends Factory
{
    protected $model = Dokuman::class;

    public function definition(): array
    {
        $dokumanTipi = $this->faker->randomElement(DokumanTipi::cases());
        $mimeTypes = $dokumanTipi->allowedMimeTypes();
        $selectedMimeType = $this->faker->randomElement($mimeTypes);
        
        // MIME type'a göre dosya uzantısı belirle
        $extension = match($selectedMimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/dwg' => 'dwg',
            'application/dxf' => 'dxf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            default => 'pdf'
        };

        $fileName = $this->faker->slug(3) . '.' . $extension;
        $orijinalAd = $this->faker->words(3, true) . '.' . $extension;

        return [
            'url' => 'dokumanlar/' . $this->faker->year . '/' . $fileName,
            'documentable_id' => $this->faker->uuid,
            'documentable_type' => $this->faker->randomElement([
                'App\\Models\\Mulk\\BaseMulk',
                'App\\Models\\Musteri\\Musteri',
                'App\\Models\\Kisi\\Personel'
            ]),
            'dokuman_tipi' => $dokumanTipi,
            'baslik' => $this->faker->sentence(4),
            'aciklama' => $this->faker->optional()->paragraph,
            'dosya_adi' => $fileName,
            'orijinal_dosya_adi' => $orijinalAd,
            'dosya_boyutu' => $this->faker->numberBetween(1024, 10485760), // 1KB - 10MB
            'mime_type' => $selectedMimeType,
            'dosya_uzantisi' => $extension,
            'dosya_hash' => $this->faker->sha256,
            'versiyon' => 1,
            'ana_dokuman_id' => null,
            'gizli_mi' => $this->faker->boolean(20), // %20 gizli
            'erisim_izinleri' => $this->faker->optional()->randomElements(
                [User::factory()->create()->id, User::factory()->create()->id],
                $this->faker->numberBetween(0, 2)
            ),
            'metadata' => [
                'upload_time' => $this->faker->dateTimeThisYear->toISOString(),
                'original_name' => $orijinalAd,
                'size' => $this->faker->numberBetween(1024, 10485760),
                'mime_type' => $selectedMimeType,
            ],
            'son_erisim_tarihi' => $this->faker->optional()->dateTimeThisMonth,
            'erisim_sayisi' => $this->faker->numberBetween(0, 100),
            'olusturan_id' => User::factory(),
            'guncelleyen_id' => null,
            'aktif_mi' => true,
            'olusturma_tarihi' => $this->faker->dateTimeThisYear,
            'guncelleme_tarihi' => $this->faker->dateTimeThisYear,
        ];
    }

    /**
     * Tapu dökümanı state
     */
    public function tapu(): static
    {
        return $this->state(fn (array $attributes) => [
            'dokuman_tipi' => DokumanTipi::TAPU,
            'baslik' => 'Tapu Senedi - ' . $this->faker->city,
            'mime_type' => 'application/pdf',
            'dosya_uzantisi' => 'pdf',
            'gizli_mi' => false, // Tapu genelde gizli değil
        ]);
    }

    /**
     * AutoCAD dosyası state
     */
    public function autocad(): static
    {
        return $this->state(fn (array $attributes) => [
            'dokuman_tipi' => DokumanTipi::AUTOCAD,
            'baslik' => 'Teknik Çizim - ' . $this->faker->words(2, true),
            'mime_type' => $this->faker->randomElement(['application/dwg', 'application/dxf']),
            'dosya_uzantisi' => $this->faker->randomElement(['dwg', 'dxf']),
            'dosya_boyutu' => $this->faker->numberBetween(5242880, 52428800), // 5MB - 50MB
        ]);
    }

    /**
     * Proje resmi state
     */
    public function projeResmi(): static
    {
        return $this->state(fn (array $attributes) => [
            'dokuman_tipi' => DokumanTipi::PROJE_RESMI,
            'baslik' => 'Proje Görseli - ' . $this->faker->words(2, true),
            'mime_type' => $this->faker->randomElement(['image/jpeg', 'image/png']),
            'dosya_uzantisi' => $this->faker->randomElement(['jpg', 'png']),
            'metadata' => [
                'upload_time' => $this->faker->dateTimeThisYear->toISOString(),
                'width' => $this->faker->numberBetween(1920, 4096),
                'height' => $this->faker->numberBetween(1080, 2160),
                'type' => 2, // JPEG
            ],
        ]);
    }

    /**
     * Ruhsat dökümanı state
     */
    public function ruhsat(): static
    {
        return $this->state(fn (array $attributes) => [
            'dokuman_tipi' => DokumanTipi::RUHSAT,
            'baslik' => 'Ruhsat Belgesi - ' . $this->faker->words(2, true),
            'mime_type' => 'application/pdf',
            'dosya_uzantisi' => 'pdf',
            'metadata' => [
                'ruhsat_no' => $this->faker->numerify('RUH-####-####'),
                'gecerlilik_tarihi' => $this->faker->dateTimeBetween('now', '+5 years')->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Gizli döküman state
     */
    public function gizli(): static
    {
        return $this->state(fn (array $attributes) => [
            'gizli_mi' => true,
            'erisim_izinleri' => [User::factory()->create()->id],
        ]);
    }

    /**
     * Versiyonlu döküman state
     */
    public function versiyonlu(): static
    {
        return $this->state(fn (array $attributes) => [
            'versiyon' => $this->faker->numberBetween(2, 5),
            'ana_dokuman_id' => Dokuman::factory()->create()->id,
        ]);
    }

    /**
     * Çok erişilen döküman state
     */
    public function cokErisilen(): static
    {
        return $this->state(fn (array $attributes) => [
            'erisim_sayisi' => $this->faker->numberBetween(100, 1000),
            'son_erisim_tarihi' => $this->faker->dateTimeThisWeek,
        ]);
    }

    /**
     * Büyük dosya state
     */
    public function buyukDosya(): static
    {
        return $this->state(fn (array $attributes) => [
            'dosya_boyutu' => $this->faker->numberBetween(20971520, 52428800), // 20MB - 50MB
        ]);
    }
}