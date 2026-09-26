<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Documents;

use App\Enums\DocumentCategory;
use App\Models\Document;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Vista transversal de toda la documentación, pensada para el control de
 * caducidades (seguros, PRL, licencias) sin entrar entidad por entidad.
 */
#[Layout('components.layouts.admin')]
#[Title('Documentos')]
class DocumentIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'tipo', except: '')]
    public string $category = '';

    #[Url(as: 'estado', except: 'all')]
    public string $expiry = 'all'; // all | expired | soon

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $documents = Document::query()
            ->with('documentable', 'uploader')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('original_name', 'like', '%'.$this->search.'%')))
            ->category($this->category ?: null)
            ->when($this->expiry === 'expired', fn ($q) => $q->expired())
            ->when($this->expiry === 'soon', fn ($q) => $q->expiring(30)->whereDate('expires_on', '>=', now()))
            ->orderByRaw('expires_on is null, expires_on asc')
            ->paginate(25);

        return view('livewire.admin.documents.document-index', [
            'documents' => $documents,
            'categories' => DocumentCategory::cases(),
            'counters' => [
                'expired' => Document::expired()->count(),
                'soon' => Document::expiring(30)->whereDate('expires_on', '>=', now())->count(),
                'total' => Document::count(),
            ],
        ]);
    }
}