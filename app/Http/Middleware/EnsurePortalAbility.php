<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PortalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Comprueba que el token del portal tiene el permiso concreto que exige la
 * ruta (por ejemplo extra.decide). Evita que un enlace emitido para ver un
 * presupuesto sirva para aprobar extras de una obra.
 */
class EnsurePortalAbility
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        /** @var PortalAccessToken|null $token */
        $token = $request->attributes->get('portalToken');

        abort_if($token === null, 403);

        foreach ($abilities as $ability) {
            if ($token->can($ability)) {
                return $next($request);
            }
        }

        abort(403, 'Este enlace no permite realizar esta acción.');
    }
}