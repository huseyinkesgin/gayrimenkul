import Sortable from 'sortablejs';

document.addEventListener('livewire:init', () => {
    const el = document.querySelector('#sehir-tbody');
    if (!el) return;

    Sortable.create(el, {
        animation: 150,
        ghostClass: 'bg-blue-100',
        onEnd: function (evt) {
            // Sıralı id’leri al
            const orderedIds = Array.from(el.children).map(tr => tr.dataset.sehirId);
            // Livewire component’ine gönder
            this.call('sortOrderUpdated', orderedIds);
        }
    });
});
