<?php

use App\Http\Livewire\Kategori\KategoriAnasayfa;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Appearance;
use Illuminate\Support\Facades\Route;
use App\Livewire\Sirket\Sube\SubeAnasayfa;
use App\Livewire\Lokasyon\Ilce\IlceAnasayfa;
use App\Livewire\Lokasyon\Semt\SemtAnasayfa;
use App\Livewire\Lokasyon\Sehir\SehirAnasayfa;
use App\Livewire\Lokasyon\Mahalle\MahalleAnasayfa;
use App\Livewire\Sirket\Personel\PersonelAnasayfa;
use App\Livewire\Sirket\Pozisyon\PozisyonAnasayfa;
use App\Livewire\Sirket\Departman\DepartmanAnasayfa;
use App\Livewire\Sirket\PersonelRole\PersonelRoleAnasayfa;
use App\Livewire\Test\TestAnasayfa;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    Route::get('sehirler', SehirAnasayfa::class)->name('sehirler.anasayfa');
    Route::get('ilceler', IlceAnasayfa::class)->name('ilceler.anasayfa');
    Route::get('semtler', SemtAnasayfa::class)->name('semtler.anasayfa');
    Route::get('mahalleler', MahalleAnasayfa::class)->name('mahalleler.anasayfa');

    Route::get('subeler', SubeAnasayfa::class)->name('subeler.anasayfa');
    Route::get('departmanlar', DepartmanAnasayfa::class)->name('departmanlar.anasayfa');
    Route::get('pozisyonlar', PozisyonAnasayfa::class)->name('pozisyonlar.anasayfa');
    Route::get('personel-rolleri',PersonelRoleAnasayfa::class)->name('personel-rolleri.anasayfa');
    Route::get('personeller', PersonelAnasayfa::class)->name('personeller.anasayfa');

  

});



require __DIR__.'/auth.php';
