<?php

namespace App\Enums;

enum ResimKategorisi: string
{
    // Avatar ve Logo Kategorileri
    case AVATAR = 'avatar';
    case LOGO = 'logo';
    
    // Galeri Kategorileri - Genel
    case GALERI_DIS_CEPHE = 'galeri_dis_cephe';
    case GALERI_IC_MEKAN = 'galeri_ic_mekan';
    case GALERI_GENEL = 'galeri_genel';
    
    // Galeri Kategorileri - Konut
    case GALERI_SALON = 'galeri_salon';
    case GALERI_MUTFAK = 'galeri_mutfak';
    case GALERI_YATAK_ODASI = 'galeri_yatak_odasi';
    case GALERI_BANYO = 'galeri_banyo';
    case GALERI_BALKON = 'galeri_balkon';
    case GALERI_BAHCE = 'galeri_bahce';
    case GALERI_TERAS = 'galeri_teras';
    case GALERI_GARAJ = 'galeri_garaj';
    
    // Galeri Kategorileri - İşyeri
    case GALERI_OFIS = 'galeri_ofis';
    case GALERI_DEPO = 'galeri_depo';
    case GALERI_URETIM_ALANI = 'galeri_uretim_alani';
    case GALERI_OTOPARK = 'galeri_otopark';
    case GALERI_MAGAZA = 'galeri_magaza';
    case GALERI_VITRIN = 'galeri_vitrin';
    
    // Galeri Kategorileri - Turistik Tesis
    case GALERI_RESEPSIYON = 'galeri_resepsiyon';
    case GALERI_ODA = 'galeri_oda';
    case GALERI_RESTORAN = 'galeri_restoran';
    case GALERI_HAVUZ = 'galeri_havuz';
    case GALERI_SPA = 'galeri_spa';
    case GALERI_KONFERANS = 'galeri_konferans';
    case GALERI_EGLENCE = 'galeri_eglence';
    
    // Harita ve Teknik Döküman Kategorileri
    case HARITA_UYDU = 'harita_uydu';
    case HARITA_OZNITELIK = 'harita_oznitelik';
    case HARITA_BUYUKSEHIR = 'harita_buyuksehir';
    case HARITA_EGIM = 'harita_egim';
    case HARITA_EIMAR = 'harita_eimar';
    case HARITA_KADASTRO = 'harita_kadastro';
    
    // Teknik Çizim ve Plan Kategorileri
    case PLAN_VAZIYET = 'plan_vaziyet';
    case PLAN_KAT = 'plan_kat';
    case PLAN_CEPHE = 'plan_cephe';
    case PLAN_KESIT = 'plan_kesit';
    case PLAN_DETAY = 'plan_detay';

    /**
     * Kategori etiketini döndür
     */
    public function label(): string
    {
        return match($this) {
            // Avatar ve Logo
            self::AVATAR => 'Avatar',
            self::LOGO => 'Logo',
            
            // Galeri - Genel
            self::GALERI_DIS_CEPHE => 'Dış Cephe',
            self::GALERI_IC_MEKAN => 'İç Mekan',
            self::GALERI_GENEL => 'Genel',
            
            // Galeri - Konut
            self::GALERI_SALON => 'Salon',
            self::GALERI_MUTFAK => 'Mutfak',
            self::GALERI_YATAK_ODASI => 'Yatak Odası',
            self::GALERI_BANYO => 'Banyo',
            self::GALERI_BALKON => 'Balkon',
            self::GALERI_BAHCE => 'Bahçe',
            self::GALERI_TERAS => 'Teras',
            self::GALERI_GARAJ => 'Garaj',
            
            // Galeri - İşyeri
            self::GALERI_OFIS => 'Ofis',
            self::GALERI_DEPO => 'Depo',
            self::GALERI_URETIM_ALANI => 'Üretim Alanı',
            self::GALERI_OTOPARK => 'Otopark',
            self::GALERI_MAGAZA => 'Mağaza',
            self::GALERI_VITRIN => 'Vitrin',
            
            // Galeri - Turistik Tesis
            self::GALERI_RESEPSIYON => 'Resepsiyon',
            self::GALERI_ODA => 'Oda',
            self::GALERI_RESTORAN => 'Restoran',
            self::GALERI_HAVUZ => 'Havuz',
            self::GALERI_SPA => 'SPA',
            self::GALERI_KONFERANS => 'Konferans Salonu',
            self::GALERI_EGLENCE => 'Eğlence Alanı',
            
            // Harita
            self::HARITA_UYDU => 'Uydu Resmi',
            self::HARITA_OZNITELIK => 'Öznitelik Resmi',
            self::HARITA_BUYUKSEHIR => 'Büyükşehir Resmi',
            self::HARITA_EGIM => 'Eğim Resmi',
            self::HARITA_EIMAR => 'E-İmar Resmi',
            self::HARITA_KADASTRO => 'Kadastro Haritası',
            
            // Plan
            self::PLAN_VAZIYET => 'Vaziyet Planı',
            self::PLAN_KAT => 'Kat Planı',
            self::PLAN_CEPHE => 'Cephe Planı',
            self::PLAN_KESIT => 'Kesit Planı',
            self::PLAN_DETAY => 'Detay Planı',
        };
    }

    /**
     * Kategori açıklamasını döndür
     */
    public function description(): string
    {
        return match($this) {
            self::AVATAR => 'Kullanıcı profil resmi',
            self::LOGO => 'Firma veya kurum logosu',
            
            self::GALERI_DIS_CEPHE => 'Binanın dış görünümü',
            self::GALERI_IC_MEKAN => 'İç mekan genel görünümü',
            self::GALERI_GENEL => 'Genel galeri resimleri',
            
            self::GALERI_SALON => 'Oturma odası ve salon alanları',
            self::GALERI_MUTFAK => 'Mutfak ve yemek alanları',
            self::GALERI_YATAK_ODASI => 'Yatak odası alanları',
            self::GALERI_BANYO => 'Banyo ve tuvalet alanları',
            self::GALERI_BALKON => 'Balkon ve açık alanlar',
            self::GALERI_BAHCE => 'Bahçe ve peyzaj alanları',
            self::GALERI_TERAS => 'Teras ve çatı alanları',
            self::GALERI_GARAJ => 'Garaj ve park alanları',
            
            self::GALERI_OFIS => 'Ofis ve çalışma alanları',
            self::GALERI_DEPO => 'Depolama alanları',
            self::GALERI_URETIM_ALANI => 'Üretim ve imalat alanları',
            self::GALERI_OTOPARK => 'Araç park alanları',
            self::GALERI_MAGAZA => 'Mağaza ve satış alanları',
            self::GALERI_VITRIN => 'Vitrin ve sergi alanları',
            
            self::GALERI_RESEPSIYON => 'Karşılama ve resepsiyon alanları',
            self::GALERI_ODA => 'Konaklama odaları',
            self::GALERI_RESTORAN => 'Yemek ve içecek alanları',
            self::GALERI_HAVUZ => 'Yüzme havuzu ve su alanları',
            self::GALERI_SPA => 'Wellness ve SPA alanları',
            self::GALERI_KONFERANS => 'Toplantı ve konferans salonları',
            self::GALERI_EGLENCE => 'Eğlence ve aktivite alanları',
            
            self::HARITA_UYDU => 'Uydu görüntüsü haritası',
            self::HARITA_OZNITELIK => 'Öznitelik bilgili harita',
            self::HARITA_BUYUKSEHIR => 'Büyükşehir belediyesi haritası',
            self::HARITA_EGIM => 'Arazi eğim haritası',
            self::HARITA_EIMAR => 'Elektronik imar planı',
            self::HARITA_KADASTRO => 'Kadastro sınır haritası',
            
            self::PLAN_VAZIYET => 'Arsanın konumu ve çevresi',
            self::PLAN_KAT => 'Kat planı ve oda düzeni',
            self::PLAN_CEPHE => 'Bina cephe görünümü',
            self::PLAN_KESIT => 'Bina kesit görünümü',
            self::PLAN_DETAY => 'Teknik detay çizimleri',
        };
    }

    /**
     * Kategori rengini döndür
     */
    public function color(): string
    {
        return match($this) {
            self::AVATAR, self::LOGO => 'blue',
            
            self::GALERI_DIS_CEPHE, self::GALERI_IC_MEKAN, self::GALERI_GENEL => 'green',
            
            self::GALERI_SALON, self::GALERI_MUTFAK, self::GALERI_YATAK_ODASI, 
            self::GALERI_BANYO, self::GALERI_BALKON, self::GALERI_BAHCE, 
            self::GALERI_TERAS, self::GALERI_GARAJ => 'purple',
            
            self::GALERI_OFIS, self::GALERI_DEPO, self::GALERI_URETIM_ALANI, 
            self::GALERI_OTOPARK, self::GALERI_MAGAZA, self::GALERI_VITRIN => 'orange',
            
            self::GALERI_RESEPSIYON, self::GALERI_ODA, self::GALERI_RESTORAN, 
            self::GALERI_HAVUZ, self::GALERI_SPA, self::GALERI_KONFERANS, 
            self::GALERI_EGLENCE => 'pink',
            
            self::HARITA_UYDU, self::HARITA_OZNITELIK, self::HARITA_BUYUKSEHIR, 
            self::HARITA_EGIM, self::HARITA_EIMAR, self::HARITA_KADASTRO => 'red',
            
            self::PLAN_VAZIYET, self::PLAN_KAT, self::PLAN_CEPHE, 
            self::PLAN_KESIT, self::PLAN_DETAY => 'gray',
        };
    }

    /**
     * Galeri kategorisi mi kontrol et
     */
    public function isGaleriKategorisi(): bool
    {
        return str_starts_with($this->value, 'galeri_');
    }

    /**
     * Harita kategorisi mi kontrol et
     */
    public function isHaritaKategorisi(): bool
    {
        return str_starts_with($this->value, 'harita_');
    }

    /**
     * Plan kategorisi mi kontrol et
     */
    public function isPlanKategorisi(): bool
    {
        return str_starts_with($this->value, 'plan_');
    }

    /**
     * Mülk tipine göre uygun kategorileri getir
     */
    public static function forMulkType(string $mulkType): array
    {
        $modelAdi = class_basename($mulkType);
        
        // Konut kategorileri
        $konutTipleri = ['Daire', 'Villa', 'Rezidans', 'Yali', 'Yazlik'];
        if (in_array($modelAdi, $konutTipleri)) {
            return [
                self::GALERI_DIS_CEPHE,
                self::GALERI_SALON,
                self::GALERI_MUTFAK,
                self::GALERI_YATAK_ODASI,
                self::GALERI_BANYO,
                self::GALERI_BALKON,
                self::GALERI_BAHCE,
                self::GALERI_TERAS,
                self::GALERI_GARAJ,
            ];
        }

        // İşyeri kategorileri
        $isyeriTipleri = ['Depo', 'Fabrika', 'Magaza', 'Ofis', 'Dukkan'];
        if (in_array($modelAdi, $isyeriTipleri)) {
            return [
                self::GALERI_DIS_CEPHE,
                self::GALERI_IC_MEKAN,
                self::GALERI_OFIS,
                self::GALERI_DEPO,
                self::GALERI_URETIM_ALANI,
                self::GALERI_OTOPARK,
                self::GALERI_MAGAZA,
                self::GALERI_VITRIN,
            ];
        }

        // Turistik tesis kategorileri
        $turistikTipleri = ['ButikOtel', 'ApartOtel', 'Hotel', 'Motel', 'TatilKoyu'];
        if (in_array($modelAdi, $turistikTipleri)) {
            return [
                self::GALERI_DIS_CEPHE,
                self::GALERI_RESEPSIYON,
                self::GALERI_ODA,
                self::GALERI_RESTORAN,
                self::GALERI_HAVUZ,
                self::GALERI_SPA,
                self::GALERI_KONFERANS,
                self::GALERI_EGLENCE,
                self::GALERI_BAHCE,
            ];
        }

        // Arsa için harita kategorileri
        $arsaTipleri = ['TicariArsa', 'SanayiArsasi', 'KonutArsasi'];
        if (in_array($modelAdi, $arsaTipleri)) {
            return [
                self::HARITA_UYDU,
                self::HARITA_OZNITELIK,
                self::HARITA_BUYUKSEHIR,
                self::HARITA_EGIM,
                self::HARITA_EIMAR,
                self::HARITA_KADASTRO,
            ];
        }

        // Varsayılan
        return [self::GALERI_GENEL];
    }

    /**
     * Tüm galeri kategorilerini getir
     */
    public static function galeriKategorileri(): array
    {
        return array_filter(self::cases(), fn($case) => $case->isGaleriKategorisi());
    }

    /**
     * Tüm harita kategorilerini getir
     */
    public static function haritaKategorileri(): array
    {
        return array_filter(self::cases(), fn($case) => $case->isHaritaKategorisi());
    }

    /**
     * Tüm plan kategorilerini getir
     */
    public static function planKategorileri(): array
    {
        return array_filter(self::cases(), fn($case) => $case->isPlanKategorisi());
    }
}