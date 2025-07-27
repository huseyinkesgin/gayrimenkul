<?php

namespace App\Services;

use App\Models\Resim;
use App\Enums\ResimKategorisi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Resim Upload ve İşleme Servisi
 * 
 * Bu servis gayrimenkul portföy sistemi için resim yükleme,
 * boyutlandırma, optimizasyon ve depolama işlemlerini yönetir.
 * 
 * Özellikler:
 * - Otomatik boyutlandırma (thumbnail, medium, large)
 * - Resim optimizasyonu ve sıkıştırma
 * - Watermark ekleme
 * - EXIF veri temizleme
 * - Güvenlik kontrolleri
 * - Çoklu format desteği
 */
class ResimUploadService
{
    private ImageManager $imageManager;
    private array $allowedMimeTypes;
    private array $imageSizes;
    private int $maxFileSize;
    private int $jpegQuality;
    private int $pngQuality;
    private bool $watermarkEnabled;
    private string $watermarkPath;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
        
        // Yapılandırma ayarları
        $this->allowedMimeTypes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/tiff'
        ];

        // Resim boyutları (genişlik x yükseklik)
        $this->imageSizes = [
            'thumbnail' => ['width' => 150, 'height' => 150, 'quality' => 80],
            'small' => ['width' => 300, 'height' => 300, 'quality' => 85],
            'medium' => ['width' => 800, 'height' => 600, 'quality' => 90],
            'large' => ['width' => 1200, 'height' => 900, 'quality' => 95],
            'original' => ['quality' => 100] // Orijinal boyut korunur
        ];

        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->jpegQuality = 90;
        $this->pngQuality = 9;
        $this->watermarkEnabled = config('app.watermark_enabled', false);
        $this->watermarkPath = storage_path('app/watermarks/logo.png');
    }

    /**
     * Resim yükle ve işle
     */
    public function uploadResim(
        UploadedFile $file,
        string $imagableType,
        string $imagableId,
        ResimKategorisi $kategori,
        array $boyutlar = null,
        bool $watermarkEkle = false,
        array $additionalData = []
    ): array {
        try {
            // Güvenlik kontrolleri
            $guvenlikKontrol = $this->guvenlikKontrolleri($file);
            if (!$guvenlikKontrol['gecerli']) {
                return [
                    'basarili' => false,
                    'hatalar' => $guvenlikKontrol['hatalar']
                ];
            }

            // Dosya adı oluştur
            $dosyaAdi = $this->guvenliDosyaAdiOlustur($file);
            $klasorYolu = $this->klasorYoluOlustur($imagableType, $imagableId, $kategori);

            // Orijinal resmi yükle
            $orijinalYol = $this->orijinalResmiYukle($file, $klasorYolu, $dosyaAdi);

            // Farklı boyutlarda resimleri oluştur
            $boyutlar = $boyutlar ?? array_keys($this->imageSizes);
            $olusturulanResimler = $this->farkliBoytlardaOlustur(
                $file, 
                $klasorYolu, 
                $dosyaAdi, 
                $boyutlar,
                $watermarkEkle
            );

            // Resim metadata'sını çıkar
            $metadata = $this->resimMetadatasiCikar($file, $orijinalYol);

            // Veritabanına kaydet
            $resim = $this->veritabaniKaydiOlustur(
                $file,
                $orijinalYol,
                $olusturulanResimler,
                $imagableType,
                $imagableId,
                $kategori,
                $metadata,
                $additionalData
            );

            return [
                'basarili' => true,
                'resim' => $resim,
                'orijinal_yol' => $orijinalYol,
                'boyutlar' => $olusturulanResimler,
                'metadata' => $metadata,
                'mesaj' => 'Resim başarıyla yüklendi ve işlendi.'
            ];

        } catch (\Exception $e) {
            Log::error('Resim upload hatası: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'basarili' => false,
                'hatalar' => ['Resim yüklenirken beklenmeyen bir hata oluştu: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Toplu resim yükleme
     */
    public function topluResimYukle(
        array $files,
        string $imagableType,
        string $imagableId,
        ResimKategorisi $kategori,
        array $boyutlar = null,
        bool $watermarkEkle = false,
        array $additionalData = []
    ): array {
        $sonuclar = [];
        $basarili = 0;
        $hatali = 0;

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                $sonuclar[$index] = [
                    'basarili' => false,
                    'hatalar' => ['Geçersiz dosya formatı']
                ];
                $hatali++;
                continue;
            }

            $sonuc = $this->uploadResim(
                $file, 
                $imagableType, 
                $imagableId, 
                $kategori, 
                $boyutlar, 
                $watermarkEkle, 
                $additionalData
            );
            
            $sonuclar[$index] = $sonuc;

            if ($sonuc['basarili']) {
                $basarili++;
            } else {
                $hatali++;
            }
        }

        return [
            'sonuclar' => $sonuclar,
            'ozet' => [
                'toplam' => count($files),
                'basarili' => $basarili,
                'hatali' => $hatali
            ]
        ];
    }

    /**
     * Güvenlik kontrolleri
     */
    private function guvenlikKontrolleri(UploadedFile $file): array
    {
        $hatalar = [];

        // Dosya boyutu kontrolü
        if ($file->getSize() > $this->maxFileSize) {
            $hatalar[] = 'Dosya boyutu çok büyük. Maksimum ' . ($this->maxFileSize / 1024 / 1024) . 'MB olabilir.';
        }

        // MIME type kontrolü
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            $hatalar[] = 'Desteklenmeyen dosya formatı. İzin verilen formatlar: ' . implode(', ', $this->allowedMimeTypes);
        }

        // Dosya uzantısı kontrolü
        $uzanti = strtolower($file->getClientOriginalExtension());
        $izinVerilenUzantilar = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];
        if (!in_array($uzanti, $izinVerilenUzantilar)) {
            $hatalar[] = 'Desteklenmeyen dosya uzantısı: ' . $uzanti;
        }

        // Dosya içeriği kontrolü (gerçek resim mi?)
        try {
            $resimBilgisi = getimagesize($file->getPathname());
            if ($resimBilgisi === false) {
                $hatalar[] = 'Dosya geçerli bir resim dosyası değil.';
            }
        } catch (\Exception $e) {
            $hatalar[] = 'Resim dosyası doğrulanamadı.';
        }

        // Zararlı içerik kontrolü
        if ($this->zararliIcerikKontrolu($file)) {
            $hatalar[] = 'Dosya güvenlik kontrolünden geçemedi.';
        }

        return [
            'gecerli' => empty($hatalar),
            'hatalar' => $hatalar
        ];
    }

    /**
     * Zararlı içerik kontrolü
     */
    private function zararliIcerikKontrolu(UploadedFile $file): bool
    {
        // Dosya içeriğinde PHP kodu var mı kontrol et
        $icerik = file_get_contents($file->getPathname());
        $zararliPatternler = [
            '/<\?php/',
            '/<\?=/',
            '/<script/',
            '/javascript:/',
            '/vbscript:/',
            '/onload=/i',
            '/onerror=/i'
        ];

        foreach ($zararliPatternler as $pattern) {
            if (preg_match($pattern, $icerik)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Güvenli dosya adı oluştur
     */
    private function guvenliDosyaAdiOlustur(UploadedFile $file): string
    {
        $orijinalAd = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $uzanti = strtolower($file->getClientOriginalExtension());

        // Türkçe karakterleri dönüştür ve güvenli hale getir
        $guvenliAd = Str::slug($orijinalAd, '_');
        $benzersizId = Str::random(8);
        $zaman = time();

        return "{$guvenliAd}_{$zaman}_{$benzersizId}.{$uzanti}";
    }

    /**
     * Klasör yolu oluştur
     */
    private function klasorYoluOlustur(string $imagableType, string $imagableId, ResimKategorisi $kategori): string
    {
        $modelAdi = class_basename($imagableType);
        $tarih = date('Y/m/d');
        
        return "resimler/{$modelAdi}/{$imagableId}/{$kategori->value}/{$tarih}";
    }

    /**
     * Orijinal resmi yükle
     */
    private function orijinalResmiYukle(UploadedFile $file, string $klasorYolu, string $dosyaAdi): string
    {
        $tamYol = $klasorYolu . '/original/' . $dosyaAdi;
        
        // Klasörü oluştur
        Storage::disk('public')->makeDirectory(dirname($tamYol));
        
        // Dosyayı kaydet
        $file->storeAs($klasorYolu . '/original', $dosyaAdi, 'public');
        
        return $tamYol;
    }

    /**
     * Farklı boyutlarda resimler oluştur
     */
    private function farkliBoytlardaOlustur(
        UploadedFile $file,
        string $klasorYolu,
        string $dosyaAdi,
        array $boyutlar,
        bool $watermarkEkle = false
    ): array {
        $olusturulanResimler = [];

        // Resmi yükle
        $resim = $this->imageManager->read($file->getPathname());

        // EXIF verilerini temizle
        $resim = $this->exifVerileriniTemizle($resim);

        foreach ($boyutlar as $boyutAdi) {
            if (!isset($this->imageSizes[$boyutAdi])) {
                continue;
            }

            $boyutAyarlari = $this->imageSizes[$boyutAdi];
            $boyutluResim = clone $resim;

            // Boyutlandırma (original hariç)
            if ($boyutAdi !== 'original') {
                $boyutluResim = $this->resimBoyutlandir(
                    $boyutluResim,
                    $boyutAyarlari['width'],
                    $boyutAyarlari['height']
                );
            }

            // Watermark ekle
            if ($watermarkEkle && $this->watermarkEnabled) {
                $boyutluResim = $this->watermarkEkle($boyutluResim);
            }

            // Optimizasyon uygula
            $boyutluResim = $this->resimOptimizasyonu($boyutluResim, $boyutAyarlari['quality']);

            // Kaydet
            $boyutKlasorYolu = $klasorYolu . '/' . $boyutAdi;
            Storage::disk('public')->makeDirectory($boyutKlasorYolu);
            $kayitYolu = storage_path('app/public/' . $boyutKlasorYolu . '/' . $dosyaAdi);
            $boyutluResim->save($kayitYolu);

            $olusturulanResimler[$boyutAdi] = [
                'yol' => $boyutKlasorYolu . '/' . $dosyaAdi,
                'url' => Storage::disk('public')->url($boyutKlasorYolu . '/' . $dosyaAdi),
                'boyut' => $this->dosyaBoyutuAl($kayitYolu),
                'genislik' => $boyutluResim->width(),
                'yukseklik' => $boyutluResim->height()
            ];
        }

        return $olusturulanResimler;
    }

    /**
     * Resim boyutlandır
     */
    private function resimBoyutlandir($resim, int $genislik, int $yukseklik)
    {
        // Orantılı boyutlandırma (aspect ratio korunur)
        return $resim->resize($genislik, $yukseklik, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize(); // Küçük resimleri büyütme
        });
    }

    /**
     * Watermark ekle
     */
    private function watermarkEkle($resim)
    {
        if (!file_exists($this->watermarkPath)) {
            return $resim;
        }

        try {
            $watermark = $this->imageManager->read($this->watermarkPath);
            
            // Watermark boyutunu resmin %10'u kadar yap
            $watermarkGenislik = (int)($resim->width() * 0.1);
            $watermark->resize($watermarkGenislik, null, function ($constraint) {
                $constraint->aspectRatio();
            });

            // Sağ alt köşeye yerleştir
            $resim->place($watermark, 'bottom-right', 10, 10);
        } catch (\Exception $e) {
            Log::warning('Watermark eklenemedi: ' . $e->getMessage());
        }

        return $resim;
    }

    /**
     * Resim optimizasyonu
     */
    private function resimOptimizasyonu($resim, int $kalite)
    {
        // Kalite ayarını uygula
        return $resim->encode(quality: $kalite);
    }

    /**
     * EXIF verilerini temizle
     */
    private function exifVerileriniTemizle($resim)
    {
        // Intervention Image otomatik olarak EXIF verilerini temizler
        // Ek güvenlik için manuel temizleme de yapabiliriz
        return $resim;
    }

    /**
     * Resim metadata'sını çıkar
     */
    private function resimMetadatasiCikar(UploadedFile $file, string $kayitYolu): array
    {
        $metadata = [
            'orijinal_ad' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'boyut' => $file->getSize(),
            'upload_tarihi' => now()->toISOString()
        ];

        try {
            $resimBilgisi = getimagesize($file->getPathname());
            if ($resimBilgisi) {
                $metadata['genislik'] = $resimBilgisi[0];
                $metadata['yukseklik'] = $resimBilgisi[1];
                $metadata['renk_kanali'] = $resimBilgisi['channels'] ?? null;
                $metadata['bit_derinligi'] = $resimBilgisi['bits'] ?? null;
            }

            // EXIF verileri (temizlenmeden önce)
            if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/tiff'])) {
                $exifData = @exif_read_data($file->getPathname());
                if ($exifData) {
                    $metadata['exif'] = [
                        'kamera' => $exifData['Make'] ?? null,
                        'model' => $exifData['Model'] ?? null,
                        'cekim_tarihi' => $exifData['DateTime'] ?? null,
                        'gps_lat' => $this->gpsKoordinatiCikar($exifData, 'GPSLatitude', 'GPSLatitudeRef'),
                        'gps_lon' => $this->gpsKoordinatiCikar($exifData, 'GPSLongitude', 'GPSLongitudeRef')
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Resim metadata çıkarılamadı: ' . $e->getMessage());
        }

        return $metadata;
    }

    /**
     * GPS koordinatını çıkar
     */
    private function gpsKoordinatiCikar(array $exifData, string $koordinatKey, string $refKey): ?float
    {
        if (!isset($exifData[$koordinatKey]) || !isset($exifData[$refKey])) {
            return null;
        }

        $koordinat = $exifData[$koordinatKey];
        $ref = $exifData[$refKey];

        if (!is_array($koordinat) || count($koordinat) < 3) {
            return null;
        }

        // DMS (Degrees, Minutes, Seconds) formatından decimal'e çevir
        $derece = $this->rasyonelSayiyiCevir($koordinat[0]);
        $dakika = $this->rasyonelSayiyiCevir($koordinat[1]);
        $saniye = $this->rasyonelSayiyiCevir($koordinat[2]);

        $decimal = $derece + ($dakika / 60) + ($saniye / 3600);

        // Güney ve Batı için negatif yap
        if (in_array($ref, ['S', 'W'])) {
            $decimal *= -1;
        }

        return $decimal;
    }

    /**
     * Rasyonel sayıyı çevir
     */
    private function rasyonelSayiyiCevir(string $rasyonel): float
    {
        $parts = explode('/', $rasyonel);
        if (count($parts) === 2 && $parts[1] != 0) {
            return (float)$parts[0] / (float)$parts[1];
        }
        return (float)$rasyonel;
    }

    /**
     * Dosya boyutunu al
     */
    private function dosyaBoyutuAl(string $dosyaYolu): int
    {
        return file_exists($dosyaYolu) ? filesize($dosyaYolu) : 0;
    }

    /**
     * Veritabanı kaydı oluştur
     */
    private function veritabaniKaydiOlustur(
        UploadedFile $file,
        string $orijinalYol,
        array $olusturulanResimler,
        string $imagableType,
        string $imagableId,
        ResimKategorisi $kategori,
        array $metadata,
        array $additionalData
    ): Resim {
        return Resim::create([
            'baslik' => $additionalData['baslik'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'aciklama' => $additionalData['aciklama'] ?? null,
            'kategori' => $kategori,
            'dosya_adi' => basename($orijinalYol),
            'orijinal_dosya_adi' => $file->getClientOriginalName(),
            'dosya_yolu' => $orijinalYol,
            'dosya_boyutu' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'genislik' => $metadata['genislik'] ?? null,
            'yukseklik' => $metadata['yukseklik'] ?? null,
            'boyutlar' => $olusturulanResimler,
            'metadata' => $metadata,
            'imagable_type' => $imagableType,
            'imagable_id' => $imagableId,
            'olusturan_id' => Auth::id(),
            'aktif_mi' => true,
            'sira' => $additionalData['sira'] ?? 0,
            'ana_resim_mi' => $additionalData['ana_resim_mi'] ?? false
        ]);
    }

    /**
     * Resim sil
     */
    public function resimSil(Resim $resim): bool
    {
        try {
            // Tüm boyutlardaki resimleri sil
            foreach (array_keys($this->imageSizes) as $boyut) {
                $klasorYolu = dirname($resim->dosya_yolu);
                $dosyaAdi = basename($resim->dosya_yolu);
                $dosyaYolu = $klasorYolu . '/' . $boyut . '/' . $dosyaAdi;
                
                if (Storage::disk('public')->exists($dosyaYolu)) {
                    Storage::disk('public')->delete($dosyaYolu);
                }
            }

            // Veritabanından sil
            $resim->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Resim silme hatası: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Resim boyutlarını al
     */
    public function getResimBoyutlari(): array
    {
        return $this->imageSizes;
    }

    /**
     * İzin verilen MIME tiplerini al
     */
    public function getIzinVerilenMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * Maksimum dosya boyutunu al
     */
    public function getMaxDosyaBoyutu(): int
    {
        return $this->maxFileSize;
    }

    /**
     * Resim URL'ini oluştur
     */
    public function resimUrlOlustur(Resim $resim, string $boyut = 'medium'): ?string
    {
        if (isset($resim->boyutlar[$boyut])) {
            return $resim->boyutlar[$boyut]['url'];
        }

        // Fallback olarak orijinal resim
        return Storage::disk('public')->url($resim->dosya_yolu);
    }

    /**
     * Resim bilgilerini al
     */
    public function resimBilgileriAl(Resim $resim): array
    {
        $bilgiler = [];
        
        foreach (array_keys($this->imageSizes) as $boyut) {
            if (isset($resim->boyutlar[$boyut])) {
                $bilgiler[$boyut] = $resim->boyutlar[$boyut];
            }
        }

        return $bilgiler;
    }

    /**
     * Resim istatistikleri
     */
    public function resimIstatistikleriAl(string $imagableType = null, string $imagableId = null): array
    {
        $query = Resim::where('aktif_mi', true);

        if ($imagableType && $imagableId) {
            $query->where('imagable_type', $imagableType)
                  ->where('imagable_id', $imagableId);
        }

        $toplamResim = $query->count();
        $toplamBoyut = $query->sum('dosya_boyutu');
        
        $kategorilereBolum = $query->selectRaw('kategori, COUNT(*) as adet, SUM(dosya_boyutu) as toplam_boyut')
                                  ->groupBy('kategori')
                                  ->get()
                                  ->mapWithKeys(function ($item) {
                                      return [
                                          $item->kategori->value => [
                                              'adet' => $item->adet,
                                              'toplam_boyut' => $item->toplam_boyut,
                                              'ortalama_boyut' => $item->adet > 0 ? $item->toplam_boyut / $item->adet : 0
                                          ]
                                      ];
                                  });

        return [
            'toplam_resim' => $toplamResim,
            'toplam_boyut' => $toplamBoyut,
            'toplam_boyut_formatli' => $this->dosyaBoyutuFormatla($toplamBoyut),
            'ortalama_boyut' => $toplamResim > 0 ? $toplamBoyut / $toplamResim : 0,
            'kategorilere_bolum' => $kategorilereBolum,
            'son_guncelleme' => $query->latest('created_at')->first()?->created_at
        ];
    }

    /**
     * Dosya boyutunu formatla
     */
    private function dosyaBoyutuFormatla(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}