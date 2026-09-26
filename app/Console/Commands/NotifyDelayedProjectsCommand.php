<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Project;
use App\Notifications\Internal\ProjectDelayedNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;

/** Alerta interna de obras que han rebasado su fecha de fin prevista. */
class NotifyDelayedProjectsCommand extends Command
{
    protected $signature = 'crm:notify-delayed-projects';

    protected $description = 'Notifica al equipo las obras retrasadas';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $projects = Project::with('client', 'manager')->delayed()->get();

        foreach ($projects as $project) {
            $recipients = collect([$project->manager])->filter();

            if ($recipients->isEmpty()) {
                $dispatcher->toRole('admin', new ProjectDelayedNotification($project));
            } else {
                $dispatcher->toStaff($recipients, new ProjectDelayedNotification($project));
            }
        }

        $this->info("{$projects->count()} obras retrasadas notificadas.");

        return self::SUCCESS;
    }
}