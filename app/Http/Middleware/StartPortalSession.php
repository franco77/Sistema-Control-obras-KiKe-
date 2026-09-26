<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PortalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guardia del portal sin login.
 *
 * El token en claro solo viaja en el primer enlace; a partir de ahí la
 * sesión guarda el identificador del token y este middleware lo revalida
 * en cada petición (incluidas las de Livewire), comprobando vigencia,
 * revocación y número de usos. Si deja de ser válido, cierra la sesión.
 */
class StartPortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $tokenId = $request->session()->get('portal.token_id');

        if (! $tokenId) {
            return $this->reject($request, 'Necesitas acceder desde el enlace que te hemos enviado.');
        }

        $token = PortalAccessToken::with('tokenable')->find($tokenId);

        if (! $token || ! $token->isValid()) {
            $request->session()->forget('portal.token_id');

            return $this->reject($request, 'Tu enlace de acceso ha caducado o ha sido revocado.');
        }

        if (! $token->tokenable) {
            return $this->reject($request, 'El recurso asociado a este enlace ya no existe.');
        }

        // Disponible para controladores, componentes Livewire y vistas.
        $request->attributes->set('portalToken', $token);
        view()->share('portalToken', $token);

        return $next($request);
    }

    private function reject(Request $request, string $message): Response
    {
        if ($request->hasHeader('X-Livewire')) {
            abort(419, $message);
        }

        return redirect()->route('portal.expired')->with('portal_error', $message);
    }
}