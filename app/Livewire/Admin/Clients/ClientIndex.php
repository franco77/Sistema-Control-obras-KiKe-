<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Clients;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Livewire\Concerns\WithSorting;
use App\Livewire\Concerns\WithToasts;
use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Clientes')]
class ClientIndex extends Component
{
    use WithPagination;
    use WithSorting;
    use WithToasts;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'estado', except: '')]
    public string $status = '';

    #[Url(as: 'tipo', except: '')]
    public string $type = '';

    public int $perPage = 20;

    protected function sortableColumns(): array
    {
        return ['name', 'code', 'city', 'status', 'created_at'];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'type'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'type');
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $client = Client::findOrFail($id);
        $this->authorize('delete', $client);

        if ($client->projects()->exists()) {
            $this->toastError('No puedes eliminar un cliente con obras asociadas.');

            return;
        }

        $client->delete();
        $this->toastSuccess('Cliente eliminado.');
    }

    public function render()
    {
        $clients = $this->applySorting(
            Client::query()
                ->with('owner')
                ->withCount(['properties', 'projects', 'quotes'])
                ->search($this->search)
                ->status($this->status ?: null)
                ->when($this->type, fn ($q) => $q->where('type', $this->type)),
            'created_at'
        )->paginate($this->perPage);

        return view('livewire.admin.clients.client-index', [
            'clients' => $clients,
            'statuses' => ClientStatus::cases(),
            'types' => ClientType::cases(),
        ]);
    }
}