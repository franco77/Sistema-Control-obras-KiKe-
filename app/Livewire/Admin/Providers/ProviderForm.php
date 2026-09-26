<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Providers;

use App\Enums\ProviderStatus;
use App\Enums\ProviderType;
use App\Livewire\Concerns\WithToasts;
use App\Models\Provider;
use App\Models\Trade;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ProviderForm extends Component
{
    use WithToasts;

    public ?Provider $provider = null;

    public array $form = [
        'type' => 'freelancer', 'status' => 'pending_docs', 'name' => '', 'legal_name' => '',
        'tax_id' => '', 'email' => '', 'phone' => '', 'address' => '', 'postal_code' => '',
        'city' => '', 'province' => '', 'iban' => '', 'default_hourly_rate' => null,
        'irpf_rate' => null, 'max_parallel_projects' => 3, 'radius_km' => null, 'notes' => '',
    ];

    /** trade_id => ['selected' => bool, 'is_primary' => bool, 'hourly_rate' => ?float, 'experience_years' => ?int] */
    public array $trades = [];

    public function mount(?Provider $provider = null): void
    {
        if ($provider?->exists) {
            $this->authorize('update', $provider);
            $this->provider = $provider;
            $this->form = array_merge($this->form, $provider->only(array_keys($this->form)));
            $this->form['type'] = $provider->type->value;
            $this->form['status'] = $provider->status->value;
        } else {
            $this->authorize('create', Provider::class);
        }

        foreach (Trade::active()->get() as $trade) {
            $pivot = $this->provider?->trades->firstWhere('id', $trade->id)?->pivot;

            $this->trades[$trade->id] = [
                'selected' => $pivot !== null,
                'is_primary' => (bool) ($pivot->is_primary ?? false),
                'hourly_rate' => $pivot->hourly_rate ?? null,
                'experience_years' => $pivot->experience_years ?? null,
            ];
        }
    }

    protected function rules(): array
    {
        return [
            'form.type' => ['required', Rule::enum(ProviderType::class)],
            'form.status' => ['required', Rule::enum(ProviderStatus::class)],
            'form.name' => ['required', 'string', 'max:191'],
            'form.legal_name' => ['nullable', 'string', 'max:191'],
            'form.tax_id' => ['nullable', 'string', 'max:30'],
            'form.email' => ['nullable', 'email', 'max:191'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.address' => ['nullable', 'string', 'max:191'],
            'form.postal_code' => ['nullable', 'string', 'max:10'],
            'form.city' => ['nullable', 'string', 'max:191'],
            'form.province' => ['nullable', 'string', 'max:191'],
            'form.iban' => ['nullable', 'string', 'max:34'],
            'form.default_hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'form.irpf_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'form.max_parallel_projects' => ['required', 'integer', 'min:1', 'max:50'],
            'form.radius_km' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function save()
    {
        $data = $this->validate()['form'];

        $provider = $this->provider
            ? tap($this->provider)->update($data)
            : Provider::create($data + ['created_by' => auth()->id()]);

        // Sincroniza oficios con sus tarifas específicas.
        $sync = collect($this->trades)
            ->filter(fn (array $row) => $row['selected'])
            ->mapWithKeys(fn (array $row, int $tradeId) => [$tradeId => [
                'is_primary' => (bool) $row['is_primary'],
                'hourly_rate' => $row['hourly_rate'] !== '' ? $row['hourly_rate'] : null,
                'experience_years' => $row['experience_years'] !== '' ? $row['experience_years'] : null,
            ]])
            ->all();

        $provider->trades()->sync($sync);

        $provider->recordActivity(
            $this->provider ? 'provider.updated' : 'provider.created',
            $this->provider ? 'Ficha actualizada' : 'Proveedor dado de alta'
        );

        session()->flash('status', 'Proveedor guardado correctamente.');

        return $this->redirectRoute('admin.providers.show', $provider, navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.providers.provider-form', [
            'types' => ProviderType::cases(),
            'statuses' => ProviderStatus::cases(),
            'allTrades' => Trade::active()->get(),
        ])->title($this->provider ? 'Editar '.$this->provider->name : 'Nuevo proveedor');
    }
}