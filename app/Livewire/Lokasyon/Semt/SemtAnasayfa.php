<?php

namespace App\Livewire\Lokasyon\Semt;

use Livewire\Attributes\Title;
use Livewire\Component;

class SemtAnasayfa extends Component
{

    #[Title('Semtler Listesi')]
    public function render()
    {

        return view('livewire.lokasyon.semt.semt-anasayfa');
    }
}
