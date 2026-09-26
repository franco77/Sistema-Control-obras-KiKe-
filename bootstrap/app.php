<?php

use App\Console\Commands\ExpireQuotesCommand;
use App\Console\Commands\NotifyDelayedProjectsCommand;
use App\Console\Commands\NotifyExpiringDocumentsCommand;
use App\Console\Commands\SendEventRemindersCommand;
use App\Exceptions\InvalidTransitionException;
use App\Exceptions\ProviderNotAssignableException;
use App\Http\Middleware\EnsurePortalAbility;
use App\Http\Middleware\StartPortalSession;
use App\Http\Middleware\TrackLastLogin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('portal')
                ->as('portal.')
                ->group(base_path('routes/portal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'portal' => StartPortalSession::class,
            'portal.can' => EnsurePortalAbility::class,
            'role' => Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);

        $middleware->appendToGroup('web', TrackLastLogin::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Las violaciones de reglas de negocio se devuelven al usuario como
        // un mensaje legible, no como un error 500.
        $exceptions->render(function (InvalidTransitionException|ProviderNotAssignableException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command(ExpireQuotesCommand::class)->dailyAt('01:00');
        $schedule->command(NotifyExpiringDocumentsCommand::class)->dailyAt('07:30');
        $schedule->command(NotifyDelayedProjectsCommand::class)->weekdays()->at('08:00');
        $schedule->command(SendEventRemindersCommand::class)->everyFifteenMinutes();
    })
    ->create();
