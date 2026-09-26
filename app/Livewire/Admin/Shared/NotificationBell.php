<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Shared;

use Livewire\Attributes\On;
use Livewire\Component;

/** Campana de notificaciones internas (canal database). */
class NotificationBell extends Component
{
    public bool $open = false;

    #[On('notifications-updated')]
    public function refreshList(): void
    {
        // El render se encarga; el evento solo fuerza la actualización.
    }

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('livewire.admin.shared.notification-bell', [
            'notifications' => auth()->user()->notifications()->latest()->limit(10)->get(),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}