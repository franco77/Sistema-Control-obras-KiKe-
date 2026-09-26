<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Providers;

use App\Enums\AvailabilityType;
use App\Enums\DocumentCategory;
use App\Livewire\Concerns\WithToasts;
use App\Models\Provider;
use App\Services\Portal\PortalTokenService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Ficha del proveedor: oficios, documentación legal, disponibilidad,
 * histórico de trabajos y valoraciones.
 */
#[Layout('components.layouts.admin')]
class ProviderShow extends Component
{
    use WithToasts;

    public Provider $provider;

    #[Url(as: 'tab')]
    public string $tab = 'resumen';

    /** Alta rápida de bloques de disponibilidad. */
    public array $availability = [
        'type' => 'unavailable', 'starts_on' => '', 'ends_on' => '', 'note' => '',
    ];

    public ?string $portalLink = null;

    public function mount(Provider $provider): void
    {
        $this->authorize('view', $provider);
        $this->provider = $provider;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function addAvailability(): void
    {
        $this->authorize('update', $this->provider);

        $data = $this->validate([
            'availability.type' => ['required', Rule::enum(AvailabilityType::class)],
            'availability.starts_on' => ['required', 'date'],
            'availability.ends_on' => ['required', 'date', 'after_or_equal:availability.starts_on'],
            'availability.note' => ['nullable', 'string', 'max:191'],
        ])['availability'];

        $this->provider->availabilities()->create($data);
        $this->reset('availability');
        $this->toastSuccess('Disponibilidad registrada.');
    }

    public function removeAvailability(int $id): void
    {
        $this->provider->availabilities()->whereKey($id)->delete();
    }

    /** Genera un enlace de portal para que el proveedor vea sus tareas. */
    public function generatePortalLink(PortalTokenService $tokens): void
    {
        $this->authorize('update', $this->provider);

        $plain = $tokens->issueOrReuse(
            $this->provider,
            abilities: ['provider.tasks', 'provider.photos', 'provider.availability'],
            audience: 'provider',
            ttlDays: 180,
        );

        $this->portalLink = $tokens->urlFor($plain);
        $this->toastSuccess('Enlace generado. Cópialo ahora: no volverá a mostrarse.');
    }

    public function render()
    {
        return view('livewire.admin.providers.provider-show', [
            'tasks' => $this->tab === 'trabajos'
                ? $this->provider->tasks()->with('project', 'phase')->latest()->paginate(20)
                : null,
            'availabilities' => $this->provider->availabilities()
                ->where('ends_on', '>=', now()->subMonths(2))
                ->orderBy('starts_on')->get(),
            'reviews' => $this->provider->reviews()->with('project', 'user')->latest()->get(),
            'documentCategories' => [
                DocumentCategory::Insurance->value, DocumentCategory::Prl->value,
                DocumentCategory::SocialSecurity->value, DocumentCategory::TaxCertificate->value,
                DocumentCategory::Identity->value, DocumentCategory::Contract->value,
                DocumentCategory::Other->value,
            ],
            'availabilityTypes' => AvailabilityType::cases(),
        ])->title($this->provider->name);
    }
}