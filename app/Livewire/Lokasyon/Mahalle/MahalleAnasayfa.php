<?php

namespace App\Livewire\Lokasyon\Mahalle;

use Livewire\Component;
use Livewire\Attributes\Title;

class MahalleAnasayfa extends Component
{
    #[Title('Mahalleler Listesi')]
    public function render()
    {
        return view('livewire.lokasyon.mahalle.mahalle-anasayfa');
    }
}
