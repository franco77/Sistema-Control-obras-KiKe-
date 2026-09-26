<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Clients;

use App\Enums\PropertyType;
use App\Livewire\Concerns\WithToasts;
use App\Models\Client;
use App\Models\Property;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Inmuebles del cliente: alta, edición y borrado en línea. */
class PropertyManager extends Component
{
    use WithToasts;

    public Client $client;

    public bool $showForm = false;
    public ?int $editingId = null;

    public array $form = [
        'alias' => '', 'type' => 'apartment', 'address' => '', 'block' => '',
        'floor' => '', 'door' => '', 'postal_code' => '', 'city' => '', 'province' => '',
        'cadastral_reference' => '', 'built_area' => null, 'usable_area' => null,
        'rooms' => null, 'bathrooms' => null, 'year_built' => null,
        'has_elevator' => false, 'is_occupied' => true, 'access_notes' => '', 'notes' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.alias' => ['required', 'string', 'max:191'],
            'form.type' => ['required', Rule::enum(PropertyType::class)],
            'form.address' => ['required', 'string', 'max:191'],
            'form.block' => ['nullable', 'string', 'max:20'],
            'form.floor' => ['nullable', 'string', 'max:20'],
            'form.door' => ['nullable', 'string', 'max:20'],
            'form.postal_code' => ['nullable', 'string', 'max:10'],
            'form.city' => ['nullable', 'string', 'max:191'],
            'form.province' => ['nullable', 'string', 'max:191'],
            'form.cadastral_reference' => ['nullable', 'string', 'max:30'],
            'form.built_area' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'form.usable_area' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'form.rooms' => ['nullable', 'integer', 'min:0', 'max:255'],
            'form.bathrooms' => ['nullable', 'integer', 'min:0', 'max:255'],
            'form.year_built' => ['nullable', 'integer', 'min:1800', 'max:'.(now()->year + 5)],
            'form.access_notes' => ['nullable', 'string', 'max:2000'],
            'form.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function create(): void
    {
        $this->reset('form', 'editingId');
        $this->form['city'] = $this->client->city ?? '';
        $this->form['province'] = $this->client->province ?? '';
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $property = $this->client->properties()->findOrFail($id);
        $this->editingId = $id;
        $this->form = array_merge($this->form, $property->only(array_keys($this->form)));
        $this->form['type'] = $property->type->value;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->client);
        $data = $this->validate()['form'];

        if ($this->editingId) {
            $this->client->properties()->findOrFail($this->editingId)->update($data);
            $this->toastSuccess('Inmueble actualizado.');
        } else {
            $this->client->properties()->create($data);
            $this->toastSuccess('Inmueble añadido.');
        }

        $this->reset('form', 'editingId', 'showForm');
    }

    public function delete(int $id): void
    {
        $property = $this->client->properties()->findOrFail($id);

        if ($property->projects()->exists()) {
            $this->toastError('El inmueble tiene obras asociadas y no puede eliminarse.');

            return;
        }

        $property->delete();
        $this->toastSuccess('Inmueble eliminado.');
    }

    public function render()
    {
        return view('livewire.admin.clients.property-manager', [
            'properties' => $this->client->properties()->withCount('projects')->get(),
            'types' => PropertyType::cases(),
        ]);
    }
}