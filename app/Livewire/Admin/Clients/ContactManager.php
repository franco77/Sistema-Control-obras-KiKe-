<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Clients;

use App\Livewire\Concerns\WithToasts;
use App\Models\Client;
use Livewire\Component;

/** Personas de contacto del cliente (útil en comunidades y empresas). */
class ContactManager extends Component
{
    use WithToasts;

    public Client $client;

    public ?int $editingId = null;
    public bool $showForm = false;

    public array $form = [
        'name' => '', 'role' => '', 'email' => '', 'phone' => '',
        'is_primary' => false, 'receives_notifications' => true, 'notes' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:191'],
            'form.role' => ['nullable', 'string', 'max:80'],
            'form.email' => ['nullable', 'email', 'max:191'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function create(): void
    {
        $this->reset('form', 'editingId');
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $contact = $this->client->contacts()->findOrFail($id);
        $this->editingId = $id;
        $this->form = $contact->only(array_keys($this->form));
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->client);
        $data = $this->validate()['form'];

        $this->editingId
            ? $this->client->contacts()->findOrFail($this->editingId)->update($data)
            : $this->client->contacts()->create($data);

        $this->reset('form', 'editingId', 'showForm');
        $this->toastSuccess('Contacto guardado.');
    }

    public function delete(int $id): void
    {
        $this->client->contacts()->findOrFail($id)->delete();
        $this->toastSuccess('Contacto eliminado.');
    }

    public function render()
    {
        return view('livewire.admin.clients.contact-manager', [
            'contacts' => $this->client->contacts()->get(),
        ]);
    }
}