<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\TalepEslestirmeService;
use App\Models\MusteriTalep;
use Illuminate\Support\Facades\Log;

class TalepEslestirmeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 dakika timeout
    public $tries = 3; // 3 kez deneme

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?string $talepId = null,
        public bool $tumTalepler = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TalepEslestirmeService $eslestirmeService): void
    {
        Log::info('TalepEslestirmeJob başlatıldı', [
            'talep_id' => $this->talepId,
            'tum_talepler' => $this->tumTalepler
        ]);

        try {
            if ($this->talepId) {
                // Belirli talep için eşleştirme
                $talep = MusteriTalep::find($this->talepId);
                
                if (!$talep) {
                    Log::warning('Talep bulunamadı', ['talep_id' => $this->talepId]);
                    return;
                }

                $eslestirmeler = $eslestirmeService->talepIcinEslestirmeYap($talep);
                
                Log::info('Talep eşleştirme tamamlandı', [
                    'talep_id' => $this->talepId,
                    'bulunan_eslestirme_sayisi' => $eslestirmeler->count()
                ]);

                // Eşleştirme bulunduysa talep aktivitesi ekle
                if ($eslestirmeler->isNotEmpty()) {
                    $talep->aktiviteEkle('otomatik_eslestirme', [
                        'bulunan_eslestirme_sayisi' => $eslestirmeler->count(),
                        'en_yuksek_skor' => $eslestirmeler->max('eslestirme_skoru'),
                        'job_id' => $this->job->getJobId(),
                    ]);
                }

            } elseif ($this->tumTalepler) {
                // Tüm talepler için eşleştirme
                $sonuclar = $eslestirmeService->tumTaleplerIcinEslestirmeYap();
                
                $toplamEslestirme = collect($sonuclar)->sum(function ($sonuc) {
                    return $sonuc['eslestirme_sayisi'] ?? 0;
                });

                Log::info('Tüm talepler için eşleştirme tamamlandı', [
                    'islenen_talep_sayisi' => count($sonuclar),
                    'toplam_eslestirme' => $toplamEslestirme
                ]);
            }

        } catch (\Exception $e) {
            Log::error('TalepEslestirmeJob hatası', [
                'talep_id' => $this->talepId,
                'tum_talepler' => $this->tumTalepler,
                'hata' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            throw $e; // Job'ı failed olarak işaretle
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TalepEslestirmeJob başarısız oldu', [
            'talep_id' => $this->talepId,
            'tum_talepler' => $this->tumTalepler,
            'hata' => $exception->getMessage()
        ]);

        // Eğer belirli bir talep için job başarısız olduysa, talep aktivitesi ekle
        if ($this->talepId) {
            try {
                $talep = MusteriTalep::find($this->talepId);
                if ($talep) {
                    $talep->aktiviteEkle('eslestirme_hatasi', [
                        'hata_mesaji' => $exception->getMessage(),
                        'job_id' => $this->job?->getJobId(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Talep aktivitesi eklenirken hata', [
                    'talep_id' => $this->talepId,
                    'hata' => $e->getMessage()
                ]);
            }
        }
    }
}