<div>
    <h1 class="text-2xl font-bold mb-4">Test Yönetimi</h1>
    <button wire:click="$set('showEklemeModal', true)" class="mb-4 px-4 py-2 bg-blue-500 text-white rounded">Yeni Test Ekle</button>

    <livewire:test.test-tablo />

    @if($showEklemeModal)
        <livewire:test.test-ekleme-modal />
    @endif

    @if($showDuzenlemeModal)
        <livewire:test.test-duzenleme-modal itemId="{$selectedItemId}" />
    @endif
</div>
