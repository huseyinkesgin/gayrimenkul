<?php

namespace App\Livewire\Sirket\Personel;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Kisi\Personel;

class PersonelDetayModal extends Component
{
    public ?string $personelId = null;
    public ?Personel $personel = null;
    public bool $loading = false;

    #[On('loadPersonelDetay')]
    public function handleLoadPersonelDetay($personelId)
    {
        $this->loading = true;
        $this->personelId = $personelId;
        $this->loadPersonel();
        $this->loading = false;
        $this->dispatch('open-modal', name: 'personel-detay-modal');
    }

    #[On('personelAdresGuncellendi')]
    #[On('personelAdresSilindi')]
    #[On('personelAdresEklendi')]
    public function refreshPersonelData()
    {
        if ($this->personelId) {
            $this->loadPersonel();
        }
    }

    public function deleteAdres($adresId)
    {
        try {
            $adres = \App\Models\Adres::find($adresId);
            if ($adres && $adres->addressable_id === $this->personel->kisi->id && $adres->addressable_type === 'App\Models\Kisi\Kisi') {
                $adres->delete();
                $this->loadPersonel();
                
                // Event dispatch et ki diğer modallar da güncellensin
                $this->dispatch('personelAdresSilindi');
                
                session()->flash('message', 'Adres başarıyla silindi.');
            } else {
                session()->flash('error', 'Adres bulunamadı veya bu personele ait değil.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Adres silinirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function loadPersonel()
    {
        if ($this->personelId) {
            $this->personel = Personel::with([
                'kisi.adresler.sehir',
                'kisi.adresler.ilce', 
                'kisi.adresler.semt',
                'kisi.adresler.mahalle',
                'sube',
                'departman.yonetici.kisi',
                'pozisyon',
                'roller',
                'avatar'
            ])->find($this->personelId);
        }
    }

    public function resetModal()
    {
        $this->personelId = null;
        $this->personel = null;
        $this->loading = false;
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'personel-detay-modal');
        $this->resetModal();
    }

    public function render()
    {
        return view('livewire.sirket.personel.personel-detay-modal');
    }
}
