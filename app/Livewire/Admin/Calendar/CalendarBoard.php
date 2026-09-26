<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Calendar;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Livewire\Concerns\WithToasts;
use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProviderAvailability;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Agenda mensual y semanal: visitas, hitos, tareas programadas y la
 * disponibilidad declarada por los proveedores, todo en la misma rejilla.
 */
#[Layout('components.layouts.admin')]
#[Title('Agenda')]
class CalendarBoard extends Component
{
    use WithToasts;

    #[Url(as: 'mes')]
    public string $month = '';

    #[Url(as: 'vista')]
    public string $view = 'month'; // month | list

    #[Url(as: 'tipo', except: '')]
    public string $typeFilter = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    public array $form = [
        'title' => '', 'description' => '', 'type' => 'visit', 'status' => 'scheduled',
        'starts_at' => '', 'ends_at' => '', 'all_day' => false, 'location' => '',
        'client_id' => null, 'project_id' => null, 'provider_id' => null,
        'owner_id' => null, 'reminder_at' => '', 'visible_to_client' => false,
    ];

    public function mount(): void
    {
        $this->month = $this->month ?: now()->format('Y-m');
    }

    public function shiftMonth(int $delta): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonths($delta)->format('Y-m');
    }

    public function today(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function newEvent(?string $date = null): void
    {
        $this->reset('form', 'editingId');

        $start = $date ? Carbon::parse($date)->setTime(9, 0) : now()->addHour()->startOfHour();

        $this->form['starts_at'] = $start->format('Y-m-d\TH:i');
        $this->form['ends_at'] = $start->copy()->addHour()->format('Y-m-d\TH:i');
        $this->form['owner_id'] = auth()->id();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $event = CalendarEvent::findOrFail($id);
        $this->editingId = $id;
        $this->form = [
            'title' => $event->title,
            'description' => (string) $event->description,
            'type' => $event->type->value,
            'status' => $event->status->value,
            'starts_at' => $event->starts_at->format('Y-m-d\TH:i'),
            'ends_at' => $event->ends_at->format('Y-m-d\TH:i'),
            'all_day' => $event->all_day,
            'location' => (string) $event->location,
            'client_id' => $event->client_id,
            'project_id' => $event->project_id,
            'provider_id' => $event->provider_id,
            'owner_id' => $event->owner_id,
            'reminder_at' => $event->reminder_at?->format('Y-m-d\TH:i') ?? '',
            'visible_to_client' => $event->visible_to_client,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.title' => ['required', 'string', 'max:191'],
            'form.description' => ['nullable', 'string', 'max:2000'],
            'form.type' => ['required', Rule::enum(EventType::class)],
            'form.status' => ['required', Rule::enum(EventStatus::class)],
            'form.starts_at' => ['required', 'date'],
            'form.ends_at' => ['required', 'date', 'after_or_equal:form.starts_at'],
            'form.location' => ['nullable', 'string', 'max:191'],
            'form.client_id' => ['nullable', 'exists:clients,id'],
            'form.project_id' => ['nullable', 'exists:projects,id'],
            'form.provider_id' => ['nullable', 'exists:providers,id'],
            'form.owner_id' => ['nullable', 'exists:users,id'],
            'form.reminder_at' => ['nullable', 'date'],
        ])['form'];

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        $this->editingId
            ? CalendarEvent::findOrFail($this->editingId)->update($data)
            : CalendarEvent::create($data + ['created_by' => auth()->id()]);

        $this->reset('form', 'editingId', 'showForm');
        $this->toastSuccess('Evento guardado.');
    }

    public function delete(int $id): void
    {
        CalendarEvent::findOrFail($id)->delete();
        $this->toastSuccess('Evento eliminado.');
    }

    public function markDone(int $id): void
    {
        CalendarEvent::findOrFail($id)->update(['status' => EventStatus::Done]);
    }

    public function render()
    {
        $cursor = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $gridStart = $cursor->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $cursor->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $events = CalendarEvent::with('client', 'project', 'provider', 'owner')
            ->between($gridStart->toDateTimeString(), $gridEnd->toDateTimeString())
            ->type($this->typeFilter ?: null)
            ->orderBy('starts_at')
            ->get();

        // Ausencias de proveedores superpuestas al mes visible.
        $absences = ProviderAvailability::with('provider')
            ->blocking()
            ->overlapping($gridStart->toDateString(), $gridEnd->toDateString())
            ->get();

        return view('livewire.admin.calendar.calendar-board', [
            'cursor' => $cursor,
            'gridStart' => $gridStart,
            'gridEnd' => $gridEnd,
            'days' => $gridStart->toPeriod($gridEnd)->toArray(),
            'eventsByDay' => $events->groupBy(fn (CalendarEvent $e) => $e->starts_at->toDateString()),
            'events' => $events,
            'absences' => $absences,
            'types' => EventType::cases(),
            'statuses' => EventStatus::cases(),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'projects' => Project::open()->orderBy('name')->get(['id', 'name', 'code']),
            'providers' => Provider::orderBy('name')->get(['id', 'name']),
            'users' => User::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}