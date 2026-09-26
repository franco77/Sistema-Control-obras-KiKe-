<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Objeto de formulario del cliente: mantiene el componente de página
 * delgado y permite reutilizar las mismas reglas desde cualquier pantalla.
 */
class ClientData extends Form
{
    public ?Client $client = null;

    public string $type = 'individual';
    public string $status = 'lead';
    public string $name = '';
    public string $legal_name = '';
    public string $tax_id = '';
    public string $email = '';
    public string $phone = '';
    public string $phone_alt = '';
    public string $address = '';
    public string $address_extra = '';
    public string $postal_code = '';
    public string $city = '';
    public string $province = '';
    public string $source = '';
    public string $preferred_channel = 'email';
    public bool $accepts_marketing = false;
    public string $notes = '';
    public ?int $owner_id = null;

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ClientType::class)],
            'status' => ['required', Rule::enum(ClientStatus::class)],
            'name' => ['required', 'string', 'max:191'],
            'legal_name' => ['nullable', 'string', 'max:191'],
            'tax_id' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_alt' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:191'],
            'address_extra' => ['nullable', 'string', 'max:191'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:191'],
            'province' => ['nullable', 'string', 'max:191'],
            'source' => ['nullable', 'string', 'max:60'],
            'preferred_channel' => ['required', 'in:email,phone,whatsapp'],
            'accepts_marketing' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nombre', 'legal_name' => 'razón social', 'tax_id' => 'NIF/CIF',
            'email' => 'email', 'phone' => 'teléfono', 'address' => 'dirección',
            'postal_code' => 'código postal', 'city' => 'ciudad', 'province' => 'provincia',
        ];
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;

        // Las propiedades del formulario son string; los enums y los null de
        // la base de datos hay que aplanarlos antes de asignarlos.
        $this->fill([
            'type' => $client->type->value,
            'status' => $client->status->value,
            'name' => (string) $client->name,
            'legal_name' => (string) $client->legal_name,
            'tax_id' => (string) $client->tax_id,
            'email' => (string) $client->email,
            'phone' => (string) $client->phone,
            'phone_alt' => (string) $client->phone_alt,
            'address' => (string) $client->address,
            'address_extra' => (string) $client->address_extra,
            'postal_code' => (string) $client->postal_code,
            'city' => (string) $client->city,
            'province' => (string) $client->province,
            'source' => (string) $client->source,
            'preferred_channel' => (string) $client->preferred_channel,
            'accepts_marketing' => (bool) $client->accepts_marketing,
            'notes' => (string) $client->notes,
            'owner_id' => $client->owner_id,
        ]);
    }

    public function save(): Client
    {
        $data = $this->validate();

        if ($this->client) {
            $this->client->update($data);

            return $this->client;
        }

        return Client::create($data + ['created_by' => auth()->id()]);
    }
}