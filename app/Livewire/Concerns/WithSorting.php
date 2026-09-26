<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Ordenación de listados con estado en la URL. El componente declara las
 * columnas permitidas en sortableColumns() para evitar inyección de campos.
 */
trait WithSorting
{
    #[Url(as: 'orden', except: '')]
    public string $sortField = '';

    #[Url(as: 'dir', except: 'desc')]
    public string $sortDirection = 'desc';

    /** @return array<int, string> */
    abstract protected function sortableColumns(): array;

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->sortableColumns(), true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    protected function applySorting(Builder $query, string $default = 'created_at'): Builder
    {
        $field = in_array($this->sortField, $this->sortableColumns(), true) ? $this->sortField : $default;

        return $query->orderBy($field, $this->sortDirection === 'asc' ? 'asc' : 'desc');
    }
}