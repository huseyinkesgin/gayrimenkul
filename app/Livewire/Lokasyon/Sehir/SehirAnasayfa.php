<?php
namespace App\Livewire\Lokasyon\Sehir;

use Livewire\Attributes\Title;
use Livewire\Component;

class SehirAnasayfa extends Component
{

    #[Title('Şehirler Listesi')]
    public function render()
    {

        return view('livewire.lokasyon.sehir.sehir-anasayfa');
    }
}
