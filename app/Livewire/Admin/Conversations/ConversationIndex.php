<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conversations;

use App\Models\Conversation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Mensajes')]
class ConversationIndex extends Component
{
    use WithPagination;

    #[Url(as: 'filtro', except: 'open')]
    public string $filter = 'open';

    public function render()
    {
        return view('livewire.admin.conversations.conversation-index', [
            'conversations' => Conversation::query()
                ->with('client', 'project', 'assignee')
                ->withCount('messages')
                ->when($this->filter === 'open', fn ($q) => $q->open())
                ->when($this->filter === 'unanswered', fn ($q) => $q->unanswered())
                ->orderByDesc('last_message_at')
                ->paginate(20),
            'unansweredCount' => Conversation::unanswered()->count(),
        ]);
    }
}