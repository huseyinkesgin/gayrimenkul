<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TalepEslestirmeService;
use App\Models\MusteriTalep;

class TalepEslestirmeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'talep:eslestir 
                            {--talep-id= : Belirli bir talep ID\'si için eşleştirme yap}
                            {--tumu : Tüm aktif talepler için eşleştirme yap}
                            {--otomatik : Otomatik eşleştirme kontrolü yap}
                            {--istatistik : Eşleştirme istatistiklerini göster}';

    /**
     * The console command description.
     */
    protected $description = 'Müşteri taleplerini portföy ile eşleştir';

    /**
     * Execute the console command.
     */
    public function handle(TalepEslestirmeService $eslestirmeService): int
    {
        $this->info('Talep Eşleştirme Sistemi');
        $this->line('========================');

        // İstatistik göster
        if ($this->option('istatistik')) {
            $this->gosterIstatistikler($eslestirmeService);
            return Command::SUCCESS;
        }

        // Otomatik eşleştirme kontrolü
        if ($this->option('otomatik')) {
            $this->info('Otomatik eşleştirme kontrolü başlatılıyor...');
            $eslestirmeService->otomatikEslestirmeKontrolu();
            $this->info('Otomatik eşleştirme kontrolü tamamlandı.');
            return Command::SUCCESS;
        }

        // Belirli talep için eşleştirme
        if ($talepId = $this->option('talep-id')) {
            return $this->belirliTalepIcinEslestir($eslestirmeService, $talepId);
        }

        // Tüm talepler için eşleştirme
        if ($this->option('tumu')) {
            return $this->tumTaleplerIcinEslestir($eslestirmeService);
        }

        // Hiçbir seçenek belirtilmemişse yardım göster
        $this->error('Lütfen bir seçenek belirtin:');
        $this->line('  --talep-id=ID    : Belirli bir talep için eşleştirme');
        $this->line('  --tumu           : Tüm aktif talepler için eşleştirme');
        $this->line('  --otomatik       : Otomatik eşleştirme kontrolü');
        $this->line('  --istatistik     : Eşleştirme istatistikleri');

        return Command::FAILURE;
    }

    /**
     * Belirli talep için eşleştirme yap
     */
    protected function belirliTalepIcinEslestir(TalepEslestirmeService $eslestirmeService, string $talepId): int
    {
        $talep = MusteriTalep::find($talepId);
        
        if (!$talep) {
            $this->error("Talep bulunamadı: {$talepId}");
            return Command::FAILURE;
        }

        $this->info("Talep eşleştirme başlatılıyor...");
        $this->line("Talep: {$talep->baslik}");
        $this->line("Müşteri: {$talep->musteri->ad} {$talep->musteri->soyad}");
        $this->line("Durum: {$talep->durum->label()}");

        if (!$talep->durum->isAktif()) {
            $this->warn('Bu talep aktif değil, eşleştirme yapılamaz.');
            return Command::FAILURE;
        }

        $eslestirmeler = $eslestirmeService->talepIcinEslestirmeYap($talep);

        if ($eslestirmeler->isEmpty()) {
            $this->warn('Bu talep için uygun mülk bulunamadı.');
            return Command::SUCCESS;
        }

        $this->info("Bulunan eşleştirme sayısı: {$eslestirmeler->count()}");
        
        // En iyi 5 eşleştirmeyi göster
        $this->table(
            ['Mülk ID', 'Mülk Tipi', 'Başlık', 'Skor', 'Fiyat', 'M²'],
            $eslestirmeler->take(5)->map(function ($eslestirme) {
                $mulk = $eslestirme['mulk'];
                return [
                    $mulk->id,
                    $mulk->getMulkType(),
                    $mulk->baslik,
                    number_format($eslestirme['eslestirme_skoru'], 3),
                    $mulk->formatted_price,
                    $mulk->formatted_area,
                ];
            })->toArray()
        );

        return Command::SUCCESS;
    }

    /**
     * Tüm talepler için eşleştirme yap
     */
    protected function tumTaleplerIcinEslestir(TalepEslestirmeService $eslestirmeService): int
    {
        $this->info('Tüm aktif talepler için eşleştirme başlatılıyor...');
        
        $aktifTalepSayisi = MusteriTalep::aktif()->count();
        $this->line("Toplam aktif talep sayısı: {$aktifTalepSayisi}");

        if ($aktifTalepSayisi === 0) {
            $this->warn('Aktif talep bulunamadı.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($aktifTalepSayisi);
        $bar->start();

        $sonuclar = [];
        $aktifTalepler = MusteriTalep::aktif()->get();

        foreach ($aktifTalepler as $talep) {
            try {
                $eslestirmeler = $eslestirmeService->talepIcinEslestirmeYap($talep);
                $sonuclar[] = [
                    'talep_id' => $talep->id,
                    'baslik' => $talep->baslik,
                    'eslestirme_sayisi' => $eslestirmeler->count(),
                    'en_yuksek_skor' => $eslestirmeler->max('eslestirme_skoru') ?? 0,
                ];
            } catch (\Exception $e) {
                $sonuclar[] = [
                    'talep_id' => $talep->id,
                    'baslik' => $talep->baslik,
                    'eslestirme_sayisi' => 0,
                    'en_yuksek_skor' => 0,
                    'hata' => $e->getMessage(),
                ];
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Özet rapor
        $toplamEslestirme = collect($sonuclar)->sum('eslestirme_sayisi');
        $eslestirmesiOlanTalep = collect($sonuclar)->where('eslestirme_sayisi', '>', 0)->count();
        $ortalamaSkor = collect($sonuclar)->where('en_yuksek_skor', '>', 0)->avg('en_yuksek_skor');

        $this->info('Eşleştirme Özeti:');
        $this->line("- Toplam eşleştirme: {$toplamEslestirme}");
        $this->line("- Eşleştirmesi olan talep: {$eslestirmesiOlanTalep}/{$aktifTalepSayisi}");
        $this->line("- Ortalama en yüksek skor: " . number_format($ortalamaSkor ?? 0, 3));

        // Detaylı sonuçları göster
        if ($this->confirm('Detaylı sonuçları görmek ister misiniz?')) {
            $this->table(
                ['Talep ID', 'Başlık', 'Eşleştirme Sayısı', 'En Yüksek Skor', 'Hata'],
                collect($sonuclar)->map(function ($sonuc) {
                    return [
                        $sonuc['talep_id'],
                        \Str::limit($sonuc['baslik'], 30),
                        $sonuc['eslestirme_sayisi'],
                        number_format($sonuc['en_yuksek_skor'], 3),
                        $sonuc['hata'] ?? '-',
                    ];
                })->toArray()
            );
        }

        return Command::SUCCESS;
    }

    /**
     * Eşleştirme istatistiklerini göster
     */
    protected function gosterIstatistikler(TalepEslestirmeService $eslestirmeService): void
    {
        $istatistikler = $eslestirmeService->eslestirmeIstatistikleri();

        $this->info('Eşleştirme İstatistikleri:');
        $this->line('========================');
        
        foreach ($istatistikler as $anahtar => $deger) {
            $etiket = match($anahtar) {
                'toplam_aktif_talep' => 'Toplam Aktif Talep',
                'eslestirmesi_olan_talep' => 'Eşleştirmesi Olan Talep',
                'toplam_eslestirme' => 'Toplam Eşleştirme',
                'yuksek_skorlu_eslestirme' => 'Yüksek Skorlu Eşleştirme (≥0.8)',
                'sunulmus_eslestirme' => 'Sunulmuş Eşleştirme',
                'bekleyen_eslestirme' => 'Bekleyen Eşleştirme',
                default => $anahtar
            };
            
            $this->line("- {$etiket}: {$deger}");
        }

        // Eşleştirme oranları
        if ($istatistikler['toplam_aktif_talep'] > 0) {
            $eslestirmeOrani = ($istatistikler['eslestirmesi_olan_talep'] / $istatistikler['toplam_aktif_talep']) * 100;
            $this->line("- Eşleştirme Oranı: " . number_format($eslestirmeOrani, 1) . "%");
        }

        if ($istatistikler['toplam_eslestirme'] > 0) {
            $yuksekSkorOrani = ($istatistikler['yuksek_skorlu_eslestirme'] / $istatistikler['toplam_eslestirme']) * 100;
            $this->line("- Yüksek Skor Oranı: " . number_format($yuksekSkorOrani, 1) . "%");
        }
    }
}