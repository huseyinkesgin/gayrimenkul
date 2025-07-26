<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold mb-4">Test Düzenle</h2>
        <form wire:submit.prevent="save">
            <div class="mb-4">
                <label for="edit-name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" wire:model.defer="name" id="edit-name" class="mt-1 block w-full border border-gray-300 rounded-md p-2" />
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="edit-notes" class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea wire:model.defer="notes" id="edit-notes" class="mt-1 block w-full border border-gray-300 rounded-md p-2"></textarea>
                @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <div class="mt-4 flex justify-end space-x-2">
                <button type="button" wire:click="$dispatch('closeModals')" class="px-4 py-2 bg-gray-200 rounded">İptal</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Kaydet</button>
            </div>
        </form>
    </div>
</div>
