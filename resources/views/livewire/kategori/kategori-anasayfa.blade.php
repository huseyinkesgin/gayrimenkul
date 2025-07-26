<div>
    <h1 class="text-2xl font-bold mb-4">Kategori Yönetimi</h1>
    <button wire:click="$set('showEklemeModal', true)" class="mb-4 px-4 py-2 bg-blue-500 text-white rounded">Yeni Kategori Ekle</button>

    <livewire:kategori.kategori-tablo />

    @if($showEklemeModal)
        <livewire:kategori.kategori-ekleme-modal />
    @endif

    @if($showDuzenlemeModal)
        <livewire:kategori.kategori-duzenleme-modal itemId="{$selectedItemId}" />
    @endif
</div>
