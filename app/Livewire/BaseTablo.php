<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

abstract class BaseTablo extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'ad';
    public $sortDirection = 'asc';
    public $selected = [];
    public $perPage = 20;
    public $aktifMi = '';
    public $selectedPage = false;
    public $silinenleriGoster = false;
    public $silinenId = null;

    // Alt sınıflar bu metodu implement etmeli
    abstract protected function getModelClass(): string;
    
    // Alt sınıflar bu metodu implement etmeli (opsiyonel)
    protected function getDefaultSortField(): string
    {
        return 'ad';
    }

    public function mount()
    {
        $this->sortField = $this->getDefaultSortField();
    }

    public function hideGeriAlModal()
    {
        $this->silinenId = null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function sortOrderUpdated(array $orderedIds)
    {
        $modelClass = $this->getModelClass();
        foreach ($orderedIds as $index => $id) {
            $modelClass::where('id', $id)->update(['siralama' => $index + 1]);
        }
    }

    public function deleteSelected()
    {
        $modelClass = $this->getModelClass();
        $modelClass::whereIn('id', $this->selected)->delete();
        $this->selected = [];
    }

    public function updatedSelectedPage($value)
    {
        $pageIds = $this->getCurrentPageItems()->pluck('id')->toArray();
        $this->selected = $value ? $pageIds : [];
    }

    public function updatedSelected()
    {
        $pageIds = $this->getCurrentPageItems()->pluck('id')->toArray();
        $this->selectedPage = empty(array_diff($pageIds, $this->selected));
    }

    protected function getCurrentPageItems()
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->aktifMi !== '', fn($q) => $q->where('aktif_mi', $this->aktifMi))
            ->applySort($this->sortField, $this->sortDirection)
            ->ordered();

        if ($this->silinenleriGoster) {
            $query->onlyTrashed();
        }

        return $query->paginate($this->perPage);
    }

    public function toggleAktifMi($id)
    {
        $modelClass = $this->getModelClass();
        $data = $modelClass::find($id);
        if ($data) {
            $data->aktif_mi = !$data->aktif_mi;
            $data->save();
        }
    }

    public function deleteItem($id)
    {
        $modelClass = $this->getModelClass();
        $data = $modelClass::find($id);
        if ($data) {
            $data->delete();
            $this->dispatch($this->getDeletedEventName());
        }
    }

  

    // Genel metod isimleri - view'larda bu isimleri kullanacağız
    public function delete($id)
    {
        $this->deleteItem($id);
    }

    public function restore($id = null)
    {
        $this->geriAlItem($id);
    }

    public function showSilinenler()
    {
        $this->silinenleriGoster = true;
    }

    public function hideSilinenler()
    {
        $this->silinenleriGoster = false;
    }

    public function showGeriAlModal($id)
    {
        $this->silinenId = $id;
    }

    public function geriAlItem($id = null)
    {
        $geriAlId = $id ?? $this->silinenId;
        if ($geriAlId) {
            $modelClass = $this->getModelClass();
            $modelClass::geriAl($geriAlId);
            $this->silinenId = null;
            $this->silinenleriGoster = true;
            $this->dispatch($this->getRestoredEventName());
        }
    }

    public function geriAl($id = null)
    {
        $this->geriAlItem($id);
    }

    // Alt sınıflar bu metodları override edebilir
    protected function getDeletedEventName(): string
    {
        return strtolower(class_basename($this->getModelClass())) . 'Silindi';
    }

    protected function getRestoredEventName(): string
    {
        return strtolower(class_basename($this->getModelClass())) . 'GeriAlindi';
    }

    protected function getAddedEventName(): string
    {
        return strtolower(class_basename($this->getModelClass())) . 'Eklendi';
    }

    protected function getUpdatedEventName(): string
    {
        return strtolower(class_basename($this->getModelClass())) . 'Guncellendi';
    }

    // Alt sınıflar bu metodları implement etmeli
    abstract protected function getViewName(): string;
    abstract protected function getDataVariableName(): string;

    // Alt sınıflar eager loading için bu metodu override edebilir
    protected function getEagerLoadRelations(): array
    {
        return [];
    }

    protected function getQuery()
    {
        $modelClass = $this->getModelClass();
        $eagerLoad = $this->getEagerLoadRelations();
        
        if ($this->silinenleriGoster) {
            $query = $modelClass::sadeceSilinen()
                ->when($this->search, fn($q) => $q->search($this->search))
                ->ordered();
        } else {
            $query = $modelClass::query()
                ->when($this->search, fn($q) => $q->search($this->search))
                ->when($this->aktifMi !== '', fn($q) => $q->where('aktif_mi', $this->aktifMi))
                ->applySort($this->sortField, $this->sortDirection)
                ->ordered();
        }
        
        // Eager loading varsa ekle
        if (!empty($eagerLoad)) {
            $query->with($eagerLoad);
        }
        
        return $query;
    }

    protected function getAdditionalViewData(): array
    {
        return [];
    }

    public function render()
    {
        $modelClass = $this->getModelClass();
        $query = $this->getQuery();
        $data = $query->paginate($this->perPage);
        $silinenSayisi = $modelClass::onlyTrashed()->count();
        
        $viewData = [
            $this->getDataVariableName() => $data,
            'silinenSayisi' => $silinenSayisi,
        ];
        
        // Alt sınıflardan ek veri alabilir
        $additionalData = $this->getAdditionalViewData();
        $viewData = array_merge($viewData, $additionalData);
        
        return view($this->getViewName(), $viewData);
    }
}