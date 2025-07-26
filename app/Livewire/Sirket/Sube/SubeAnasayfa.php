<?php

namespace App\Livewire\Sirket\Sube;

use Livewire\Component;
use Livewire\Attributes\Title;

class SubeAnasayfa extends Component
{
    #[Title('Şube Listesi')]
    public function render()
    {
        return view('livewire.sirket.sube.sube-anasayfa');
    }
}
