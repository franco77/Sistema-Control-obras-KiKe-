<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sella el último acceso del usuario del panel (una vez por sesión). */
class TrackLastLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->session()->has('login_tracked')) {
            $request->user()->forceFill(['last_login_at' => now()])->saveQuietly();
            $request->session()->put('login_tracked', true);
        }

        return $next($request);
    }
}