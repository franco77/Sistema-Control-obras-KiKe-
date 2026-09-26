<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectExtra;
use App\Models\Provider;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // MariaDB con utf8mb4 y índices en versiones antiguas.
        Schema::defaultStringLength(191);

        // En desarrollo, romper pronto ante lazy loading o asignaciones raras.
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
        Model::unguard(false);

        Paginator::useTailwind();

        // Nombres estables en columnas polimórficas: si algún día se mueve o
        // renombra una clase, los datos guardados siguen resolviendo.
        Relation::enforceMorphMap([
            'client' => Client::class,
            'provider' => Provider::class,
            'quote' => Quote::class,
            'project' => Project::class,
            'extra' => ProjectExtra::class,
            'user' => \App\Models\User::class,
            'property' => \App\Models\Property::class,
            'incident' => \App\Models\ProjectIncident::class,
            'task' => \App\Models\ProjectTask::class,
        ]);

        // El rol admin lo puede todo; el resto pasa por permisos concretos.
        Gate::before(fn ($user, string $ability) => $user->hasRole('admin') ? true : null);

        \Illuminate\Support\Carbon::setLocale(config('app.locale'));
    }
}
