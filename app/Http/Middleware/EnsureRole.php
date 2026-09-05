<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde de rôle — équivalent de requireRole()/requireAdmin() de l'ancien
 * includes/auth.php. Usage dans routes/web.php :
 *   ->middleware('role:admin')
 *   ->middleware('role:particulier,entreprise')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $compte = $request->user();

        if (!$compte) {
            return redirect()->route('login');
        }

        $client = $compte->client;
        $role = $client ? $client->role() : 'admin';

        if (!in_array($role, $roles, true)) {
            abort(404);
        }

        return $next($request);
    }
}
