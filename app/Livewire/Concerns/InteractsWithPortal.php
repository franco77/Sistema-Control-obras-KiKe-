<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\PortalAccessToken;
use App\Models\Project;
use Illuminate\Support\Facades\Session;

/**
 * Resuelve el token del portal desde la sesión en CADA petición Livewire.
 *
 * Es deliberado no guardar el token ni el id del recurso en propiedades
 * públicas del componente: viajan al navegador y podrían manipularse. La
 * fuente de verdad es siempre la sesión de servidor.
 */
trait InteractsWithPortal
{
    protected function portalToken(): PortalAccessToken
    {
        $token = PortalAccessToken::with('tokenable')->find(Session::get('portal.token_id'));

        abort_if($token === null || ! $token->isValid(), 419, 'Tu enlace de acceso ha caducado.');

        return $token;
    }

    protected function portalAbility(string $ability): void
    {
        abort_unless($this->portalToken()->can($ability), 403, 'Este enlace no permite realizar esta acción.');
    }

    /** Obra asociada al token, sea directa (Project) o indirecta (Extra). */
    protected function portalProject(): Project
    {
        $target = $this->portalToken()->tokenable;

        $project = $target instanceof Project
            ? $target
            : Project::find(data_get($target, 'project_id'));

        abort_if($project === null, 404);
        abort_unless($project->portal_enabled, 403, 'El portal de esta obra está desactivado.');

        return $project;
    }
}