<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Services\DokumanYonetimService;
use App\Models\Dokuman;
use App\Enums\DokumanTipi;
use Illuminate\Support\Collection;

/**
 * Döküman Yönetimi Livewire Bileşeni
 * 
 * Bu bileşen, herhangi bir model için döküman yönetimi
 * arayüzü sağlar ve tüm gereksinimleri karşılar.
 */
class DokumanYonetimi extends Component
{
    use WithFileUploads, WithPagination;

    // Model bilgileri
    public string $documentableType;
    public string $documentableId;
    public ?string $mulkType = null;

    // Upload form
    public $files = [];
    public string $selectedDokumanTipi = '';
    public string $baslik = '';
    public string $aciklama = '';
    public bool $gizliMi = false;
    public array $erisimIzinleri = [];

    // Filtreleme ve arama
    public string $searchTerm = '';
    public string $filterDokumanTipi = '';
    public string $filterDurum = 'aktif'; // aktif, arsivlenmis, tumu
    public string $sortBy = 'olusturma_tarihi';
    public string $sortDirection = 'desc';

    // Modal durumları
    public bool $showUploadModal = false;
    public bool $showVersionModal = false;
    public bool $showDeleteModal = false;
    public bool $showRestoreModal = false;
    public ?Dokuman $selectedDokuman = null;

    // Diğer
    public bool $showStatistics = false;
    public array $statistics = [];
    public array $uygunDokumanTipleri = [];
    public array $eksikZorunluDokumanlar = [];

    protected $listeners = [
        'refreshDokumanlar' => '$refresh',
        'dokumanSilindi' => 'handleDokumanSilindi',
        'dokumanGeriYuklendi' => 'handleDokumanGeriYuklendi'
    ];

    public function mount(
        string $documentableType,
        string $documentableId,
        ?string $mulkType = null
    ): void {
        $this->documentableType = $documentableType;
        $this->documentableId = $documentableId;
        $this->mulkType = $mulkType;
        
        $this->loadUygunDokumanTipleri();
        $this->loadEksikZorunluDokumanlar();
        $this->loadStatistics();
    }

    public function render()
    {
        $dokumanlar = $this->getDokumanlar();
        
        return view('livewire.dokuman-yonetimi', [
            'dokumanlar' => $dokumanlar,
            'uygunTipler' => $this->uygunDokumanTipleri,
            'eksikZorunluDokumanlar' => $this->eksikZorunluDokumanlar,
            'statistics' => $this->statistics
        ]);
    }

    /**
     * Dökümanları getir ve filtrele
     */
    private function getDokumanlar()
    {
        $service = app(DokumanYonetimService::class);
        
        // Arama varsa
        if (!empty($this->searchTerm)) {
            $dokumanlar = $service->dokumanAra(
                $this->searchTerm,
                $this->documentableType,
                $this->documentableId,
                $this->filterDokumanTipi ? DokumanTipi::from($this->filterDokumanTipi) : null
            );
        } else {
            // Normal filtreleme
            $query = Dokuman::where('documentable_type', $this->documentableType)
                           ->where('documentable_id', $this->documentableId);

            // Durum filtresi
            switch ($this->filterDurum) {
                case 'aktif':
                    $query->where('aktif_mi', true);
                    break;
                case 'arsivlenmis':
                    $query->where('aktif_mi', false);
                    break;
                case 'silinen':
                    $query->onlyTrashed();
                    break;
                // 'tumu' için ek filtre yok
            }

            // Döküman tipi filtresi
            if (!empty($this->filterDokumanTipi)) {
                $query->where('dokuman_tipi', $this->filterDokumanTipi);
            }

            // Mülk tipi filtresi
            if ($this->mulkType) {
                $uygunTipler = DokumanTipi::forMulkType($this->mulkType);
                $tipValues = array_map(fn($tip) => $tip->value, $uygunTipler);
                $query->whereIn('dokuman_tipi', $tipValues);
            }

            // Sıralama
            $query->orderBy($this->sortBy, $this->sortDirection);

            $dokumanlar = $query->with(['olusturan', 'guncelleyen'])->get();
        }

        return $dokumanlar->groupBy('dokuman_tipi');
    }

    /**
     * Uygun döküman tiplerini yükle
     */
    private function loadUygunDokumanTipleri(): void
    {
        if ($this->mulkType) {
            $service = app(DokumanYonetimService::class);
            $this->uygunDokumanTipleri = $service->getMulkTipineGoreDokumanTipleri($this->mulkType);
        } else {
            $this->uygunDokumanTipleri = DokumanTipi::toArray();
        }
    }

    /**
     * Eksik zorunlu dökümanları yükle
     */
    private function loadEksikZorunluDokumanlar(): void
    {
        if ($this->mulkType) {
            $service = app(DokumanYonetimService::class);
            $this->eksikZorunluDokumanlar = $service->getEksikZorunluDokumanlar(
                $this->documentableType,
                $this->documentableId,
                $this->mulkType
            );
        }
    }

    /**
     * İstatistikleri yükle
     */
    private function loadStatistics(): void
    {
        $service = app(DokumanYonetimService::class);
        $this->statistics = $service->getDokumanIstatistikleri(
            $this->documentableType,
            $this->documentableId
        );
    }

    /**
     * Upload modal aç
     */
    public function openUploadModal(): void
    {
        $this->resetUploadForm();
        $this->showUploadModal = true;
    }

    /**
     * Upload modal kapat
     */
    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->resetUploadForm();
    }

    /**
     * Upload form sıfırla
     */
    private function resetUploadForm(): void
    {
        $this->files = [];
        $this->selectedDokumanTipi = '';
        $this->baslik = '';
        $this->aciklama = '';
        $this->gizliMi = false;
        $this->erisimIzinleri = [];
    }

    /**
     * Döküman yükle
     */
    public function uploadDokuman(): void
    {
        $this->validate([
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:51200', // 50MB
            'selectedDokumanTipi' => 'required|string',
            'baslik' => 'nullable|string|max:255',
            'aciklama' => 'nullable|string|max:1000',
            'gizliMi' => 'boolean',
        ]);

        $service = app(DokumanYonetimService::class);
        $dokumanTipi = DokumanTipi::from($this->selectedDokumanTipi);

        $additionalData = [
            'baslik' => $this->baslik,
            'aciklama' => $this->aciklama,
            'gizli_mi' => $this->gizliMi,
            'erisim_izinleri' => $this->erisimIzinleri
        ];

        if (count($this->files) === 1) {
            // Tek dosya yükleme
            $result = $service->dokumanYukle(
                $this->files[0],
                $this->documentableType,
                $this->documentableId,
                $dokumanTipi,
                $additionalData
            );

            if ($result['success']) {
                session()->flash('message', $result['message']);
            } else {
                session()->flash('error', implode(', ', $result['errors']));
            }
        } else {
            // Toplu yükleme
            $result = $service->topluDokumanYukle(
                $this->files,
                $this->documentableType,
                $this->documentableId,
                $dokumanTipi,
                $additionalData
            );

            $summary = $result['summary'];
            session()->flash('message', 
                "{$summary['success']} döküman başarıyla yüklendi. " .
                ($summary['error'] > 0 ? "{$summary['error']} dosyada hata oluştu." : "")
            );
        }

        $this->closeUploadModal();
        $this->loadStatistics();
        $this->loadEksikZorunluDokumanlar();
    }

    /**
     * Versiyon güncelleme modal aç
     */
    public function openVersionModal(Dokuman $dokuman): void
    {
        $this->selectedDokuman = $dokuman;
        $this->selectedDokumanTipi = $dokuman->dokuman_tipi->value;
        $this->baslik = $dokuman->baslik;
        $this->aciklama = $dokuman->aciklama;
        $this->showVersionModal = true;
    }

    /**
     * Versiyon güncelle
     */
    public function updateVersion(): void
    {
        $this->validate([
            'files' => 'required|array|min:1|max:1',
            'files.*' => 'required|file|max:51200',
            'baslik' => 'nullable|string|max:255',
            'aciklama' => 'nullable|string|max:1000',
        ]);

        $service = app(DokumanYonetimService::class);
        
        $result = $service->dokumanVersiyonuGuncelle(
            $this->selectedDokuman,
            $this->files[0],
            [
                'baslik' => $this->baslik,
                'aciklama' => $this->aciklama
            ]
        );

        if ($result['success']) {
            session()->flash('message', $result['message']);
        } else {
            session()->flash('error', implode(', ', $result['errors']));
        }

        $this->showVersionModal = false;
        $this->selectedDokuman = null;
        $this->resetUploadForm();
        $this->loadStatistics();
    }

    /**
     * Silme modal aç
     */
    public function openDeleteModal(Dokuman $dokuman): void
    {
        $this->selectedDokuman = $dokuman;
        $this->showDeleteModal = true;
    }

    /**
     * Döküman sil
     */
    public function deleteDokuman(): void
    {
        $service = app(DokumanYonetimService::class);
        
        $result = $service->dokumanSil($this->selectedDokuman);

        if ($result['success']) {
            session()->flash('message', $result['message']);
        } else {
            session()->flash('error', implode(', ', $result['errors']));
        }

        $this->showDeleteModal = false;
        $this->selectedDokuman = null;
        $this->loadStatistics();
        $this->loadEksikZorunluDokumanlar();
    }

    /**
     * Geri yükleme modal aç
     */
    public function openRestoreModal(Dokuman $dokuman): void
    {
        $this->selectedDokuman = $dokuman;
        $this->showRestoreModal = true;
    }

    /**
     * Döküman geri yükle
     */
    public function restoreDokuman(): void
    {
        $service = app(DokumanYonetimService::class);
        
        $result = $service->dokumanGeriYukle($this->selectedDokuman);

        if ($result['success']) {
            session()->flash('message', $result['message']);
        } else {
            session()->flash('error', implode(', ', $result['errors']));
        }

        $this->showRestoreModal = false;
        $this->selectedDokuman = null;
        $this->loadStatistics();
        $this->loadEksikZorunluDokumanlar();
    }

    /**
     * Döküman indir
     */
    public function downloadDokuman(Dokuman $dokuman)
    {
        // Erişim kontrolü
        if (!$dokuman->hasAccess(auth()->id())) {
            session()->flash('error', 'Bu dökümana erişim yetkiniz yok.');
            return;
        }

        // Erişim sayısını artır
        $dokuman->incrementAccess();

        return response()->download(
            storage_path('app/public/' . $dokuman->url),
            $dokuman->orijinal_dosya_adi
        );
    }

    /**
     * İstatistikleri göster/gizle
     */
    public function toggleStatistics(): void
    {
        $this->showStatistics = !$this->showStatistics;
        if ($this->showStatistics) {
            $this->loadStatistics();
        }
    }

    /**
     * Filtreleri sıfırla
     */
    public function resetFilters(): void
    {
        $this->searchTerm = '';
        $this->filterDokumanTipi = '';
        $this->filterDurum = 'aktif';
        $this->sortBy = 'olusturma_tarihi';
        $this->sortDirection = 'desc';
    }

    /**
     * Sıralama değiştir
     */
    public function sortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Event handlers
     */
    public function handleDokumanSilindi(): void
    {
        $this->loadStatistics();
        $this->loadEksikZorunluDokumanlar();
    }

    public function handleDokumanGeriYuklendi(): void
    {
        $this->loadStatistics();
        $this->loadEksikZorunluDokumanlar();
    }

    /**
     * Döküman tipi seçildiğinde başlığı otomatik doldur
     */
    public function updatedSelectedDokumanTipi(): void
    {
        if (!empty($this->selectedDokumanTipi) && empty($this->baslik)) {
            $tip = DokumanTipi::from($this->selectedDokumanTipi);
            $this->baslik = $tip->label();
        }
    }
}