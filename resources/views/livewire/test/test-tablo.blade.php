<div class="overflow-x-auto">
    <div class="mb-4">
        <input type="text" wire:model.debounce.300ms="search" placeholder="Ara..." class="w-full p-2 border border-gray-300 rounded">
    </div>
    <table class="min-w-full bg-white border border-gray-300">
        <thead>
            <tr>
                <th wire:click="sortBy('id')" class="px-4 py-2 text-left border-b cursor-pointer">ID <span class="text-xs">@if($sortField === 'id') {{ $sortDirection === 'asc' ? '▲' : '▼' }} @endif</span></th>
                <th wire:click="sortBy('name')" class="px-4 py-2 text-left border-b cursor-pointer">Name <span class="text-xs">@if($sortField === 'name') {{ $sortDirection === 'asc' ? '▲' : '▼' }} @endif</span></th>
                <th wire:click="sortBy('notes')" class="px-4 py-2 text-left border-b cursor-pointer">Notes <span class="text-xs">@if($sortField === 'notes') {{ $sortDirection === 'asc' ? '▲' : '▼' }} @endif</span></th>
                <th class="px-4 py-2 text-left border-b">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr class="hover:bg-gray-100">
                    <td class="px-4 py-2 border-b">{{ $item->id }}</td>
                    <td class="px-4 py-2 border-b">{{ $item->name }}</td>
                    <td class="px-4 py-2 border-b">{{ $item->notes }}</td>
                    <td class="px-4 py-2 border-b">
                        <button wire:click="edit('{{ $item->id }}')" class="text-blue-500 hover:underline">Düzenle</button>
                        <button wire:click="delete('{{ $item->id }}')" onclick="return confirm('Bu kaydı silmek istediğinizden emin misiniz?')" class="text-red-500 hover:underline ml-2">Sil</button>
                    </td>
                </tr>
            @empty
                 <tr><td colspan="4" class="text-center p-4">Kayıt bulunamadı.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $items->links() }}</div>
</div>
