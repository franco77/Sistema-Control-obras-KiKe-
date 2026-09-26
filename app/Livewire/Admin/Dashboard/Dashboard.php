<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboard;

use App\Enums\ExtraStatus;
use App\Enums\ProjectStatus;
use App\Enums\QuoteStatus;
use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\Project;
use App\Models\ProjectExtra;
use App\Models\ProjectIncident;
use App\Models\Quote;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Cuadro de mando: lo que hay que mirar cada mañana. Prioriza lo accionable
 * (presupuestos pendientes, obras retrasadas, documentos caducados) sobre
 * las métricas de vanidad.
 */
#[Layout('components.layouts.admin')]
#[Title('Panel')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard.dashboard', [
            'stats' => $this->stats(),
            'pendingQuotes' => Quote::with('client')->pending()->orderBy('valid_until')->limit(6)->get(),
            'activeProjects' => Project::with('client')->open()->orderBy('planned_end')->limit(6)->get(),
            'upcomingEvents' => CalendarEvent::with('client', 'project')->upcoming(7)->limit(6)->get(),
            'openIncidents' => ProjectIncident::with('project')->critical()->limit(5)->get(),
            'expiringDocuments' => Document::with('documentable')->expiring(30)->orderBy('expires_on')->limit(5)->get(),
        ]);
    }

    /** @return array<string, mixed> */
    private function stats(): array
    {
        $month = now()->startOfMonth();

        return [
            'active_projects' => Project::open()->count(),
            'delayed_projects' => Project::delayed()->count(),
            'pending_quotes' => Quote::pending()->count(),
            'pending_quotes_amount' => (float) Quote::pending()->sum('total'),
            'approved_month' => (float) Quote::where('status', QuoteStatus::Approved)
                ->where('decided_at', '>=', $month)->sum('total'),
            'contracted' => (float) Project::whereNotIn('status', [ProjectStatus::Cancelled])
                ->sum('budget_total'),
            'pending_extras' => ProjectExtra::where('status', ExtraStatus::Sent)->count(),
            'unread_messages' => Conversation::unanswered()->count(),
            'new_clients_month' => Client::where('created_at', '>=', $month)->count(),
        ];
    }
}