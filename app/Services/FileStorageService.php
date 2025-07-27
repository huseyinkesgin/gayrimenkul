<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Dosya Depolama Servisi
 * 
 * Bu servis gayrimenkul portföy sistemi için dosya depolama,
 * yedekleme ve temizleme işlemlerini yönetir.
 * 
 * Özellikler:
 * - Çoklu disk desteği
 * - Otomatik yedekleme
 * - Dosya sıkıştırma
 * - Temizleme işlemleri
 * - Depolama istatistikleri
 */
class FileStorageService
{
    private array $diskler;
    private string $varsayilanDisk;
    private string $yedekDisk;
    private bool $otomatikYedekleme;
    private int $eskiDosyaGunSayisi;

    public function __construct()
    {
        $this->diskler = ['public', 'local', 's3'];
        $this->varsayilanDisk = 'public';
        $this->yedekDisk = 'local';
        $this->otomatikYedekleme = config('filesystems.auto_backup', false);
        $this->eskiDosyaGunSayisi = config('filesystems.old_file_days', 30);
    }

    /**
     * Dosya kaydet
     */
    public function dosyaKaydet(
        string $icerik,
        string $dosyaYolu,
        string $disk = null,
        bool $yedekle = true
    ): array {
        try {
            $disk = $disk ?? $this->varsayilanDisk;
            
            // Ana diske kaydet
            $kaydedildi = Storage::disk($disk)->put($dosyaYolu, $icerik);
            
            if (!$kaydedildi) {
                return [
                    'basarili' => false,
                    'hata' => 'Dosya kaydedilemedi'
                ];
            }

            $sonuc = [
                'basarili' => true,
                'dosya_yolu' => $dosyaYolu,
                'disk' => $disk,
                'boyut' => strlen($icerik),
                'url' => Storage::disk($disk)->url($dosyaYolu)
            ];

            // Otomatik yedekleme
            if ($yedekle && $this->otomatikYedekleme) {
                $yedekSonuc = $this->dosyaYedekle($dosyaYolu, $disk);
                $sonuc['yedek'] = $yedekSonuc;
            }

            return $sonuc;

        } catch (\Exception $e) {
            Log::error('Dosya kaydetme hatası: ' . $e->getMessage(), [
                'dosya_yolu' => $dosyaYolu,
                'disk' => $disk
            ]);

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Dosya oku
     */
    public function dosyaOku(string $dosyaYolu, string $disk = null): array
    {
        try {
            $disk = $disk ?? $this->varsayilanDisk;
            
            if (!Storage::disk($disk)->exists($dosyaYolu)) {
                return [
                    'basarili' => false,
                    'hata' => 'Dosya bulunamadı'
                ];
            }

            $icerik = Storage::disk($disk)->get($dosyaYolu);
            $boyut = Storage::disk($disk)->size($dosyaYolu);
            $sonDegisiklik = Storage::disk($disk)->lastModified($dosyaYolu);

            return [
                'basarili' => true,
                'icerik' => $icerik,
                'boyut' => $boyut,
                'son_degisiklik' => date('Y-m-d H:i:s', $sonDegisiklik),
                'mime_type' => Storage::disk($disk)->mimeType($dosyaYolu)
            ];

        } catch (\Exception $e) {
            Log::error('Dosya okuma hatası: ' . $e->getMessage(), [
                'dosya_yolu' => $dosyaYolu,
                'disk' => $disk
            ]);

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Dosya sil
     */
    public function dosyaSil(string $dosyaYolu, string $disk = null, bool $yedektenDeSil = false): array
    {
        try {
            $disk = $disk ?? $this->varsayilanDisk;
            
            if (!Storage::disk($disk)->exists($dosyaYolu)) {
                return [
                    'basarili' => false,
                    'hata' => 'Dosya bulunamadı'
                ];
            }

            // Ana diskten sil
            $silindi = Storage::disk($disk)->delete($dosyaYolu);
            
            $sonuc = [
                'basarili' => $silindi,
                'dosya_yolu' => $dosyaYolu,
                'disk' => $disk
            ];

            // Yedekten de sil
            if ($yedektenDeSil && Storage::disk($this->yedekDisk)->exists($dosyaYolu)) {
                $yedekSilindi = Storage::disk($this->yedekDisk)->delete($dosyaYolu);
                $sonuc['yedek_silindi'] = $yedekSilindi;
            }

            return $sonuc;

        } catch (\Exception $e) {
            Log::error('Dosya silme hatası: ' . $e->getMessage(), [
                'dosya_yolu' => $dosyaYolu,
                'disk' => $disk
            ]);

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Dosya yedekle
     */
    public function dosyaYedekle(string $dosyaYolu, string $kaynakDisk = null): array
    {
        try {
            $kaynakDisk = $kaynakDisk ?? $this->varsayilanDisk;
            
            if (!Storage::disk($kaynakDisk)->exists($dosyaYolu)) {
                return [
                    'basarili' => false,
                    'hata' => 'Kaynak dosya bulunamadı'
                ];
            }

            // Yedek klasörü oluştur
            $yedekYolu = 'yedekler/' . date('Y/m/d') . '/' . $dosyaYolu;
            $icerik = Storage::disk($kaynakDisk)->get($dosyaYolu);
            
            $yedeklendi = Storage::disk($this->yedekDisk)->put($yedekYolu, $icerik);

            return [
                'basarili' => $yedeklendi,
                'kaynak_yol' => $dosyaYolu,
                'yedek_yol' => $yedekYolu,
                'kaynak_disk' => $kaynakDisk,
                'yedek_disk' => $this->yedekDisk
            ];

        } catch (\Exception $e) {
            Log::error('Dosya yedekleme hatası: ' . $e->getMessage(), [
                'dosya_yolu' => $dosyaYolu,
                'kaynak_disk' => $kaynakDisk
            ]);

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Dosya kopyala
     */
    public function dosyaKopyala(
        string $kaynakYol,
        string $hedefYol,
        string $kaynakDisk = null,
        string $hedefDisk = null
    ): array {
        try {
            $kaynakDisk = $kaynakDisk ?? $this->varsayilanDisk;
            $hedefDisk = $hedefDisk ?? $this->varsayilanDisk;
            
            if (!Storage::disk($kaynakDisk)->exists($kaynakYol)) {
                return [
                    'basarili' => false,
                    'hata' => 'Kaynak dosya bulunamadı'
                ];
            }

            $icerik = Storage::disk($kaynakDisk)->get($kaynakYol);
            $kopyalandi = Storage::disk($hedefDisk)->put($hedefYol, $icerik);

            return [
                'basarili' => $kopyalandi,
                'kaynak_yol' => $kaynakYol,
                'hedef_yol' => $hedefYol,
                'kaynak_disk' => $kaynakDisk,
                'hedef_disk' => $hedefDisk
            ];

        } catch (\Exception $e) {
            Log::error('Dosya kopyalama hatası: ' . $e->getMessage(), [
                'kaynak_yol' => $kaynakYol,
                'hedef_yol' => $hedefYol
            ]);

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Dosya taşı
     */
    public function dosyaTasi(
        string $kaynakYol,
        string $hedefYol,
        string $kaynakDisk = null,
        string $hedefDisk = null
    ): array {
        try {
            // Önce kopyala
            $kopyaSonuc = $this->dosyaKopyala($kaynakYol, $hedefYol, $kaynakDisk, $hedefDisk);
            
            if (!$kopyaSonuc['basarili']) {
                return $kopyaSonuc;
            }

            // Sonra kaynağı sil
            $silmeSonuc = $this->dosyaSil($kaynakYol, $kaynakDisk);
            
            return [
                'basarili' => $silmeSonuc['basarili'],
                'kaynak_yol' => $kaynakYol,
                'hedef_yol' => $hedefYol,
                'kaynak_disk' => $kaynakDisk ?? $this->varsayilanDisk,
                'hedef_disk' => $hedefDisk ?? $this->varsayilanDisk
            ];

        } catch (\Exception $e) {
            Log::error('Dosya taşıma hatası: ' . $e->getMessage());

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Klasör oluştur
     */
    public function klasorOlustur(string $klasorYolu, string $disk = null): array
    {
        try {
            $disk = $disk ?? $this->varsayilanDisk;
            
            $olusturuldu = Storage::disk($disk)->makeDirectory($klasorYolu);

            return [
                'basarili' => $olusturuldu,
                'klasor_yolu' => $klasorYolu,
                'disk' => $disk
            ];

        } catch (\Exception $e) {
            Log::error('Klasör oluşturma hatası: ' . $e->getMessage(), [
                'klasor_yolu' => $klasorYolu,
                'disk' => $disk
            ]);

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Klasör sil
     */
    public function klasorSil(string $klasorYolu, string $disk = null): array
    {
        try {
            $disk = $disk ?? $this->varsayilanDisk;
            
            if (!Storage::disk($disk)->exists($klasorYolu)) {
                return [
                    'basarili' => false,
                    'hata' => 'Klasör bulunamadı'
                ];
            }

            $silindi = Storage::disk($disk)->deleteDirectory($klasorYolu);

            return [
                'basarili' => $silindi,
                'klasor_yolu' => $klasorYolu,
                'disk' => $disk
            ];

        } catch (\Exception $e) {
            Log::error('Klasör silme hatası: ' . $e->getMessage(), [
                'klasor_yolu' => $klasorYolu,
                'disk' => $disk
            ]);

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Klasör içeriğini listele
     */
    public function klasorIcerigiListele(string $klasorYolu = '', string $disk = null): array
    {
        try {
            $disk = $disk ?? $this->varsayilanDisk;
            
            $dosyalar = Storage::disk($disk)->files($klasorYolu);
            $klasorler = Storage::disk($disk)->directories($klasorYolu);

            $dosyaBilgileri = [];
            foreach ($dosyalar as $dosya) {
                $dosyaBilgileri[] = [
                    'ad' => basename($dosya),
                    'yol' => $dosya,
                    'boyut' => Storage::disk($disk)->size($dosya),
                    'son_degisiklik' => Storage::disk($disk)->lastModified($dosya),
                    'mime_type' => Storage::disk($disk)->mimeType($dosya),
                    'url' => Storage::disk($disk)->url($dosya)
                ];
            }

            return [
                'basarili' => true,
                'klasor_yolu' => $klasorYolu,
                'dosyalar' => $dosyaBilgileri,
                'klasorler' => $klasorler,
                'toplam_dosya' => count($dosyalar),
                'toplam_klasor' => count($klasorler)
            ];

        } catch (\Exception $e) {
            Log::error('Klasör listeleme hatası: ' . $e->getMessage(), [
                'klasor_yolu' => $klasorYolu,
                'disk' => $disk
            ]);

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Depolama istatistikleri
     */
    public function depolamaIstatistikleri(string $disk = null): array
    {
        try {
            $disk = $disk ?? $this->varsayilanDisk;
            
            $tumDosyalar = Storage::disk($disk)->allFiles();
            $toplamBoyut = 0;
            $dosyaTipleri = [];

            foreach ($tumDosyalar as $dosya) {
                $boyut = Storage::disk($disk)->size($dosya);
                $toplamBoyut += $boyut;
                
                $uzanti = pathinfo($dosya, PATHINFO_EXTENSION);
                if (!isset($dosyaTipleri[$uzanti])) {
                    $dosyaTipleri[$uzanti] = ['adet' => 0, 'boyut' => 0];
                }
                $dosyaTipleri[$uzanti]['adet']++;
                $dosyaTipleri[$uzanti]['boyut'] += $boyut;
            }

            return [
                'basarili' => true,
                'disk' => $disk,
                'toplam_dosya' => count($tumDosyalar),
                'toplam_boyut' => $toplamBoyut,
                'toplam_boyut_formatli' => $this->boyutFormatla($toplamBoyut),
                'dosya_tipleri' => $dosyaTipleri,
                'ortalama_dosya_boyutu' => count($tumDosyalar) > 0 ? $toplamBoyut / count($tumDosyalar) : 0
            ];

        } catch (\Exception $e) {
            Log::error('Depolama istatistikleri hatası: ' . $e->getMessage(), [
                'disk' => $disk
            ]);

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Eski dosyaları temizle
     */
    public function eskiDosyalariTemizle(string $disk = null, int $gunSayisi = null): array
    {
        try {
            $disk = $disk ?? $this->varsayilanDisk;
            $gunSayisi = $gunSayisi ?? $this->eskiDosyaGunSayisi;
            
            $silinecekTarih = time() - ($gunSayisi * 24 * 60 * 60);
            $tumDosyalar = Storage::disk($disk)->allFiles();
            
            $silinenDosyalar = [];
            $silinenBoyut = 0;

            foreach ($tumDosyalar as $dosya) {
                $sonDegisiklik = Storage::disk($disk)->lastModified($dosya);
                
                if ($sonDegisiklik < $silinecekTarih) {
                    $boyut = Storage::disk($disk)->size($dosya);
                    
                    if (Storage::disk($disk)->delete($dosya)) {
                        $silinenDosyalar[] = $dosya;
                        $silinenBoyut += $boyut;
                    }
                }
            }

            Log::info('Eski dosya temizleme tamamlandı', [
                'disk' => $disk,
                'gun_sayisi' => $gunSayisi,
                'silinen_adet' => count($silinenDosyalar),
                'silinen_boyut' => $silinenBoyut
            ]);

            return [
                'basarili' => true,
                'disk' => $disk,
                'gun_sayisi' => $gunSayisi,
                'silinen_dosyalar' => $silinenDosyalar,
                'silinen_adet' => count($silinenDosyalar),
                'silinen_boyut' => $silinenBoyut,
                'silinen_boyut_formatli' => $this->boyutFormatla($silinenBoyut)
            ];

        } catch (\Exception $e) {
            Log::error('Eski dosya temizleme hatası: ' . $e->getMessage(), [
                'disk' => $disk,
                'gun_sayisi' => $gunSayisi
            ]);

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Dosya sıkıştır
     */
    public function dosyaSikistir(array $dosyaYollari, string $zipAdi, string $disk = null): array
    {
        try {
            $disk = $disk ?? $this->varsayilanDisk;
            
            $zip = new \ZipArchive();
            $zipYolu = storage_path('app/temp/' . $zipAdi);
            
            // Temp klasörü oluştur
            if (!is_dir(dirname($zipYolu))) {
                mkdir(dirname($zipYolu), 0755, true);
            }

            if ($zip->open($zipYolu, \ZipArchive::CREATE) !== TRUE) {
                return [
                    'basarili' => false,
                    'hata' => 'ZIP dosyası oluşturulamadı'
                ];
            }

            $eklenenDosyalar = [];
            foreach ($dosyaYollari as $dosyaYolu) {
                if (Storage::disk($disk)->exists($dosyaYolu)) {
                    $icerik = Storage::disk($disk)->get($dosyaYolu);
                    $zip->addFromString(basename($dosyaYolu), $icerik);
                    $eklenenDosyalar[] = $dosyaYolu;
                }
            }

            $zip->close();

            // ZIP dosyasını storage'a taşı
            $hedefYol = 'arsivler/' . date('Y/m/d') . '/' . $zipAdi;
            $zipIcerik = file_get_contents($zipYolu);
            Storage::disk($disk)->put($hedefYol, $zipIcerik);
            
            // Temp dosyayı sil
            unlink($zipYolu);

            return [
                'basarili' => true,
                'zip_yolu' => $hedefYol,
                'zip_boyutu' => strlen($zipIcerik),
                'eklenen_dosyalar' => $eklenenDosyalar,
                'eklenen_adet' => count($eklenenDosyalar),
                'url' => Storage::disk($disk)->url($hedefYol)
            ];

        } catch (\Exception $e) {
            Log::error('Dosya sıkıştırma hatası: ' . $e->getMessage());

            return [
                'basarili' => false,
                'hata' => $e->getMessage()
            ];
        }
    }

    /**
     * Boyut formatla
     */
    private function boyutFormatla(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Benzersiz dosya adı oluştur
     */
    public function benzersizDosyaAdiOlustur(string $orijinalAd, string $klasorYolu = '', string $disk = null): string
    {
        $disk = $disk ?? $this->varsayilanDisk;
        $dosyaAdi = pathinfo($orijinalAd, PATHINFO_FILENAME);
        $uzanti = pathinfo($orijinalAd, PATHINFO_EXTENSION);
        
        $sayac = 1;
        $yeniAd = $orijinalAd;
        
        while (Storage::disk($disk)->exists($klasorYolu . '/' . $yeniAd)) {
            $yeniAd = $dosyaAdi . '_' . $sayac . '.' . $uzanti;
            $sayac++;
        }
        
        return $yeniAd;
    }

    /**
     * Disk durumunu kontrol et
     */
    public function diskDurumuKontrol(string $disk = null): array
    {
        try {
            $disk = $disk ?? $this->varsayilanDisk;
            
            // Disk erişilebilir mi?
            $erisimTesti = Storage::disk($disk)->put('test_file.txt', 'test');
            if ($erisimTesti) {
                Storage::disk($disk)->delete('test_file.txt');
            }

            return [
                'basarili' => true,
                'disk' => $disk,
                'erisim' => $erisimTesti,
                'durum' => 'Çalışıyor',
                'test_tarihi' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            return [
                'basarili' => false,
                'disk' => $disk,
                'erisim' => false,
                'durum' => 'Hata: ' . $e->getMessage(),
                'test_tarihi' => now()->toISOString()
            ];
        }
    }
}