<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Clients;

use App\Enums\DocumentCategory;
use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Ficha 360º del cliente: inmuebles, presupuestos, obras, documentación,
 * mensajes y traza de actividad.
 */
#[Layout('components.layouts.admin')]
class ClientShow extends Component
{
    public Client $client;

    #[Url(as: 'tab')]
    public string $tab = 'resumen';

    public function mount(Client $client): void
    {
        $this->authorize('view', $client);
        $this->client = $client;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render()
    {
        $this->client->loadCount(['properties', 'quotes', 'projects']);

        return view('livewire.admin.clients.client-show', [
            'quotes' => $this->tab === 'presupuestos'
                ? $this->client->quotes()->with('property')->paginate(15)
                : null,
            'projects' => $this->tab === 'obras'
                ? $this->client->projects()->with('property')->paginate(15)
                : null,
            'activities' => $this->tab === 'actividad'
                ? $this->client->activities()->with('causer')->limit(50)->get()
                : null,
            'documentCategories' => [
                DocumentCategory::Contract->value, DocumentCategory::Identity->value,
                DocumentCategory::Invoice->value, DocumentCategory::Plan->value,
                DocumentCategory::License->value, DocumentCategory::Other->value,
            ],
            'totals' => [
                'approved' => (float) $this->client->quotes()->where('status', 'approved')->sum('total'),
                'contracted' => (float) $this->client->projects()->sum('budget_total'),
            ],
        ])->title($this->client->name);
    }
}