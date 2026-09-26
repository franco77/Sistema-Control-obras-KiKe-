<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Quotes;

use App\Enums\QuoteStatus;
use App\Livewire\Concerns\WithSorting;
use App\Livewire\Concerns\WithToasts;
use App\Models\Quote;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Presupuestos')]
class QuoteIndex extends Component
{
    use WithPagination;
    use WithSorting;
    use WithToasts;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'estado', except: '')]
    public string $status = '';

    /** Oculta las versiones antiguas para no duplicar filas. */
    #[Url(as: 'versiones', except: false)]
    public bool $showAllVersions = false;

    protected function sortableColumns(): array
    {
        return ['number', 'issue_date', 'valid_until', 'total', 'status'];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'showAllVersions'], true)) {
            $this->resetPage();
        }
    }

    public function delete(int $id): void
    {
        $quote = Quote::findOrFail($id);
        $this->authorize('delete', $quote);

        $quote->delete();
        $this->toastSuccess('Presupuesto eliminado.');
    }

    public function render()
    {
        $quotes = $this->applySorting(
            Quote::query()
                ->with('client', 'property', 'project')
                ->search($this->search)
                ->status($this->status ?: null)
                ->when(! $this->showAllVersions, fn ($q) => $q->where('status', '!=', QuoteStatus::Superseded)),
            'issue_date'
        )->paginate(20);

        return view('livewire.admin.quotes.quote-index', [
            'quotes' => $quotes,
            'statuses' => QuoteStatus::cases(),
            'summary' => [
                'pending' => (float) Quote::pending()->sum('total'),
                'approved_month' => (float) Quote::where('status', QuoteStatus::Approved)
                    ->where('decided_at', '>=', now()->startOfMonth())->sum('total'),
                'conversion' => $this->conversionRate(),
            ],
        ]);
    }

    /** % de presupuestos decididos que acabaron aprobados (últimos 6 meses). */
    private function conversionRate(): float
    {
        $since = now()->subMonths(6);

        $decided = Quote::whereIn('status', [QuoteStatus::Approved, QuoteStatus::Rejected])
            ->where('decided_at', '>=', $since)->count();

        if ($decided === 0) {
            return 0.0;
        }

        $approved = Quote::where('status', QuoteStatus::Approved)
            ->where('decided_at', '>=', $since)->count();

        return round(($approved / $decided) * 100, 1);
    }
}