<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Livewire\Concerns\WithToasts;
use App\Models\Setting;
use App\Models\Trade;
use App\Services\Branding\LogoService;
use App\Support\UploadEnvironment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Configuración de la empresa: datos fiscales, valores por defecto de
 * presupuestos y catálogo de oficios.
 */
#[Layout('components.layouts.admin')]
#[Title('Configuración')]
class SettingsPage extends Component
{
    use WithFileUploads;
    use WithToasts;

    /** Fichero temporal mientras el usuario sube un logo nuevo. */
    public $logo = null;

    /**
     * Valores del formulario indexados con guion bajo: Livewire interpreta
     * los puntos de wire:model como anidamiento de arrays, así que la clave
     * real («company.name») se guarda como «company_name» en el componente.
     */
    public array $values = [];

    /** clave => [etiqueta, tipo, grupo] */
    public const SCHEMA = [
        'company.name' => ['Nombre comercial', 'string', 'empresa'],
        'company.legal_name' => ['Razón social', 'string', 'empresa'],
        'company.tax_id' => ['CIF', 'string', 'empresa'],
        'company.address' => ['Dirección', 'string', 'empresa'],
        'company.phone' => ['Teléfono', 'string', 'empresa'],
        'company.email' => ['Email de contacto', 'string', 'empresa'],
        'company.tax_rate' => ['IVA por defecto (%)', 'float', 'empresa'],
        'quotes.prefix' => ['Prefijo de presupuestos', 'string', 'presupuestos'],
        'quotes.valid_days' => ['Validez por defecto (días)', 'int', 'presupuestos'],
        'quotes.token_ttl_days' => ['Caducidad del enlace (días)', 'int', 'presupuestos'],
        'quotes.payment_terms' => ['Forma de pago por defecto', 'text', 'presupuestos'],
        'quotes.terms' => ['Condiciones generales', 'text', 'presupuestos'],
        'projects.token_ttl_days' => ['Caducidad del portal de obra (días)', 'int', 'obras'],
        'providers.require_documents' => ['Exigir documentación legal para asignar tareas', 'bool', 'obras'],
    ];

    public array $tradeForm = ['name' => '', 'color' => 'blue'];

    public function mount(): void
    {
        foreach (array_keys(self::SCHEMA) as $key) {
            $this->values[self::formKey($key)] = Setting::get($key, '');
        }
    }

    public static function formKey(string $settingKey): string
    {
        return str_replace('.', '_', $settingKey);
    }

    public function save(): void
    {
        $this->validate([
            'values.company_name' => ['nullable', 'string', 'max:191'],
            'values.company_email' => ['nullable', 'email'],
            'values.company_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'values.quotes_valid_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ], attributes: [
            'values.company_name' => 'nombre comercial',
            'values.company_email' => 'email de contacto',
            'values.company_tax_rate' => 'IVA por defecto',
            'values.quotes_valid_days' => 'validez por defecto',
        ]);

        foreach (self::SCHEMA as $key => [$label, $cast, $group]) {
            $value = $this->values[self::formKey($key)] ?? null;

            Setting::put(
                $key,
                $cast === 'bool' ? (int) (bool) $value : (string) $value,
                $cast === 'text' ? 'string' : $cast,
                $group,
            );
        }

        $this->toastSuccess('Configuración guardada.');
    }

    public function updatedLogo(): void
    {
        // Validar al seleccionar el fichero da el aviso al momento, sin que el
        // usuario tenga que pulsar guardar para descubrir que no vale.
        $this->validateOnly('logo', ['logo' => $this->logoRules()]);
    }

    public function saveLogo(LogoService $logos): void
    {
        $this->validate(['logo' => $this->logoRules()], [
            'logo.required' => 'Elige primero un fichero de imagen.',
            'logo.mimetypes' => 'El logo debe ser PNG, JPG o WEBP.',
            'logo.max' => 'El logo no puede pesar más de 2 MB.',
        ]);

        $logos->store($this->logo);

        $this->reset('logo');
        $this->toastSuccess('Logo actualizado. Ya aparece en el panel, en el portal y en los PDF.');
    }

    public function removeLogo(LogoService $logos): void
    {
        $logos->remove();

        $this->reset('logo');
        $this->toastSuccess('Logo eliminado. Se vuelven a mostrar las iniciales.');
    }

    /** @return array<int, string> */
    private function logoRules(): array
    {
        return [
            'required',
            'image',
            'mimetypes:'.implode(',', LogoService::MIME_TYPES),
            'max:'.LogoService::MAX_KILOBYTES,
        ];
    }

    public function addTrade(): void
    {
        $this->validate([
            'tradeForm.name' => ['required', 'string', 'max:191', 'unique:trades,name'],
        ], attributes: ['tradeForm.name' => 'nombre del oficio']);

        Trade::create([
            'name' => $this->tradeForm['name'],
            'color' => $this->tradeForm['color'],
            'position' => (int) Trade::max('position') + 1,
        ]);

        $this->reset('tradeForm');
        $this->toastSuccess('Oficio añadido.');
    }

    public function toggleTrade(int $id): void
    {
        $trade = Trade::findOrFail($id);
        $trade->update(['is_active' => ! $trade->is_active]);
    }

    public function render()
    {
        return view('livewire.admin.settings.settings-page', [
            'schema' => self::SCHEMA,
            // preserveKeys es obligatorio: sin él groupBy() reindexa a 0,1,2…
            // y la vista pierde la clave real del ajuste («company.legal_name»),
            // con lo que los campos se enlazan a slots inexistentes y repetidos
            // entre grupos, y lo que escribe el usuario se descarta al guardar.
            'groups' => collect(self::SCHEMA)->groupBy(fn (array $meta) => $meta[2], preserveKeys: true),
            'trades' => Trade::orderBy('position')->get(),
            'logoUrl' => app(LogoService::class)->url(),
            // Si el entorno no puede recibir ficheros, mejor decirlo aqui
            // que dejar que la subida falle con un mensaje generico.
            'uploadProblem' => UploadEnvironment::cachedProblem(),
        ]);
    }
}