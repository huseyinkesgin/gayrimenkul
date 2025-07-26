<?php

namespace Database\Seeders;

use App\Models\Kisi\Departman;
use App\Models\Kisi\Kisi;
use App\Models\Kisi\Personel;
use App\Models\Kisi\PersonelRol;
use App\Models\Kisi\Pozisyon;
use App\Models\Lokasyon\Ilce;
use App\Models\Lokasyon\Sehir;
use App\Models\Musteri\Musteri;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GayrimenkulSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Test kullanıcısı oluştur
        $user = User::firstOrCreate([
            'email' => 'admin@gayrimenkul.com'
        ], [
            'id' => Str::uuid(),
            'name' => 'Admin User',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        //Sube verilerini ekle
        $subeId = Str::uuid();
        DB::table('sube')->insertOrIgnore([
            [
                'id' => $subeId,
                'ad' => 'Merkez Ofis',
                'kod' => 'MERKEZ',
                'telefon' => '0212 123 45 67',
                'email' => 'merkez@ofis.com',
                'aktif_mi' => true,
                'olusturma_tarihi' => now(),
                'guncelleme_tarihi' => now(),
            ],
        ]);
        // Sube kaydını al
        $sube = DB::table('sube')->where('id', $subeId)->first();

        Departman::insert([
            [
                'id' => Str::uuid(),
                'ad' => 'Danışman',
                'olusturma_tarihi' => now(),
                'guncelleme_tarihi' => now(),
            ],
            [
                'id' => Str::uuid(),
                'ad' => 'Broker',
                'olusturma_tarihi' => now(),
                'guncelleme_tarihi' => now(),
            ],
        ]);

        Pozisyon::insert([
            ['id' => Str::uuid(), 'ad' => 'Müdür', 'olusturma_tarihi' => now(), 'guncelleme_tarihi' => now()],
            ['id' => Str::uuid(), 'ad' => 'Sorumlu', 'olusturma_tarihi' => now(), 'guncelleme_tarihi' => now()],
            ['id' => Str::uuid(), 'ad' => 'Uzman', 'olusturma_tarihi' => now(), 'guncelleme_tarihi' => now()],
            ['id' => Str::uuid(), 'ad' => 'Asistan', 'olusturma_tarihi' => now(), 'guncelleme_tarihi' => now()],
        ]);

        // Personel rolleri verilerini ekle
        PersonelRol::insert([
            [
                'id' => Str::uuid(),
                'ad' => 'Danışman',
                'olusturma_tarihi' => now(),
                'guncelleme_tarihi' => now(),
            ],
            [
                'id' => Str::uuid(),
                'ad' => 'Broker',
                'olusturma_tarihi' => now(),
                'guncelleme_tarihi' => now(),
            ],
        ]);

        // Test kişisi oluştur
        $kisi = Kisi::firstOrCreate([
            'tc_kimlik_no' => '12345678901'
        ], [
            'id' => Str::uuid(),
            'ad' => 'Test',
            'soyad' => 'Müşteri',
            'email' => 'test@musteri.com',
            'telefon' => '0555 123 45 67',
            'dogum_tarihi' => now()->subYears(30),
        ]);

        $personel = Personel::firstOrCreate([
            'kisi_id' => $kisi->id,
            'sube_id' => $sube->id,
            'departman_id' => Departman::where('ad', 'Danışman')->first()->id,
            'pozisyon_id' => Pozisyon::where('ad', 'Uzman')->first()->id,
        ], [
            'id' => Str::uuid(),
            'personel_no' => 'P-' . str_pad(1, 6, '0', STR_PAD_LEFT),
            'calisma_durumu' => 'Aktif',
            'calisma_sekli' => 'Tam Zamanlı',
            'ise_baslama_tarihi' => now()->subYears(2),

            'aktif_mi' => true,
        ]);
        // Personel rolü ata
        $personel->roller()->sync([PersonelRol::where('ad', 'Danışman')->first()->id]);



        // Test müşterisi oluştur
        $musteri = Musteri::firstOrCreate([
            'kisi_id' => $kisi->id
        ], [
            'tip' => \App\Enums\MusteriTipi::BIREYSEL,
            'musteri_no' => 'M-' . str_pad(1, 6, '0', STR_PAD_LEFT),
            'aktif_mi' => true,
        ]);

        // Test şehri oluştur (eğer yoksa)
        $sehir = Sehir::firstOrCreate([
            'plaka_kodu' => '34'
        ], [
            'ad' => 'İstanbul',
            'telefon_kodu' => '0212',
            'aktif_mi' => true,
        ]);

        // Test ilçesi oluştur (eğer yoksa)
        $ilce = Ilce::firstOrCreate([
            'sehir_id' => $sehir->id,
            'ad' => 'Kadıköy'
        ], [
            'aktif_mi' => true,
        ]);

        // Test mülkü oluştur
        $mulkId = Str::uuid();
        DB::table('mulkler')->insertOrIgnore([
            'id' => $mulkId,
            'mulk_type' => 'konut',
            'baslik' => 'Test Daire',
            'aciklama' => 'Test amaçlı oluşturulan örnek daire',
            'fiyat' => 500000.00,
            'para_birimi' => 'TRY',
            'metrekare' => 120.00,
            'durum' => 'aktif',
            'aktif_mi' => true,
            'olusturma_tarihi' => now(),
            'guncelleme_tarihi' => now(),
        ]);

        // Test mülkü için adres oluştur (polymorphic)
        DB::table('adres')->insertOrIgnore([
            'id' => Str::uuid(),
            'adres_adi' => 'Mülk Adresi',
            'adres_detay' => 'Bağdat Caddesi No: 123 Daire: 5',
            'sehir_id' => $sehir->id,
            'ilce_id' => $ilce->id,
            'varsayilan_mi' => true,
            'aktif_mi' => true,
            'addressable_id' => $mulkId,
            'addressable_type' => 'App\Models\Mulk', // Bu model henüz yok ama ileride oluşturulacak
            'olusturma_tarihi' => now(),
            'guncelleme_tarihi' => now(),
        ]);

        // Test müşteri talebi oluştur
        $talepId = Str::uuid();
        DB::table('musteri_talepleri')->insertOrIgnore([
            'id' => $talepId,
            'musteri_id' => $musteri->id,
            'personel_id' => $personel->id,
            'mulk_kategorisi' => 'konut',
            'alt_kategori' => 'daire',
            'min_metrekare' => 100.00,
            'max_metrekare' => 150.00,
            'min_fiyat' => 400000.00,
            'max_fiyat' => 600000.00,
            'para_birimi' => 'TRY',
            'lokasyon_tercihleri' => json_encode([
                'sehir' => 'İstanbul',
                'ilceler' => ['Kadıköy', 'Üsküdar']
            ]),
            'durum' => 'aktif',
            'oncelik_seviyesi' => 7,
            'aciklama' => 'Deniz manzaralı daire arıyorum',
            'olusturan_id' => $personel->id,
            'aktif_mi' => true,
            'olusturma_tarihi' => now(),
            'guncelleme_tarihi' => now(),
        ]);

        // Test müşteri hizmeti oluştur
        DB::table('musteri_hizmetleri')->insertOrIgnore([
            'id' => Str::uuid(),
            'musteri_id' => $musteri->id,
            'personel_id' => $personel->id,
            'hizmet_tipi' => 'telefon',
            'hizmet_tarihi' => now(),
            'aciklama' => 'İlk görüşme yapıldı',
            'sonuc' => 'Müşteri konut arıyor, detaylar alındı',
            'degerlendirme' => json_encode([
                'tip' => 'olumlu',
                'puan' => 8,
                'notlar' => 'İlgili ve kararlı müşteri'
            ]),
            'sure_dakika' => 15,
            'olusturan_id' => $personel->id,
            'aktif_mi' => true,
            'olusturma_tarihi' => now(),
            'guncelleme_tarihi' => now(),
        ]);

        // Test hatırlatma oluştur
        DB::table('hatirlatmalar')->insertOrIgnore([
            'id' => Str::uuid(),
            'hatirlatilacak_id' => $musteri->id,
            'hatirlatilacak_type' => 'App\Models\Musteri\Musteri',
            'personel_id' => $personel->id,
            'baslik' => 'Müşteriyi ara',
            'aciklama' => 'Portföy güncellemesi hakkında bilgi ver',
            'hatirlatma_tarihi' => now()->addDays(1),
            'hatirlatma_tipi' => 'arama',
            'durum' => 'beklemede',
            'otomatik_bildirim' => true,
            'oncelik' => 7,
            'olusturan_id' => $personel->id,
            'aktif_mi' => true,
            'olusturma_tarihi' => now(),
            'guncelleme_tarihi' => now(),
        ]);

        // Test not oluştur
        DB::table('notlar')->insertOrIgnore([
            'id' => Str::uuid(),
            'notable_id' => $musteri->id,
            'notable_type' => 'App\Models\Musteri\Musteri',
            'personel_id' => $personel->id,
            'baslik' => 'Müşteri Profili',
            'icerik' => 'Deniz manzaralı daire arıyor. Bütçesi 400-600K arası. Acele etmiyor.',
            'kategori' => 'bilgi',
            'oncelik' => 6,
            'gizli_mi' => false,
            'sabitlenmis_mi' => false,
            'etiketler' => json_encode(['konut', 'deniz-manzarası', 'kadıköy']),
            'olusturan_id' => $personel->id,
            'aktif_mi' => true,
            'olusturma_tarihi' => now(),
            'guncelleme_tarihi' => now(),
        ]);

        $this->command->info('Gayrimenkul sistemi test verileri oluşturuldu.');
    }
}
