<?php
namespace App\Livewire\Lokasyon\Ilce;

use Livewire\Attributes\Title;
use Livewire\Component;

class IlceAnasayfa extends Component
{

    #[Title('İlçeler Listesi')]
    public function render()
    {

        return view('livewire.lokasyon.ilce.ilce-anasayfa');
    }
}
