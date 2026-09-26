<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Clients;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Livewire\Concerns\WithToasts;
use App\Livewire\Forms\ClientData;
use App\Models\Client;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ClientForm extends Component
{
    use WithToasts;

    public ClientData $form;

    public ?Client $client = null;

    public function mount(?Client $client = null): void
    {
        if ($client?->exists) {
            $this->authorize('update', $client);
            $this->client = $client;
            $this->form->setClient($client);
        } else {
            $this->authorize('create', Client::class);
            $this->form->owner_id = auth()->id();
        }
    }

    public function save(bool $andContinue = false)
    {
        $client = $this->form->save();

        $client->recordActivity(
            $this->client ? 'client.updated' : 'client.created',
            $this->client ? 'Ficha actualizada' : 'Cliente dado de alta'
        );

        session()->flash('status', $this->client ? 'Cliente actualizado.' : 'Cliente creado.');

        return $andContinue
            ? $this->redirectRoute('admin.clients.edit', $client, navigate: true)
            : $this->redirectRoute('admin.clients.show', $client, navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.clients.client-form', [
            'types' => ClientType::cases(),
            'statuses' => ClientStatus::cases(),
            'owners' => User::active()->orderBy('name')->get(['id', 'name']),
        ])->title($this->client ? 'Editar '.$this->client->name : 'Nuevo cliente');
    }
}