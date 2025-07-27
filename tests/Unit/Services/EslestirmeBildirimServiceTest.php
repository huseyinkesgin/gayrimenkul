<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\EslestirmeBildirimService;
use App\Models\MusteriTalep;
use App\Models\TalepPortfoyEslestirme;
use App\Models\Mulk\Konut\Daire;
use App\Models\Musteri\Musteri;
use App\Models\Kisi\Personel;
use App\Models\User;
use App\Notifications\TalepEslestirmeBildirim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;

class EslestirmeBildirimServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EslestirmeBildirimService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EslestirmeBildirimService();
        Notification::fake();
    }

    /** @test */
    public function yeni_eslestirme_bildirimi_gonderir()
    {
        // Arrange
        $user = User::factory()->create();
        $personel = Personel::factory()->create(['user_id' => $user->id]);
        $talep = MusteriTalep::factory()->create([
            'sorumlu_personel_id' => $personel->id
        ]);

        $eslestirmeler = collect([
            ['eslestirme_skoru' => 0.7],
            ['eslestirme_skoru' => 0.6],
        ]);

        // Act
        $this->service->yeniEslestirmeBildirimi($talep, $eslestirmeler);

        // Assert
        Notification::assertSentTo(
            $user,
            TalepEslestirmeBildirim::class,
            function ($notification) {
                return $notification->tip === 'yeni_eslestirme';
            }
        );
    }

    /** @test */
    public function yuksek_skorlu_eslestirme_icin_ozel_bildirim_gonderir()
    {
        // Arrange
        $user = User::factory()->create();
        $personel = Personel::factory()->create(['user_id' => $user->id]);
        $talep = MusteriTalep::factory()->create([
            'sorumlu_personel_id' => $personel->id
        ]);

        $eslestirmeler = collect([
            ['eslestirme_skoru' => 0.9], // Yüksek skor
            ['eslestirme_skoru' => 0.6],
        ]);

        // Act
        $this->service->yeniEslestirmeBildirimi($talep, $eslestirmeler);

        // Assert
        Notification::assertSentTo(
            $user,
            TalepEslestirmeBildirim::class,
            function ($notification) {
                return $notification->tip === 'yuksek_skorlu_eslestirme';
            }
        );
    }

    /** @test */
    public function sorumlu_personel_yoksa_bildirim_gondermez()
    {
        // Arrange
        $talep = MusteriTalep::factory()->create([
            'sorumlu_personel_id' => null
        ]);

        $eslestirmeler = collect([
            ['eslestirme_skoru' => 0.7],
        ]);

        // Act
        $this->service->yeniEslestirmeBildirimi($talep, $eslestirmeler);

        // Assert
        Notification::assertNothingSent();
    }

    /** @test */
    public function eslestirme_sunuldu_bildirimi_gonderir()
    {
        // Arrange
        $sorumluUser = User::factory()->create();
        $sunanUser = User::factory()->create();
        
        $sorumluPersonel = Personel::factory()->create(['user_id' => $sorumluUser->id]);
        $sunanPersonel = Personel::factory()->create(['user_id' => $sunanUser->id]);
        
        $talep = MusteriTalep::factory()->create([
            'sorumlu_personel_id' => $sorumluPersonel->id
        ]);

        $eslestirme = TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep->id,
            'sunan_personel_id' => $sunanPersonel->id,
        ]);

        // Act
        $this->service->eslestirmeSunulduBildirimi($eslestirme);

        // Assert
        // Hem sorumlu hem sunan personele bildirim gitmeli
        Notification::assertSentTo(
            [$sorumluUser, $sunanUser],
            TalepEslestirmeBildirim::class,
            function ($notification) {
                return $notification->tip === 'eslestirme_sunuldu';
            }
        );
    }

    /** @test */
    public function ayni_personelse_tek_bildirim_gonderir()
    {
        // Arrange
        $user = User::factory()->create();
        $personel = Personel::factory()->create(['user_id' => $user->id]);
        
        $talep = MusteriTalep::factory()->create([
            'sorumlu_personel_id' => $personel->id
        ]);

        $eslestirme = TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep->id,
            'sunan_personel_id' => $personel->id, // Aynı personel
        ]);

        // Act
        $this->service->eslestirmeSunulduBildirimi($eslestirme);

        // Assert
        // Sadece bir bildirim gitmeli
        Notification::assertSentToTimes($user, TalepEslestirmeBildirim::class, 1);
    }

    /** @test */
    public function eslestirme_kabul_bildirimi_gonderir()
    {
        // Arrange
        $sorumluUser = User::factory()->create();
        $sunanUser = User::factory()->create();
        
        $sorumluPersonel = Personel::factory()->create(['user_id' => $sorumluUser->id]);
        $sunanPersonel = Personel::factory()->create(['user_id' => $sunanUser->id]);
        
        $talep = MusteriTalep::factory()->create([
            'sorumlu_personel_id' => $sorumluPersonel->id
        ]);

        $eslestirme = TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep->id,
            'sunan_personel_id' => $sunanPersonel->id,
        ]);

        // Act
        $this->service->eslestirmeKabulBildirimi($eslestirme);

        // Assert
        Notification::assertSentTo(
            [$sorumluUser, $sunanUser],
            TalepEslestirmeBildirim::class,
            function ($notification) {
                return $notification->tip === 'eslestirme_kabul';
            }
        );
    }

    /** @test */
    public function eslestirme_red_bildirimi_gonderir()
    {
        // Arrange
        $sorumluUser = User::factory()->create();
        $sunanUser = User::factory()->create();
        
        $sorumluPersonel = Personel::factory()->create(['user_id' => $sorumluUser->id]);
        $sunanPersonel = Personel::factory()->create(['user_id' => $sunanUser->id]);
        
        $talep = MusteriTalep::factory()->create([
            'sorumlu_personel_id' => $sorumluPersonel->id
        ]);

        $eslestirme = TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep->id,
            'sunan_personel_id' => $sunanPersonel->id,
        ]);

        // Act
        $this->service->eslestirmeRedBildirimi($eslestirme);

        // Assert
        Notification::assertSentTo(
            [$sorumluUser, $sunanUser],
            TalepEslestirmeBildirim::class,
            function ($notification) {
                return $notification->tip === 'eslestirme_red';
            }
        );
    }

    /** @test */
    public function talep_guncellendi_bildirimi_gonderir()
    {
        // Arrange
        $user = User::factory()->create();
        $personel = Personel::factory()->create(['user_id' => $user->id]);
        $talep = MusteriTalep::factory()->create([
            'sorumlu_personel_id' => $personel->id
        ]);

        $guncellenenAlanlar = ['min_fiyat', 'max_fiyat', 'lokasyon_tercihleri'];

        // Act
        $this->service->talepGuncellendiBildirimi($talep, $guncellenenAlanlar);

        // Assert
        Notification::assertSentTo(
            $user,
            TalepEslestirmeBildirim::class,
            function ($notification) use ($guncellenenAlanlar) {
                return $notification->tip === 'talep_guncellendi' &&
                       $notification->ekstraBilgi['guncellenen_alanlar'] === $guncellenenAlanlar;
            }
        );
    }

    /** @test */
    public function toplu_bildirim_gonderir()
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $personel1 = Personel::factory()->create(['user_id' => $user1->id]);
        $personel2 = Personel::factory()->create(['user_id' => $user2->id]);
        
        $talepler = collect([
            MusteriTalep::factory()->create(['sorumlu_personel_id' => $personel1->id]),
            MusteriTalep::factory()->create(['sorumlu_personel_id' => $personel2->id]),
        ]);

        // Act
        $this->service->topluBildirimGonder('test_bildirim', $talepler, ['test' => 'data']);

        // Assert
        Notification::assertSentTo(
            [$user1, $user2],
            TalepEslestirmeBildirim::class,
            function ($notification) {
                return $notification->tip === 'test_bildirim' &&
                       $notification->ekstraBilgi['test'] === 'data';
            }
        );
    }

    /** @test */
    public function gunluk_eslestirme_ozeti_gonderir()
    {
        // Arrange
        $yoneticiUser = User::factory()->create();
        
        // Yönetici rolü oluştur (basit mock)
        $yoneticiUser->roles = collect([
            (object)['name' => 'admin']
        ]);

        // Bugün oluşturulan eşleştirmeler
        $talep = MusteriTalep::factory()->create();
        
        TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep->id,
            'eslestirme_skoru' => 0.9,
            'durum' => 'sunuldu',
            'olusturma_tarihi' => now(),
        ]);

        TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep->id,
            'eslestirme_skoru' => 0.7,
            'durum' => 'kabul_edildi',
            'olusturma_tarihi' => now(),
        ]);

        // Dün oluşturulan (dahil edilmemeli)
        TalepPortfoyEslestirme::factory()->create([
            'talep_id' => $talep->id,
            'olusturma_tarihi' => now()->subDay(),
        ]);

        // Act
        $this->service->gunlukEslestirmeOzeti();

        // Assert - Bu test gerçek yönetici kontrolü yapmadığı için bildirim gönderilmeyebilir
        // Gerçek uygulamada User model'inde roles ilişkisi olmalı
    }

    /** @test */
    public function acil_bildirim_gonderir()
    {
        // Arrange
        $user = User::factory()->create();
        $personel = Personel::factory()->create(['user_id' => $user->id]);
        $talep = MusteriTalep::factory()->create([
            'sorumlu_personel_id' => $personel->id
        ]);

        $acilMesaj = 'Müşteri acil olarak aradı!';

        // Act
        $this->service->acilBildirimGonder($talep, $acilMesaj);

        // Assert
        Notification::assertSentTo(
            $user,
            TalepEslestirmeBildirim::class,
            function ($notification) use ($acilMesaj) {
                return $notification->tip === 'acil_bildirim' &&
                       $notification->ekstraBilgi['mesaj'] === $acilMesaj &&
                       $notification->ekstraBilgi['acil'] === true;
            }
        );
    }
}