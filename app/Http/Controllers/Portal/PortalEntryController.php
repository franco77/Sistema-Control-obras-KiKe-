<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalAccessToken;
use App\Models\Project;
use App\Models\ProjectExtra;
use App\Models\Provider;
use App\Models\Quote;
use App\Services\Portal\PortalTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Puerta de entrada al portal.
 *
 * Canjea el token en claro del enlace por una sesión de portal y redirige a
 * una URL limpia: así el token deja de viajar en la barra de direcciones,
 * en el historial y en las cabeceras Referer.
 */
class PortalEntryController extends Controller
{
    public function __construct(private readonly PortalTokenService $tokens) {}

    public function enter(Request $request, string $token): RedirectResponse
    {
        $accessToken = $this->tokens->resolve($token);

        if (! $accessToken) {
            return redirect()->route('portal.expired')
                ->with('portal_error', 'Este enlace no es válido o ha caducado.');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('portal.token_id', $accessToken->id);

        $accessToken->markUsed($request->ip());

        return redirect()->to($this->homeFor($accessToken));
    }

    public function leave(Request $request): RedirectResponse
    {
        $request->session()->flush();
        $request->session()->invalidate();

        return redirect()->route('portal.expired')
            ->with('portal_error', 'Has cerrado tu sesión. Vuelve a usar el enlace del email cuando lo necesites.');
    }

    public function expired(): View
    {
        return view('portal.expired');
    }

    private function homeFor(PortalAccessToken $token): string
    {
        return match ($token->tokenable_type) {
            (new Quote)->getMorphClass() => route('portal.quote'),
            (new Project)->getMorphClass() => route('portal.project'),
            (new ProjectExtra)->getMorphClass() => route('portal.extra'),
            (new Provider)->getMorphClass() => route('portal.provider'),
            default => route('portal.expired'),
        };
    }
}