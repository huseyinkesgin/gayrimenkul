# Gayrimenkul Portföy Yönetim Sistemi - Tasarım Dokümanı

## Genel Bakış

Bu sistem, Laravel 12 framework'ü üzerinde Livewire/Flux UI kütüphanesi kullanılarak geliştirilecek kapsamlı bir gayrimenkul portföy yönetim sistemidir. Sistem, mevcut BaseModel yapısını genişleterek, hiyerarşik mülk tipi yönetimi, müşteri ilişkileri, döküman/galeri yönetimi ve gelişmiş iş süreçleri takibi sağlayacaktır.

## Mimari

### Genel Mimari Yaklaşımı

Sistem, Domain-Driven Design (DDD) prensiplerini takip ederek modüler bir yapıda tasarlanacaktır:

- **Domain Layer**: İş mantığı ve kuralları
- **Application Layer**: Use case'ler ve servisler  
- **Infrastructure Layer**: Veritabanı, dosya sistemi, dış servisler
- **Presentation Layer**: Livewire bileşenleri ve UI

### Teknoloji Stack'i

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Livewire/Flux, Alpine.js, Tailwind CSS
- **Veritabanı**: MySQL/PostgreSQL (UUID primary keys)
- **Dosya Depolama**: Laravel Storage (local/cloud)
- **Test**: Pest PHP
- **Paket Yönetimi**: Composer, NPM

## Bileşenler ve Arayüzler

### 1. Mülk Yönetimi Modülü

#### 1.1 Mülk Hiyerarşisi

```php
// Ana mülk kategorileri
enum MulkKategorisi: string
{
    case ARSA = 'arsa';
    case ISYERI = 'isyeri';  
    case KONUT = 'konut';
    case TURISTIK_TESIS = 'turistik_tesis';
}

// Alt kategoriler için trait
trait HasSubCategories
{
    abstract public function getSubCategories(): array;
    abstract public function getSpecificAttributes(): array;
}
```

#### 1.2 Mülk Modelleri Yapısı

**Base Mülk Modeli:**
```php
abstract class BaseMulk extends BaseModel
{
    protected $fillable = [
        'baslik', 'aciklama', 'fiyat', 'para_birimi',
        'metrekare', 'durum', 'yayinlanma_tarihi',
        'sehir_id', 'ilce_id', 'semt_id', 'mahalle_id'
    ];
    
    // Polymorphic ilişkiler
    public function adresler();
    public function resimler(); 
    public function dokumanlar();
    public function notlar();
    public function musteriler(); // Many-to-many
}
```

**Spesifik Mülk Modelleri:**
- `Arsa` (TicariArsa, SanayiArsasi, KonutArsasi)
- `Isyeri` (Depo, Fabrika, Magaza, Ofis, Dukkan)
- `Konut` (Daire, Rezidans, Villa, Yali, Yazlik)
- `TuristikTesis` (ButikOtel, ApartOtel, Hotel, Motel, TatilKoyu)

#### 1.3 Özellik Yönetimi

**Dinamik Özellik Sistemi:**
```php
class MulkOzellik extends BaseModel
{
    protected $fillable = [
        'mulk_id', 'mulk_type', 'ozellik_adi', 
        'ozellik_degeri', 'ozellik_tipi', 'birim'
    ];
    
    // JSON cast for complex values
    protected $casts = [
        'ozellik_degeri' => 'json'
    ];
}
```

### 2. Müşteri Yönetimi Modülü

#### 2.1 Genişletilmiş Müşteri Yapısı

**Müşteri Kategorileri:**
```php
enum MusteriKategorisi: string
{
    case SATICI = 'satici';
    case ALICI = 'alici';
    case MAL_SAHIBI = 'mal_sahibi';
    case PARTNER = 'partner';
    case TEDARIKCI = 'tedarikci';
}
```

**Müşteri-Mülk İlişki Takibi:**
```php
class MusteriMulkIliskisi extends BaseModel
{
    protected $fillable = [
        'musteri_id', 'mulk_id', 'mulk_type',
        'iliski_tipi', 'baslangic_tarihi', 'durum',
        'notlar', 'ilgi_seviyesi'
    ];
}
```

#### 2.2 Hizmet Takip Sistemi

```php
class MusteriHizmet extends BaseModel
{
    protected $fillable = [
        'musteri_id', 'personel_id', 'hizmet_tipi',
        'hizmet_tarihi', 'aciklama', 'sonuc',
        'degerlendirme', 'sure_dakika'
    ];
    
    protected $casts = [
        'hizmet_tarihi' => 'datetime',
        'degerlendirme' => 'json' // olumlu/olumsuz + detaylar
    ];
}
```

### 3. Döküman ve Galeri Yönetimi

#### 3.1 Gelişmiş Resim Yönetimi

**Resim Kategorileri:**
```php
enum ResimKategorisi: string
{
    case GALERI = 'galeri';
    case AVATAR = 'avatar';
    case LOGO = 'logo';
    case UYDU = 'uydu';
    case OZNITELIK = 'oznitelik';
    case BUYUKSEHIR = 'buyuksehir';
    case EGIM = 'egim';
    case EIMAR = 'eimar';
}
```

**Genişletilmiş Resim Modeli:**
```php
class Resim extends BaseModel
{
    protected $fillable = [
        'url', 'imageable_id', 'imageable_type',
        'kategori', 'baslik', 'aciklama', 'cekim_tarihi',
        'dosya_boyutu', 'genislik', 'yukseklik', 'aktif_mi'
    ];
    
    protected $casts = [
        'cekim_tarihi' => 'datetime',
        'kategori' => ResimKategorisi::class
    ];
}
```

#### 3.2 Döküman Yönetimi

```php
class Dokuman extends BaseModel
{
    protected $fillable = [
        'url', 'documentable_id', 'documentable_type',
        'dokuman_tipi', 'baslik', 'aciklama', 'dosya_adi',
        'dosya_boyutu', 'mime_type', 'aktif_mi'
    ];
    
    protected $casts = [
        'dokuman_tipi' => DokumanTipi::class
    ];
}

enum DokumanTipi: string
{
    case TAPU = 'tapu';
    case AUTOCAD = 'autocad';
    case PROJE_RESMI = 'proje_resmi';
    case RUHSAT = 'ruhsat';
    case DIGER = 'diger';
}
```

### 4. Talep Yönetimi Sistemi

#### 4.1 Müşteri Talepleri

```php
class MusteriTalep extends BaseModel
{
    protected $fillable = [
        'musteri_id', 'personel_id', 'mulk_kategorisi',
        'alt_kategori', 'min_metrekare', 'max_metrekare',
        'min_fiyat', 'max_fiyat', 'lokasyon_tercihleri',
        'ozel_gereksinimler', 'durum', 'oncelik_seviyesi'
    ];
    
    protected $casts = [
        'lokasyon_tercihleri' => 'json',
        'ozel_gereksinimler' => 'json',
        'mulk_kategorisi' => MulkKategorisi::class
    ];
}
```

#### 4.2 Talep-Portföy Eşleştirme

```php
class TalepPortfoyEslestirme extends BaseModel
{
    protected $fillable = [
        'talep_id', 'mulk_id', 'mulk_type',
        'eslestirme_skoru', 'eslestirme_detaylari',
        'durum', 'personel_notu'
    ];
    
    protected $casts = [
        'eslestirme_detaylari' => 'json'
    ];
}
```

### 5. Hatırlatma ve Takip Sistemi

#### 5.1 Hatırlatma Modeli

```php
class Hatirlatma extends BaseModel
{
    protected $fillable = [
        'hatirlatilacak_id', 'hatirlatilacak_type', // Polymorphic
        'personel_id', 'baslik', 'aciklama',
        'hatirlatma_tarihi', 'hatirlatma_tipi',
        'durum', 'tamamlanma_tarihi', 'sonuc'
    ];
    
    protected $casts = [
        'hatirlatma_tarihi' => 'datetime',
        'tamamlanma_tarihi' => 'datetime',
        'hatirlatma_tipi' => HatirlatmaTipi::class
    ];
}

enum HatirlatmaTipi: string
{
    case ARAMA = 'arama';
    case TOPLANTI = 'toplanti';
    case EMAIL = 'email';
    case ZIYARET = 'ziyaret';
}
```

### 6. Not Sistemi (Universal)

```php
class Not extends BaseModel
{
    protected $fillable = [
        'notable_id', 'notable_type', // Polymorphic
        'personel_id', 'baslik', 'icerik',
        'kategori', 'oncelik', 'gizli_mi'
    ];
    
    protected $casts = [
        'kategori' => NotKategorisi::class,
        'gizli_mi' => 'boolean'
    ];
}
```

## Veri Modelleri

### 1. Veritabanı Şeması

#### 1.1 Temel Altyapı Tabloları

**users**
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**sehir**
```sql
CREATE TABLE sehir (
    id UUID PRIMARY KEY,
    ad VARCHAR(255) NOT NULL,
    plaka_kodu VARCHAR(2) UNIQUE NULL,
    telefon_kodu VARCHAR(4) NULL,
    aktif_mi BOOLEAN DEFAULT TRUE,
    siralama INTEGER DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL
);
```

**ilce**
```sql
CREATE TABLE ilce (
    id UUID PRIMARY KEY,
    sehir_id UUID NOT NULL,
    ad VARCHAR(255) NOT NULL,
    aktif_mi BOOLEAN DEFAULT TRUE,
    siralama INTEGER DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL,
    FOREIGN KEY (sehir_id) REFERENCES sehir(id) ON DELETE CASCADE
);
```

**semt**
```sql
CREATE TABLE semt (
    id UUID PRIMARY KEY,
    ilce_id UUID NOT NULL,
    ad VARCHAR(255) NOT NULL,
    aktif_mi BOOLEAN DEFAULT TRUE,
    siralama INTEGER DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL,
    FOREIGN KEY (ilce_id) REFERENCES ilce(id) ON DELETE CASCADE
);
```

**mahalle**
```sql
CREATE TABLE mahalle (
    id UUID PRIMARY KEY,
    semt_id UUID NOT NULL,
    ad VARCHAR(255) NOT NULL,
    posta_kodu VARCHAR(5) NULL,
    aktif_mi BOOLEAN DEFAULT TRUE,
    siralama INTEGER DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL,
    FOREIGN KEY (semt_id) REFERENCES semt(id) ON DELETE CASCADE
);
```

**kisi**
```sql
CREATE TABLE kisi (
    id UUID PRIMARY KEY,
    ad VARCHAR(255) NOT NULL,
    soyad VARCHAR(255) NOT NULL,
    tc_kimlik_no VARCHAR(11) UNIQUE DEFAULT '11111111111',
    dogum_tarihi DATE NULL,
    cinsiyet VARCHAR(10) NULL,
    dogum_yeri VARCHAR(255) NULL,
    medeni_hali VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    telefon VARCHAR(20) NULL,
    aktif_mi BOOLEAN DEFAULT TRUE,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL
);
```

**sube**
```sql
CREATE TABLE sube (
    id UUID PRIMARY KEY,
    ad VARCHAR(255) NOT NULL,
    kod VARCHAR(50) UNIQUE NOT NULL,
    telefon VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    siralama INTEGER DEFAULT 0,
    aktif_mi BOOLEAN DEFAULT TRUE,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL
);
```

**departman**
```sql
CREATE TABLE departman (
    id UUID PRIMARY KEY,
    ad VARCHAR(255) NOT NULL,
    aciklama TEXT NULL,
    yonetici_id UUID NULL,
    aktif_mi BOOLEAN DEFAULT TRUE,
    siralama INTEGER DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL
);
```

**pozisyon**
```sql
CREATE TABLE pozisyon (
    id UUID PRIMARY KEY,
    ad VARCHAR(255) NOT NULL,
    aktif_mi BOOLEAN DEFAULT TRUE,
    siralama INTEGER DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL
);
```

**personel_rol**
```sql
CREATE TABLE personel_rol (
    id UUID PRIMARY KEY,
    ad VARCHAR(255) NOT NULL,
    aktif_mi BOOLEAN DEFAULT TRUE,
    siralama INTEGER DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL
);
```

**personel**
```sql
CREATE TABLE personel (
    id UUID PRIMARY KEY,
    kisi_id UUID NOT NULL,
    sube_id UUID NOT NULL,
    departman_id UUID NOT NULL,
    pozisyon_id UUID NOT NULL,
    ise_baslama_tarihi DATE NOT NULL,
    isten_ayrilma_tarihi DATE NULL,
    calisma_durumu VARCHAR(20) DEFAULT 'Aktif',
    calisma_sekli VARCHAR(50) NULL,
    personel_no VARCHAR(50) UNIQUE NOT NULL,
    siralama INTEGER DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL,
    FOREIGN KEY (kisi_id) REFERENCES kisi(id) ON DELETE CASCADE,
    FOREIGN KEY (sube_id) REFERENCES sube(id) ON DELETE CASCADE,
    FOREIGN KEY (departman_id) REFERENCES departman(id) ON DELETE CASCADE,
    FOREIGN KEY (pozisyon_id) REFERENCES pozisyon(id) ON DELETE CASCADE
);
```

**personel_personel_rolu**
```sql
CREATE TABLE personel_personel_rolu (
    personel_id UUID NOT NULL,
    personel_rol_id UUID NOT NULL,
    PRIMARY KEY (personel_id, personel_rol_id),
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE CASCADE,
    FOREIGN KEY (personel_rol_id) REFERENCES personel_rol(id) ON DELETE CASCADE
);
```

**resim (Genişletilmiş)**
```sql
CREATE TABLE resim (
    id UUID PRIMARY KEY,
    url VARCHAR(500) NULL,
    imageable_id UUID NOT NULL,
    imageable_type VARCHAR(255) NOT NULL,
    kategori ENUM('galeri', 'avatar', 'logo', 'kapak_resmi', 'ic_mekan', 'dis_mekan', 'detay', 'plan', 'cephe', 'manzara', 'uydu', 'oznitelik', 'buyuksehir', 'egim', 'eimar') DEFAULT 'galeri',
    baslik VARCHAR(255) NULL,
    aciklama TEXT NULL,
    cekim_tarihi TIMESTAMP NULL,
    dosya_boyutu BIGINT NULL COMMENT 'Byte cinsinden',
    genislik INTEGER NULL COMMENT 'Pixel cinsinden',
    yukseklik INTEGER NULL COMMENT 'Pixel cinsinden',
    mime_type VARCHAR(100) NULL,
    dosya_adi VARCHAR(255) NULL,
    orijinal_dosya_adi VARCHAR(255) NULL,
    hash VARCHAR(64) NULL,
    exif_data JSON NULL,
    thumbnail_url VARCHAR(500) NULL,
    medium_url VARCHAR(500) NULL,
    large_url VARCHAR(500) NULL,
    is_processed BOOLEAN DEFAULT FALSE,
    processing_error TEXT NULL,
    upload_ip VARCHAR(45) NULL,
    upload_user_agent TEXT NULL,
    yükleyen_id UUID NULL,
    onay_durumu ENUM('beklemede', 'onaylandı', 'reddedildi') DEFAULT 'beklemede',
    onaylayan_id UUID NULL,
    onay_tarihi TIMESTAMP NULL,
    görüntülenme_sayisi INTEGER DEFAULT 0,
    son_görüntülenme_tarihi TIMESTAMP NULL,
    etiketler JSON NULL,
    alt_text VARCHAR(255) NULL,
    copyright_bilgisi VARCHAR(255) NULL,
    aktif_mi BOOLEAN DEFAULT TRUE,
    siralama INTEGER DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL,
    INDEX idx_imageable (imageable_id, imageable_type),
    INDEX idx_kategori_aktif (kategori, aktif_mi),
    INDEX idx_hash (hash),
    INDEX idx_cekim_tarihi (cekim_tarihi),
    INDEX idx_siralama (siralama)
);
```

**adres**
```sql
CREATE TABLE adres (
    id UUID PRIMARY KEY,
    addressable_id UUID NOT NULL,
    addressable_type VARCHAR(255) NOT NULL,
    adres_adi VARCHAR(100) NULL,
    adres_detay TEXT NOT NULL,
    sehir_id UUID NOT NULL,
    ilce_id UUID NOT NULL,
    semt_id UUID NOT NULL,
    mahalle_id UUID NOT NULL,
    varsayilan_mi BOOLEAN DEFAULT FALSE,
    aktif_mi BOOLEAN DEFAULT TRUE,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL,
    FOREIGN KEY (sehir_id) REFERENCES sehir(id) ON DELETE CASCADE,
    FOREIGN KEY (ilce_id) REFERENCES ilce(id) ON DELETE CASCADE,
    FOREIGN KEY (semt_id) REFERENCES semt(id) ON DELETE CASCADE,
    FOREIGN KEY (mahalle_id) REFERENCES mahalle(id) ON DELETE CASCADE,
    INDEX idx_addressable (addressable_type, addressable_id),
    INDEX idx_lokasyon (sehir_id, ilce_id, semt_id, mahalle_id)
);
```

#### 1.2 Mülk Tabloları

**mulkler (Base Table)**
```sql
CREATE TABLE mulkler (
    id UUID PRIMARY KEY,
    mulk_type VARCHAR(50) NOT NULL, -- STI için
    baslik VARCHAR(255) NOT NULL,
    aciklama TEXT,
    fiyat DECIMAL(15,2),
    para_birimi VARCHAR(3) DEFAULT 'TRY',
    metrekare DECIMAL(10,2),
    durum ENUM('aktif', 'pasif', 'satildi', 'kiralandi'),
    yayinlanma_tarihi TIMESTAMP,
    -- Lokasyon bilgileri polymorphic adres ilişkisi ile yönetilir
    olusturan_id UUID,
    guncelleyen_id UUID,
    aktif_mi BOOLEAN DEFAULT TRUE,
    siralama INTEGER DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL
);
```

**mulk_ozellikleri**
```sql
CREATE TABLE mulk_ozellikleri (
    id UUID PRIMARY KEY,
    mulk_id UUID NOT NULL,
    mulk_type VARCHAR(50) NOT NULL,
    ozellik_adi VARCHAR(100) NOT NULL,
    ozellik_degeri JSON,
    ozellik_tipi ENUM('sayi', 'metin', 'boolean', 'liste'),
    birim VARCHAR(20),
    aktif_mi BOOLEAN DEFAULT TRUE,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL
);
```

#### 1.2 Müşteri İlişki Tabloları

**musteri_mulk_iliskileri**
```sql
CREATE TABLE musteri_mulk_iliskileri (
    id UUID PRIMARY KEY,
    musteri_id UUID NOT NULL,
    mulk_id UUID NOT NULL,
    mulk_type VARCHAR(50) NOT NULL,
    iliski_tipi ENUM('ilgileniyor', 'teklif_verdi', 'gorustu', 'satin_aldi'),
    baslangic_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    durum ENUM('aktif', 'pasif', 'tamamlandi'),
    ilgi_seviyesi TINYINT DEFAULT 5,
    notlar TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**musteri_hizmetleri**
```sql
CREATE TABLE musteri_hizmetleri (
    id UUID PRIMARY KEY,
    musteri_id UUID NOT NULL,
    personel_id UUID NOT NULL,
    hizmet_tipi ENUM('telefon', 'toplanti', 'email', 'ziyaret', 'diger'),
    hizmet_tarihi TIMESTAMP NOT NULL,
    aciklama TEXT,
    sonuc TEXT,
    degerlendirme JSON, -- {tip: 'olumlu/olumsuz', puan: 1-10, notlar: '...'}
    sure_dakika INT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 1.3 Talep Yönetimi Tabloları

**musteri_talepleri**
```sql
CREATE TABLE musteri_talepleri (
    id UUID PRIMARY KEY,
    musteri_id UUID NOT NULL,
    personel_id UUID NOT NULL,
    mulk_kategorisi ENUM('arsa', 'isyeri', 'konut', 'turistik_tesis'),
    alt_kategori VARCHAR(50),
    min_metrekare DECIMAL(10,2),
    max_metrekare DECIMAL(10,2),
    min_fiyat DECIMAL(15,2),
    max_fiyat DECIMAL(15,2),
    lokasyon_tercihleri JSON,
    ozel_gereksinimler JSON,
    durum ENUM('aktif', 'beklemede', 'tamamlandi', 'iptal'),
    oncelik_seviyesi TINYINT DEFAULT 5,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL
);
```

**talep_portfoy_eslestirmeleri**
```sql
CREATE TABLE talep_portfoy_eslestirmeleri (
    id UUID PRIMARY KEY,
    talep_id UUID NOT NULL,
    mulk_id UUID NOT NULL,
    mulk_type VARCHAR(50) NOT NULL,
    eslestirme_skoru DECIMAL(3,2), -- 0.00 - 1.00
    eslestirme_detaylari JSON,
    durum ENUM('yeni', 'incelendi', 'sunuldu', 'reddedildi'),
    personel_notu TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 2. Polymorphic İlişkiler

#### 2.1 Resim Sistemi
- `imageable_id` + `imageable_type` ile tüm modellere bağlanabilir
- Kategori bazlı filtreleme (galeri, avatar, harita resimleri)

#### 2.2 Döküman Sistemi  
- `documentable_id` + `documentable_type` ile tüm modellere bağlanabilir
- Mülk tipine göre uygun döküman tipleri

#### 2.3 Adres Sistemi
- `addressable_id` + `addressable_type` ile müşteri, firma, mülk modelleri
- Mülkler için lokasyon bilgileri polymorphic adres ilişkisi ile yönetilir
- Her mülkün birden fazla adresi olabilir (örn: tapu adresi, fiili adres)
- Varsayılan adres sistemi ile ana lokasyon belirlenir

#### 2.4 Not Sistemi
- `notable_id` + `notable_type` ile tüm modellere not eklenebilir

## Hata Yönetimi

### 1. Validation Kuralları

**Mülk Validasyonu:**
```php
class MulkValidationRules
{
    public static function baseRules(): array
    {
        return [
            'baslik' => 'required|string|max:255',
            'fiyat' => 'required|numeric|min:0',
            'metrekare' => 'required|numeric|min:0',
            'sehir_id' => 'required|exists:sehir,id',
            'ilce_id' => 'required|exists:ilce,id'
        ];
    }
    
    public static function specificRules(string $mulkType): array
    {
        return match($mulkType) {
            'fabrika' => [
                'kapali_alan' => 'required|numeric|min:0',
                'acik_alan' => 'nullable|numeric|min:0',
                'yukseklik' => 'required|numeric|min:0'
            ],
            'daire' => [
                'oda_sayisi' => 'required|integer|min:1',
                'asansor_var_mi' => 'required|boolean'
            ],
            default => []
        };
    }
}
```

### 2. Exception Handling

```php
class MulkNotFoundException extends Exception {}
class InvalidMulkTypeException extends Exception {}
class TalepEslestirmeException extends Exception {}
```

### 3. API Response Standardı

```php
class ApiResponse
{
    public static function success($data = null, $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ]);
    }
    
    public static function error($message, $errors = null, $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
}
```

## Test Stratejisi

### 1. Unit Tests
- Model ilişkileri ve business logic
- Validation kuralları
- Helper metodları

### 2. Feature Tests  
- API endpoint'leri
- Livewire bileşenleri
- Dosya upload işlemleri

### 3. Integration Tests
- Talep-portföy eşleştirme algoritması
- Hatırlatma sistemi
- Raporlama modülü

### 4. Test Veri Yapısı

```php
// Factories
class MulkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'baslik' => $this->faker->sentence(3),
            'fiyat' => $this->faker->numberBetween(100000, 5000000),
            'metrekare' => $this->faker->numberBetween(50, 500),
            'durum' => $this->faker->randomElement(['aktif', 'pasif']),
            'sehir_id' => Sehir::factory(),
            'ilce_id' => Ilce::factory()
        ];
    }
    
    public function fabrika(): static
    {
        return $this->state(fn (array $attributes) => [
            'mulk_type' => 'fabrika'
        ]);
    }
}
```

Bu tasarım dokümanı, mevcut Laravel yapısını genişleterek kapsamlı bir gayrimenkul portföy yönetim sistemi oluşturmak için gerekli tüm bileşenleri, veri modellerini ve iş mantığını detaylandırmaktadır. Sistem modüler, ölçeklenebilir ve genişletilebilir bir yapıda tasarlanmıştır.

## Temel Altyapı Modülleri

### 1. Kullanıcı Yönetimi Modülü

#### 1.1 Kullanıcı Modeli
```php
class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password'
    ];
    
    // Avatar ilişkisi
    public function avatar();
    
    // Resim ilişkileri
    public function resimler();
}
```

### 2. Lokasyon Hiyerarşisi Modülü

#### 2.1 Lokasyon Modelleri
```php
class Sehir extends BaseModel
{
    protected $fillable = [
        'ad', 'plaka_kodu', 'telefon_kodu', 'aktif_mi', 'siralama'
    ];
    
    public function ilceler();
}

class Ilce extends BaseModel
{
    protected $fillable = [
        'sehir_id', 'ad', 'aktif_mi', 'siralama'
    ];
    
    public function sehir();
    public function semtler();
}

class Semt extends BaseModel
{
    protected $fillable = [
        'ilce_id', 'ad', 'aktif_mi', 'siralama'
    ];
    
    public function ilce();
    public function mahalleler();
}

class Mahalle extends BaseModel
{
    protected $fillable = [
        'semt_id', 'ad', 'posta_kodu', 'aktif_mi', 'siralama'
    ];
    
    public function semt();
}
```

### 3. Kişi Yönetimi Modülü

#### 3.1 Kişi Modeli
```php
class Kisi extends BaseModel
{
    protected $fillable = [
        'ad', 'soyad', 'tc_kimlik_no', 'dogum_tarihi',
        'cinsiyet', 'dogum_yeri', 'medeni_hali',
        'email', 'telefon', 'aktif_mi'
    ];
    
    protected $casts = [
        'dogum_tarihi' => 'date'
    ];
    
    // İlişkiler
    public function personeller();
    public function adresler(); // Polymorphic
    
    // Accessor'lar
    public function getFullNameAttribute();
}
```

### 4. Organizasyon Yapısı Modülü

#### 4.1 Organizasyon Modelleri
```php
class Sube extends BaseModel
{
    protected $fillable = [
        'ad', 'kod', 'telefon', 'email', 'siralama', 'aktif_mi'
    ];
    
    public function personeller();
}

class Departman extends BaseModel
{
    protected $fillable = [
        'ad', 'aciklama', 'yonetici_id', 'aktif_mi', 'siralama'
    ];
    
    public function yonetici(); // Self-referencing
    public function personeller();
}

class Pozisyon extends BaseModel
{
    protected $fillable = [
        'ad', 'aktif_mi', 'siralama'
    ];
    
    public function personeller();
}

class PersonelRol extends BaseModel
{
    protected $fillable = [
        'ad', 'aktif_mi', 'siralama'
    ];
    
    public function personeller(); // Many-to-many
}
```

#### 4.2 Personel Modeli
```php
class Personel extends BaseModel
{
    protected $fillable = [
        'kisi_id', 'sube_id', 'departman_id', 'pozisyon_id',
        'ise_baslama_tarihi', 'isten_ayrilma_tarihi',
        'calisma_durumu', 'calisma_sekli', 'personel_no', 'siralama'
    ];
    
    protected $casts = [
        'ise_baslama_tarihi' => 'date',
        'isten_ayrilma_tarihi' => 'date'
    ];
    
    // İlişkiler
    public function kisi();
    public function sube();
    public function departman();
    public function pozisyon();
    public function roller(); // Many-to-many
    public function adresler(); // Polymorphic
    public function avatar(); // Polymorphic
    public function resimler(); // Polymorphic
    
    // Accessor'lar
    public function getFullNameAttribute();
    
    // Scope'lar
    public function scopeAktifPersonel($query);
}
```

### 5. Gelişmiş Resim Yönetimi Modülü

#### 5.1 Genişletilmiş Resim Modeli
```php
class Resim extends BaseModel
{
    protected $fillable = [
        'url', 'imageable_id', 'imageable_type',
        'kategori', 'baslik', 'aciklama', 'cekim_tarihi',
        'dosya_boyutu', 'genislik', 'yukseklik',
        'mime_type', 'dosya_adi', 'orijinal_dosya_adi',
        'hash', 'exif_data', 'thumbnail_url', 'medium_url', 'large_url',
        'is_processed', 'processing_error',
        'yükleyen_id', 'onay_durumu', 'onaylayan_id', 'onay_tarihi',
        'görüntülenme_sayisi', 'son_görüntülenme_tarihi',
        'etiketler', 'alt_text', 'copyright_bilgisi',
        'aktif_mi', 'siralama'
    ];
    
    protected $casts = [
        'kategori' => ResimKategorisi::class,
        'cekim_tarihi' => 'datetime',
        'onay_tarihi' => 'datetime',
        'son_görüntülenme_tarihi' => 'datetime',
        'exif_data' => 'json',
        'etiketler' => 'json',
        'is_processed' => 'boolean',
        'aktif_mi' => 'boolean'
    ];
    
    // İlişkiler
    public function imageable(); // Polymorphic
    public function yükleyen();
    public function onaylayan();
    
    // Scope'lar
    public function scopeIslenmis($query);
    public function scopeOnaylanmis($query);
    public function scopeKategoriye($query, $category);
    public function scopeGaleri($query);
    public function scopeProfil($query);
    public function scopeHarita($query);
    
    // Accessor'lar ve metodlar
    public function getFormattedSizeAttribute();
    public function getDimensionsAttribute();
    public function getAspectRatioAttribute();
    public function getMegapixelsAttribute();
    public function processImage();
    public function incrementViews();
}
```

#### 5.2 Resim Kategorileri
```php
enum ResimKategorisi: string
{
    case GALERI = 'galeri';
    case AVATAR = 'avatar';
    case LOGO = 'logo';
    case KAPAK_RESMI = 'kapak_resmi';
    case IC_MEKAN = 'ic_mekan';
    case DIS_MEKAN = 'dis_mekan';
    case DETAY = 'detay';
    case PLAN = 'plan';
    case CEPHE = 'cephe';
    case MANZARA = 'manzara';
    case UYDU = 'uydu';
    case OZNITELIK = 'oznitelik';
    case BUYUKSEHIR = 'buyuksehir';
    case EGIM = 'egim';
    case EIMAR = 'eimar';
    
    public function label(): string;
    public function maxFileSize(): int;
    public function recommendedDimensions(): array;
    public function requiresApproval(): bool;
    public function requiresWatermark(): bool;
}
```

### 6. Adres Yönetimi Modülü

#### 6.1 Adres Modeli
```php
class Adres extends BaseModel
{
    protected $fillable = [
        'addressable_id', 'addressable_type',
        'adres_adi', 'adres_detay',
        'sehir_id', 'ilce_id', 'semt_id', 'mahalle_id',
        'varsayilan_mi', 'aktif_mi'
    ];
    
    protected $casts = [
        'varsayilan_mi' => 'boolean',
        'aktif_mi' => 'boolean'
    ];
    
    // İlişkiler
    public function addressable(); // Polymorphic
    public function sehir();
    public function ilce();
    public function semt();
    public function mahalle();
    
    // Accessor'lar
    public function getFullAddressAttribute();
    public function getLocationHierarchyAttribute();
    
    // Scope'lar
    public function scopeVarsayilan($query);
    public function scopeBySehir($query, $sehirId);
}
```

### 7. Veri İçe/Dışa Aktarma Modülü

#### 7.1 Import/Export Servisleri
```php
class ImportExportService
{
    public function exportData(string $model, array $criteria, string $format);
    public function importData(UploadedFile $file, string $model, array $options);
    public function validateImportData(array $data, string $model);
    public function generateImportReport(array $results);
}

class ExportJob implements ShouldQueue
{
    public function handle();
    protected function generateExcel(Collection $data);
    protected function generateCSV(Collection $data);
    protected function generateJSON(Collection $data);
}

class ImportJob implements ShouldQueue
{
    public function handle();
    protected function processExcel(UploadedFile $file);
    protected function processCSV(UploadedFile $file);
    protected function validateAndImport(array $data);
}
```

#### 7.2 Import/Export Logları
```php
class ImportExportLog extends BaseModel
{
    protected $fillable = [
        'user_id', 'operation_type', 'model_type', 'file_name',
        'total_records', 'success_count', 'error_count',
        'status', 'started_at', 'completed_at', 'error_details'
    ];
    
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'error_details' => 'json'
    ];
}
```

### 8. Profesyonel Sunum ve Rapor Modülü

#### 8.1 Sunum Şablonları
```php
class SunumSablonu extends BaseModel
{
    protected $fillable = [
        'ad', 'aciklama', 'kategori', 'sablon_dosyasi',
        'onizleme_resmi', 'varsayilan_mi', 'aktif_mi'
    ];
    
    public function sunumlar();
    public function generatePreview();
}

class Sunum extends BaseModel
{
    protected $fillable = [
        'baslik', 'musteri_id', 'sablon_id', 'mulk_ids',
        'icerik_ayarlari', 'durum', 'olusturan_id',
        'pdf_dosyasi', 'olusturma_tarihi', 'son_indirme_tarihi'
    ];
    
    protected $casts = [
        'mulk_ids' => 'json',
        'icerik_ayarlari' => 'json',
        'olusturma_tarihi' => 'datetime',
        'son_indirme_tarihi' => 'datetime'
    ];
    
    public function musteri();
    public function sablon();
    public function mulkler();
    public function generatePDF();
    public function trackDownload();
}
```

#### 8.2 Sunum Servisleri
```php
class SunumService
{
    public function createPresentation(array $data);
    public function generatePDF(Sunum $sunum);
    public function addWatermark(string $pdfPath);
    public function trackPresentationUsage(Sunum $sunum);
    public function getTemplateVariables(SunumSablonu $sablon);
}

class PDFGeneratorService
{
    public function generateFromTemplate(string $template, array $data);
    public function addCoverPage(array $data);
    public function addPropertyPages(Collection $properties);
    public function addContactInfo(array $contact);
    public function optimizeForPrint();
}
```

### 9. Kapsamlı Log ve Aktivite Takibi Modülü

#### 9.1 Sistem Logları
```php
class SistemLog extends BaseModel
{
    protected $fillable = [
        'user_id', 'ip_address', 'user_agent', 'action',
        'model_type', 'model_id', 'old_values', 'new_values',
        'url', 'method', 'status_code', 'response_time',
        'session_id', 'log_level', 'message', 'context'
    ];
    
    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'context' => 'json'
    ];
    
    public function user();
    public function scopeByUser($query, $userId);
    public function scopeByAction($query, $action);
    public function scopeByDateRange($query, $start, $end);
}

class AktiviteLog extends BaseModel
{
    protected $fillable = [
        'user_id', 'aktivite_tipi', 'baslik', 'aciklama',
        'etkilenen_model', 'etkilenen_id', 'ip_address',
        'cihaz_bilgisi', 'konum_bilgisi', 'sure'
    ];
    
    public function user();
    public function getFormattedDurationAttribute();
}
```

#### 9.2 Müşteri Etkileşim Takibi
```php
class MusteriEtkilesim extends BaseModel
{
    protected $fillable = [
        'musteri_id', 'etkilesim_tipi', 'sayfa_url', 'mulk_id',
        'sure', 'tiklama_sayisi', 'scroll_derinligi',
        'cihaz_tipi', 'tarayici', 'referrer_url', 'session_id'
    ];
    
    protected $casts = [
        'sure' => 'integer',
        'tiklama_sayisi' => 'integer',
        'scroll_derinligi' => 'float'
    ];
    
    public function musteri();
    public function mulk();
    public function scopeByCustomer($query, $customerId);
    public function scopeByProperty($query, $propertyId);
}

class MusteriDavranisAnalizi extends BaseModel
{
    protected $fillable = [
        'musteri_id', 'ilgi_alanlari', 'tercih_profili',
        'ortalama_sure', 'favori_mulk_tipleri', 'butce_araligi',
        'aktivite_skoru', 'son_aktivite_tarihi'
    ];
    
    protected $casts = [
        'ilgi_alanlari' => 'json',
        'tercih_profili' => 'json',
        'favori_mulk_tipleri' => 'json',
        'son_aktivite_tarihi' => 'datetime'
    ];
}
```

### 10. Gelişmiş Bildirim Sistemi

#### 10.1 Bildirim Modelleri
```php
class Bildirim extends BaseModel
{
    protected $fillable = [
        'user_id', 'baslik', 'icerik', 'tip', 'kanal',
        'oncelik', 'okundu_mu', 'gonderildi_mi', 'hata_mesaji',
        'gonderim_tarihi', 'okunma_tarihi', 'metadata'
    ];
    
    protected $casts = [
        'okundu_mu' => 'boolean',
        'gonderildi_mi' => 'boolean',
        'gonderim_tarihi' => 'datetime',
        'okunma_tarihi' => 'datetime',
        'metadata' => 'json'
    ];
    
    public function user();
    public function markAsRead();
    public function scopeUnread($query);
}

class BildirimAyarlari extends BaseModel
{
    protected $fillable = [
        'user_id', 'bildirim_tipi', 'email_aktif', 'sms_aktif',
        'push_aktif', 'ses_aktif', 'titresim_aktif', 'saatler'
    ];
    
    protected $casts = [
        'email_aktif' => 'boolean',
        'sms_aktif' => 'boolean',
        'push_aktif' => 'boolean',
        'ses_aktif' => 'boolean',
        'titresim_aktif' => 'boolean',
        'saatler' => 'json'
    ];
}
```

#### 10.2 Bildirim Servisleri
```php
class BildirimService
{
    public function send(array $data);
    public function sendEmail(User $user, string $template, array $data);
    public function sendSMS(User $user, string $message);
    public function sendPushNotification(User $user, array $data);
    public function scheduleNotification(array $data, Carbon $sendAt);
    public function getUserPreferences(User $user);
}

class NotificationChannelManager
{
    public function getAvailableChannels();
    public function isChannelEnabled(User $user, string $channel);
    public function sendViaChannel(string $channel, array $data);
}
```

### 11. Performans İzleme Modülü

#### 11.1 Performans Metrikleri
```php
class PerformansMetrik extends BaseModel
{
    protected $fillable = [
        'metrik_tipi', 'deger', 'birim', 'url', 'user_id',
        'session_id', 'cihaz_tipi', 'tarayici', 'olcum_tarihi'
    ];
    
    protected $casts = [
        'deger' => 'float',
        'olcum_tarihi' => 'datetime'
    ];
    
    public function scopeByType($query, $type);
    public function scopeByDateRange($query, $start, $end);
}

class SistemPerformans extends BaseModel
{
    protected $fillable = [
        'cpu_kullanimi', 'ram_kullanimi', 'disk_kullanimi',
        'aktif_kullanici_sayisi', 'ortalama_yanit_suresi',
        'hata_orani', 'olcum_tarihi'
    ];
    
    protected $casts = [
        'cpu_kullanimi' => 'float',
        'ram_kullanimi' => 'float',
        'disk_kullanimi' => 'float',
        'ortalama_yanit_suresi' => 'float',
        'hata_orani' => 'float',
        'olcum_tarihi' => 'datetime'
    ];
}
```
##
 Yeni Profesyonel Modüller

### 7. Veri İçe/Dışa Aktarma Sistemi

#### 7.1 Import/Export Servisleri
```php
class DataImportService
{
    public function importFromExcel(string $filePath, string $modelType): ImportResult;
    public function importFromCsv(string $filePath, string $modelType): ImportResult;
    public function validateImportData(array $data, string $modelType): ValidationResult;
    public function processImportBatch(array $batch, string $modelType): BatchResult;
}

class DataExportService
{
    public function exportToExcel(string $modelType, array $filters = []): string;
    public function exportToCsv(string $modelType, array $filters = []): string;
    public function exportToJson(string $modelType, array $filters = []): string;
    public function generateExportReport(string $exportId): ExportReport;
}
```

#### 7.2 Import/Export Modelleri
```php
class DataImport extends BaseModel
{
    protected $fillable = [
        'dosya_adi', 'dosya_yolu', 'model_tipi',
        'durum', 'toplam_kayit', 'basarili_kayit', 'hatali_kayit',
        'hata_raporu', 'baslangic_zamani', 'bitis_zamani',
        'kullanici_id'
    ];
    
    protected $casts = [
        'hata_raporu' => 'json',
        'baslangic_zamani' => 'datetime',
        'bitis_zamani' => 'datetime'
    ];
    
    public function kullanici();
    public function hatalar(); // HasMany ImportError
}

class DataExport extends BaseModel
{
    protected $fillable = [
        'dosya_adi', 'dosya_yolu', 'model_tipi', 'format',
        'filtreler', 'durum', 'toplam_kayit',
        'baslangic_zamani', 'bitis_zamani', 'kullanici_id'
    ];
    
    protected $casts = [
        'filtreler' => 'json',
        'baslangic_zamani' => 'datetime',
        'bitis_zamani' => 'datetime'
    ];
}

class ImportError extends BaseModel
{
    protected $fillable = [
        'import_id', 'satir_no', 'alan_adi',
        'hata_mesaji', 'veri_degeri'
    ];
}
```

### 8. Profesyonel Sunum ve Rapor Sistemi

#### 8.1 Sunum Oluşturma Servisi
```php
class PresentationService
{
    public function createPropertyPresentation(array $propertyIds, string $templateId): PresentationResult;
    public function generatePdfReport(string $presentationId): string;
    public function createCustomTemplate(array $templateData): Template;
    public function getAvailableTemplates(): Collection;
}

class ReportService
{
    public function generatePortfolioReport(array $filters): PortfolioReport;
    public function generateCustomerAnalysis(array $filters): CustomerAnalysisReport;
    public function generateSalesPerformanceReport(array $dateRange): SalesReport;
    public function generateActivityReport(array $filters): ActivityReport;
}
```

#### 8.2 Sunum ve Rapor Modelleri
```php
class Presentation extends BaseModel
{
    protected $fillable = [
        'baslik', 'aciklama', 'template_id',
        'mulk_ids', 'musteri_id', 'durum',
        'pdf_yolu', 'olusturan_id', 'son_guncelleme'
    ];
    
    protected $casts = [
        'mulk_ids' => 'json',
        'son_guncelleme' => 'datetime'
    ];
    
    public function template();
    public function musteri();
    public function olusturan();
    public function mulkler(); // Many-to-many through JSON
}

class PresentationTemplate extends BaseModel
{
    protected $fillable = [
        'ad', 'aciklama', 'tasarim_ayarlari',
        'sayfa_duzeni', 'renk_paleti', 'font_ayarlari',
        'logo_pozisyonu', 'aktif_mi', 'varsayilan_mi'
    ];
    
    protected $casts = [
        'tasarim_ayarlari' => 'json',
        'sayfa_duzeni' => 'json',
        'renk_paleti' => 'json',
        'font_ayarlari' => 'json',
        'aktif_mi' => 'boolean',
        'varsayilan_mi' => 'boolean'
    ];
}

class Report extends BaseModel
{
    protected $fillable = [
        'baslik', 'rapor_tipi', 'filtreler',
        'veri_seti', 'grafik_ayarlari', 'durum',
        'dosya_yolu', 'olusturan_id', 'olusturma_zamani'
    ];
    
    protected $casts = [
        'filtreler' => 'json',
        'veri_seti' => 'json',
        'grafik_ayarlari' => 'json',
        'olusturma_zamani' => 'datetime'
    ];
}
```

### 9. Kapsamlı Log ve İzleme Sistemi

#### 9.1 Aktivite Takip Servisi
```php
class ActivityLogService
{
    public function logUserActivity(string $userId, string $action, array $details): void;
    public function logSystemEvent(string $event, array $context): void;
    public function logDataChange(string $model, string $modelId, array $changes): void;
    public function logError(Exception $exception, array $context): void;
    public function getActivityLogs(array $filters): Collection;
}

class PerformanceMonitorService
{
    public function recordPageLoad(string $page, float $loadTime): void;
    public function recordDatabaseQuery(string $query, float $executionTime): void;
    public function recordApiCall(string $endpoint, float $responseTime): void;
    public function getPerformanceMetrics(array $filters): PerformanceMetrics;
}
```

#### 9.2 Log Modelleri
```php
class ActivityLog extends BaseModel
{
    protected $fillable = [
        'kullanici_id', 'aksiyon', 'model_tipi', 'model_id',
        'eski_degerler', 'yeni_degerler', 'ip_adresi',
        'user_agent', 'oturum_id', 'zaman_damgasi'
    ];
    
    protected $casts = [
        'eski_degerler' => 'json',
        'yeni_degerler' => 'json',
        'zaman_damgasi' => 'datetime'
    ];
    
    public function kullanici();
    public function model(); // Polymorphic
}

class SystemLog extends BaseModel
{
    protected $fillable = [
        'seviye', 'mesaj', 'kanal', 'baglam',
        'hata_detaylari', 'stack_trace', 'zaman_damgasi'
    ];
    
    protected $casts = [
        'baglam' => 'json',
        'hata_detaylari' => 'json',
        'zaman_damgasi' => 'datetime'
    ];
}

class PerformanceLog extends BaseModel
{
    protected $fillable = [
        'metrik_tipi', 'sayfa_url', 'yukleme_suresi',
        'sorgu_sayisi', 'bellek_kullanimi', 'kullanici_id',
        'cihaz_bilgisi', 'tarayici_bilgisi', 'zaman_damgasi'
    ];
    
    protected $casts = [
        'cihaz_bilgisi' => 'json',
        'tarayici_bilgisi' => 'json',
        'zaman_damgasi' => 'datetime'
    ];
}
```

### 10. Bildirim ve Hatırlatma Sistemi

#### 10.1 Bildirim Servisleri
```php
class NotificationService
{
    public function sendEmail(string $to, string $subject, string $content): bool;
    public function sendSms(string $phone, string $message): bool;
    public function sendPushNotification(string $userId, array $data): bool;
    public function createInAppNotification(string $userId, array $data): Notification;
    public function markAsRead(string $notificationId): bool;
}

class ReminderService
{
    public function createReminder(array $reminderData): Reminder;
    public function processScheduledReminders(): void;
    public function snoozeReminder(string $reminderId, int $minutes): bool;
    public function completeReminder(string $reminderId, string $result): bool;
}
```

#### 10.2 Bildirim Modelleri
```php
class Notification extends BaseModel
{
    protected $fillable = [
        'kullanici_id', 'baslik', 'icerik', 'tip',
        'kanal', 'okundu_mu', 'okunma_zamani',
        'aksiyon_url', 'meta_veri', 'oncelik'
    ];
    
    protected $casts = [
        'okundu_mu' => 'boolean',
        'okunma_zamani' => 'datetime',
        'meta_veri' => 'json'
    ];
    
    public function kullanici();
    
    public function scopeOkunmamis($query);
    public function scopeTipe($query, $tip);
}

class Reminder extends BaseModel
{
    protected $fillable = [
        'kullanici_id', 'baslik', 'aciklama', 'hatirlatma_zamani',
        'hatirlatma_tipi', 'durum', 'tekrar_ayari',
        'ilgili_model_id', 'ilgili_model_tipi', 'sonuc'
    ];
    
    protected $casts = [
        'hatirlatma_zamani' => 'datetime',
        'tekrar_ayari' => 'json'
    ];
    
    public function kullanici();
    public function ilgiliModel(); // Polymorphic
    
    public function scopeAktif($query);
    public function scopeVaktiGelmis($query);
}

class NotificationChannel extends BaseModel
{
    protected $fillable = [
        'kullanici_id', 'kanal_tipi', 'aktif_mi',
        'ayarlar', 'son_kullanim'
    ];
    
    protected $casts = [
        'aktif_mi' => 'boolean',
        'ayarlar' => 'json',
        'son_kullanim' => 'datetime'
    ];
}
```

### 11. Dashboard ve Analitik Sistem

#### 11.1 Dashboard Servisleri
```php
class DashboardService
{
    public function getPortfolioSummary(): PortfolioSummary;
    public function getCustomerMetrics(): CustomerMetrics;
    public function getSalesMetrics(): SalesMetrics;
    public function getActivityMetrics(): ActivityMetrics;
    public function getRecentActivities(int $limit = 10): Collection;
}

class AnalyticsService
{
    public function generateTrendAnalysis(string $metric, array $dateRange): TrendAnalysis;
    public function calculateConversionRates(): ConversionRates;
    public function getTopPerformingProperties(): Collection;
    public function getCustomerSegmentation(): CustomerSegmentation;
}
```

#### 11.2 Dashboard Modelleri
```php
class DashboardWidget extends BaseModel
{
    protected $fillable = [
        'kullanici_id', 'widget_tipi', 'baslik',
        'ayarlar', 'pozisyon', 'boyut', 'aktif_mi'
    ];
    
    protected $casts = [
        'ayarlar' => 'json',
        'aktif_mi' => 'boolean'
    ];
    
    public function kullanici();
}

class AnalyticsMetric extends BaseModel
{
    protected $fillable = [
        'metrik_adi', 'metrik_degeri', 'metrik_tipi',
        'tarih', 'kategori', 'alt_kategori', 'meta_veri'
    ];
    
    protected $casts = [
        'metrik_degeri' => 'decimal:2',
        'tarih' => 'date',
        'meta_veri' => 'json'
    ];
    
    public function scopeTarihAraliginda($query, $baslangic, $bitis);
    public function scopeKategoriye($query, $kategori);
}
```

### 12. Gelişmiş Arama ve Filtreleme Sistemi

#### 12.1 Arama Servisleri
```php
class SearchService
{
    public function performAdvancedSearch(array $criteria): SearchResult;
    public function saveSearchCriteria(string $userId, array $criteria, string $name): SavedSearch;
    public function getSavedSearches(string $userId): Collection;
    public function getSearchSuggestions(string $query, string $type): array;
    public function logSearchActivity(string $userId, array $criteria, int $resultCount): void;
}

class FilterService
{
    public function applyFilters(Builder $query, array $filters): Builder;
    public function getAvailableFilters(string $modelType): array;
    public function validateFilters(array $filters, string $modelType): ValidationResult;
}
```

#### 12.2 Arama Modelleri
```php
class SavedSearch extends BaseModel
{
    protected $fillable = [
        'kullanici_id', 'ad', 'aciklama', 'arama_kriterleri',
        'model_tipi', 'kullanim_sayisi', 'son_kullanim'
    ];
    
    protected $casts = [
        'arama_kriterleri' => 'json',
        'son_kullanim' => 'datetime'
    ];
    
    public function kullanici();
    
    public function incrementUsage();
}

class SearchHistory extends BaseModel
{
    protected $fillable = [
        'kullanici_id', 'arama_terimi', 'arama_kriterleri',
        'sonuc_sayisi', 'arama_zamani', 'model_tipi'
    ];
    
    protected $casts = [
        'arama_kriterleri' => 'json',
        'arama_zamani' => 'datetime'
    ];
    
    public function kullanici();
}
```

## Gelişmiş Veri Modelleri

### 1. Genişletilmiş Veritabanı Şeması

#### 1.1 Import/Export Tabloları

**data_imports**
```sql
CREATE TABLE data_imports (
    id UUID PRIMARY KEY,
    dosya_adi VARCHAR(255) NOT NULL,
    dosya_yolu VARCHAR(500) NOT NULL,
    model_tipi VARCHAR(100) NOT NULL,
    durum ENUM('beklemede', 'isleniyor', 'tamamlandi', 'hata') DEFAULT 'beklemede',
    toplam_kayit INTEGER DEFAULT 0,
    basarili_kayit INTEGER DEFAULT 0,
    hatali_kayit INTEGER DEFAULT 0,
    hata_raporu JSON NULL,
    baslangic_zamani TIMESTAMP NULL,
    bitis_zamani TIMESTAMP NULL,
    kullanici_id UUID NOT NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**data_exports**
```sql
CREATE TABLE data_exports (
    id UUID PRIMARY KEY,
    dosya_adi VARCHAR(255) NOT NULL,
    dosya_yolu VARCHAR(500) NULL,
    model_tipi VARCHAR(100) NOT NULL,
    format ENUM('excel', 'csv', 'json') NOT NULL,
    filtreler JSON NULL,
    durum ENUM('beklemede', 'isleniyor', 'tamamlandi', 'hata') DEFAULT 'beklemede',
    toplam_kayit INTEGER DEFAULT 0,
    baslangic_zamani TIMESTAMP NULL,
    bitis_zamani TIMESTAMP NULL,
    kullanici_id UUID NOT NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**import_errors**
```sql
CREATE TABLE import_errors (
    id UUID PRIMARY KEY,
    import_id UUID NOT NULL,
    satir_no INTEGER NOT NULL,
    alan_adi VARCHAR(100) NOT NULL,
    hata_mesaji TEXT NOT NULL,
    veri_degeri TEXT NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (import_id) REFERENCES data_imports(id) ON DELETE CASCADE
);
```

#### 1.2 Sunum ve Rapor Tabloları

**presentations**
```sql
CREATE TABLE presentations (
    id UUID PRIMARY KEY,
    baslik VARCHAR(255) NOT NULL,
    aciklama TEXT NULL,
    template_id UUID NOT NULL,
    mulk_ids JSON NOT NULL,
    musteri_id UUID NULL,
    durum ENUM('taslak', 'hazir', 'gonderildi') DEFAULT 'taslak',
    pdf_yolu VARCHAR(500) NULL,
    olusturan_id UUID NOT NULL,
    son_guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL,
    FOREIGN KEY (template_id) REFERENCES presentation_templates(id),
    FOREIGN KEY (olusturan_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**presentation_templates**
```sql
CREATE TABLE presentation_templates (
    id UUID PRIMARY KEY,
    ad VARCHAR(255) NOT NULL,
    aciklama TEXT NULL,
    tasarim_ayarlari JSON NOT NULL,
    sayfa_duzeni JSON NOT NULL,
    renk_paleti JSON NOT NULL,
    font_ayarlari JSON NOT NULL,
    logo_pozisyonu VARCHAR(50) DEFAULT 'ust_sol',
    aktif_mi BOOLEAN DEFAULT TRUE,
    varsayilan_mi BOOLEAN DEFAULT FALSE,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinme_tarihi TIMESTAMP NULL
);
```

**reports**
```sql
CREATE TABLE reports (
    id UUID PRIMARY KEY,
    baslik VARCHAR(255) NOT NULL,
    rapor_tipi ENUM('portfoy', 'musteri', 'satis', 'aktivite', 'performans') NOT NULL,
    filtreler JSON NULL,
    veri_seti JSON NULL,
    grafik_ayarlari JSON NULL,
    durum ENUM('olusturuluyor', 'hazir', 'hata') DEFAULT 'olusturuluyor',
    dosya_yolu VARCHAR(500) NULL,
    olusturan_id UUID NOT NULL,
    olusturma_zamani TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (olusturan_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 1.3 Log ve İzleme Tabloları

**activity_logs**
```sql
CREATE TABLE activity_logs (
    id UUID PRIMARY KEY,
    kullanici_id UUID NULL,
    aksiyon VARCHAR(100) NOT NULL,
    model_tipi VARCHAR(100) NULL,
    model_id UUID NULL,
    eski_degerler JSON NULL,
    yeni_degerler JSON NULL,
    ip_adresi VARCHAR(45) NULL,
    user_agent TEXT NULL,
    oturum_id VARCHAR(100) NULL,
    zaman_damgasi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kullanici_zaman (kullanici_id, zaman_damgasi),
    INDEX idx_model (model_tipi, model_id),
    INDEX idx_aksiyon (aksiyon),
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE SET NULL
);
```

**system_logs**
```sql
CREATE TABLE system_logs (
    id UUID PRIMARY KEY,
    seviye ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL,
    mesaj TEXT NOT NULL,
    kanal VARCHAR(50) DEFAULT 'default',
    baglam JSON NULL,
    hata_detaylari JSON NULL,
    stack_trace TEXT NULL,
    zaman_damgasi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_seviye_zaman (seviye, zaman_damgasi),
    INDEX idx_kanal (kanal)
);
```

**performance_logs**
```sql
CREATE TABLE performance_logs (
    id UUID PRIMARY KEY,
    metrik_tipi ENUM('sayfa_yukleme', 'api_cagri', 'veritabani_sorgu') NOT NULL,
    sayfa_url VARCHAR(500) NULL,
    yukleme_suresi DECIMAL(8,3) NOT NULL,
    sorgu_sayisi INTEGER DEFAULT 0,
    bellek_kullanimi INTEGER NULL,
    kullanici_id UUID NULL,
    cihaz_bilgisi JSON NULL,
    tarayici_bilgisi JSON NULL,
    zaman_damgasi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metrik_zaman (metrik_tipi, zaman_damgasi),
    INDEX idx_yukleme_suresi (yukleme_suresi),
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE SET NULL
);
```

#### 1.4 Bildirim ve Hatırlatma Tabloları

**notifications**
```sql
CREATE TABLE notifications (
    id UUID PRIMARY KEY,
    kullanici_id UUID NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    icerik TEXT NOT NULL,
    tip ENUM('bilgi', 'uyari', 'hata', 'basari') DEFAULT 'bilgi',
    kanal ENUM('sistem', 'email', 'sms', 'push') DEFAULT 'sistem',
    okundu_mu BOOLEAN DEFAULT FALSE,
    okunma_zamani TIMESTAMP NULL,
    aksiyon_url VARCHAR(500) NULL,
    meta_veri JSON NULL,
    oncelik TINYINT DEFAULT 5,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kullanici_okundu (kullanici_id, okundu_mu),
    INDEX idx_tip_oncelik (tip, oncelik),
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**reminders**
```sql
CREATE TABLE reminders (
    id UUID PRIMARY KEY,
    kullanici_id UUID NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    aciklama TEXT NULL,
    hatirlatma_zamani TIMESTAMP NOT NULL,
    hatirlatma_tipi ENUM('arama', 'toplanti', 'email', 'ziyaret', 'gorev') NOT NULL,
    durum ENUM('aktif', 'tamamlandi', 'ertelendi', 'iptal') DEFAULT 'aktif',
    tekrar_ayari JSON NULL,
    ilgili_model_id UUID NULL,
    ilgili_model_tipi VARCHAR(100) NULL,
    sonuc TEXT NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kullanici_zaman (kullanici_id, hatirlatma_zamani),
    INDEX idx_durum (durum),
    INDEX idx_ilgili_model (ilgili_model_tipi, ilgili_model_id),
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**notification_channels**
```sql
CREATE TABLE notification_channels (
    id UUID PRIMARY KEY,
    kullanici_id UUID NOT NULL,
    kanal_tipi ENUM('email', 'sms', 'push', 'sistem') NOT NULL,
    aktif_mi BOOLEAN DEFAULT TRUE,
    ayarlar JSON NULL,
    son_kullanim TIMESTAMP NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_kullanici_kanal (kullanici_id, kanal_tipi),
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 1.5 Dashboard ve Analitik Tabloları

**dashboard_widgets**
```sql
CREATE TABLE dashboard_widgets (
    id UUID PRIMARY KEY,
    kullanici_id UUID NOT NULL,
    widget_tipi ENUM('portfoy_ozet', 'musteri_metrik', 'satis_grafik', 'aktivite_akis') NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    ayarlar JSON NULL,
    pozisyon JSON NOT NULL, -- {x: 0, y: 0, w: 4, h: 3}
    boyut JSON NOT NULL, -- {width: 400, height: 300}
    aktif_mi BOOLEAN DEFAULT TRUE,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kullanici_aktif (kullanici_id, aktif_mi),
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**analytics_metrics**
```sql
CREATE TABLE analytics_metrics (
    id UUID PRIMARY KEY,
    metrik_adi VARCHAR(100) NOT NULL,
    metrik_degeri DECIMAL(15,4) NOT NULL,
    metrik_tipi ENUM('sayac', 'oran', 'ortalama', 'toplam') NOT NULL,
    tarih DATE NOT NULL,
    kategori VARCHAR(50) NULL,
    alt_kategori VARCHAR(50) NULL,
    meta_veri JSON NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metrik_tarih (metrik_adi, tarih),
    INDEX idx_kategori_tarih (kategori, tarih),
    UNIQUE KEY unique_metrik_tarih_kategori (metrik_adi, tarih, kategori, alt_kategori)
);
```

#### 1.6 Arama ve Filtreleme Tabloları

**saved_searches**
```sql
CREATE TABLE saved_searches (
    id UUID PRIMARY KEY,
    kullanici_id UUID NOT NULL,
    ad VARCHAR(255) NOT NULL,
    aciklama TEXT NULL,
    arama_kriterleri JSON NOT NULL,
    model_tipi VARCHAR(100) NOT NULL,
    kullanim_sayisi INTEGER DEFAULT 0,
    son_kullanim TIMESTAMP NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kullanici_model (kullanici_id, model_tipi),
    INDEX idx_kullanim (kullanim_sayisi DESC),
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**search_history**
```sql
CREATE TABLE search_history (
    id UUID PRIMARY KEY,
    kullanici_id UUID NOT NULL,
    arama_terimi VARCHAR(500) NOT NULL,
    arama_kriterleri JSON NULL,
    sonuc_sayisi INTEGER NOT NULL,
    arama_zamani TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    model_tipi VARCHAR(100) NOT NULL,
    INDEX idx_kullanici_zaman (kullanici_id, arama_zamani),
    INDEX idx_model_zaman (model_tipi, arama_zamani),
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Gelişmiş Hata Yönetimi

### 1. Özel Exception Sınıfları

```php
// Import/Export Exceptions
class ImportValidationException extends Exception
{
    protected array $errors;
    
    public function __construct(array $errors, string $message = 'Import validation failed')
    {
        $this->errors = $errors;
        parent::__construct($message);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}

class ExportGenerationException extends Exception {}

// Presentation Exceptions
class PresentationTemplateNotFoundException extends Exception {}
class PresentationGenerationException extends Exception {}

// Notification Exceptions
class NotificationDeliveryException extends Exception {}
class InvalidNotificationChannelException extends Exception {}

// Analytics Exceptions
class MetricCalculationException extends Exception {}
class ReportGenerationException extends Exception {}
```

### 2. Gelişmiş Validation Kuralları

```php
class ImportValidationRules
{
    public static function getValidationRules(string $modelType): array
    {
        return match($modelType) {
            'mulk' => [
                'baslik' => 'required|string|max:255',
                'fiyat' => 'required|numeric|min:0',
                'metrekare' => 'required|numeric|min:0',
                'mulk_type' => 'required|in:arsa,isyeri,konut,turistik_tesis'
            ],
            'musteri' => [
                'ad' => 'required|string|max:255',
                'email' => 'nullable|email|unique:musteriler,email',
                'telefon' => 'nullable|string|max:20'
            ],
            default => []
        };
    }
}

class PresentationValidationRules
{
    public static function templateRules(): array
    {
        return [
            'ad' => 'required|string|max:255',
            'tasarim_ayarlari' => 'required|json',
            'sayfa_duzeni' => 'required|json',
            'renk_paleti' => 'required|json'
        ];
    }
}
```

## Gelişmiş Test Stratejisi

### 1. Import/Export Test Senaryoları

```php
class ImportServiceTest extends TestCase
{
    public function test_excel_import_with_valid_data()
    {
        // Test implementation
    }
    
    public function test_import_with_validation_errors()
    {
        // Test implementation
    }
    
    public function test_large_file_batch_processing()
    {
        // Test implementation
    }
}

class ExportServiceTest extends TestCase
{
    public function test_excel_export_generation()
    {
        // Test implementation
    }
    
    public function test_export_with_filters()
    {
        // Test implementation
    }
}
```

### 2. Presentation Test Senaryoları

```php
class PresentationServiceTest extends TestCase
{
    public function test_pdf_generation_with_template()
    {
        // Test implementation
    }
    
    public function test_custom_template_creation()
    {
        // Test implementation
    }
}
```

### 3. Performance Test Senaryoları

```php
class PerformanceTest extends TestCase
{
    public function test_large_dataset_search_performance()
    {
        // Test implementation
    }
    
    public function test_concurrent_user_load()
    {
        // Test implementation
    }
    
    public function test_memory_usage_optimization()
    {
        // Test implementation
    }
}
```

Bu genişletilmiş tasarım dokümanı, profesyonel gayrimenkul portföy yönetim sistemi için gerekli tüm gelişmiş özellikleri, veri modellerini ve teknik detayları kapsamaktadır. Sistem artık enterprise seviyede özellikler sunacak şekilde tasarlanmıştır.