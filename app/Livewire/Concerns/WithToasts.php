<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Avisos efímeros en la interfaz. Se emiten como evento de navegador para
 * que un único contenedor de toasts los pinte, sin acoplar los componentes.
 */
trait WithToasts
{
    public function toastSuccess(string $message, ?string $title = null): void
    {
        $this->toast('success', $message, $title);
    }

    public function toastError(string $message, ?string $title = null): void
    {
        $this->toast('error', $message, $title);
    }

    public function toastInfo(string $message, ?string $title = null): void
    {
        $this->toast('info', $message, $title);
    }

    protected function toast(string $type, string $message, ?string $title = null): void
    {
        $this->dispatch('toast', type: $type, message: $message, title: $title);
    }
}