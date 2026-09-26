<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Providers;

use App\Enums\ProviderStatus;
use App\Livewire\Concerns\WithSorting;
use App\Livewire\Concerns\WithToasts;
use App\Models\Provider;
use App\Models\Trade;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Proveedores')]
class ProviderIndex extends Component
{
    use WithPagination;
    use WithSorting;
    use WithToasts;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'oficio', except: '')]
    public string $trade = '';

    #[Url(as: 'estado', except: '')]
    public string $status = '';

    /** Solo proveedores con toda la documentación legal en vigor. */
    #[Url(as: 'docs', except: false)]
    public bool $onlyCompliant = false;

    protected function sortableColumns(): array
    {
        return ['name', 'code', 'rating', 'city', 'created_at'];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'trade', 'status', 'onlyCompliant'], true)) {
            $this->resetPage();
        }
    }

    public function delete(int $id): void
    {
        $provider = Provider::findOrFail($id);
        $this->authorize('delete', $provider);

        if ($provider->tasks()->exists()) {
            $this->toastError('El proveedor tiene tareas asignadas; desactívalo en lugar de borrarlo.');

            return;
        }

        $provider->delete();
        $this->toastSuccess('Proveedor eliminado.');
    }

    public function render()
    {
        $providers = $this->applySorting(
            Provider::query()
                ->with(['trades', 'documents'])
                ->withCount('tasks')
                ->search($this->search)
                ->withTrade($this->trade ?: null)
                ->when($this->status, fn ($q) => $q->where('status', $this->status)),
            'name'
        )->paginate(20);

        if ($this->onlyCompliant) {
            $providers->setCollection($providers->getCollection()->filter->hasValidDocumentation());
        }

        return view('livewire.admin.providers.provider-index', [
            'providers' => $providers,
            'trades' => Trade::active()->get(),
            'statuses' => ProviderStatus::cases(),
        ]);
    }
}