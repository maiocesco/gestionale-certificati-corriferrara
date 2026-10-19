<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Blocca ogni rotta diversa dal cambio password obbligatorio e dal logout
     * finché l'utente non ha sostituito la password provvisoria.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $allowedRoutes = ['password.change', 'password.change.update', 'logout'];

        if ($user && $user->must_change_password && ! $request->routeIs(...$allowedRoutes)) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
