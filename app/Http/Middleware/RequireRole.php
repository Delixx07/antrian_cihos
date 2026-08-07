<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi akses halaman berdasarkan role (dari sesi 'role').
 * Pemakaian di route: ->middleware('role:administrator')
 * atau beberapa role: ->middleware('role:administrator,spv')
 */
class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = (string) $request->session()->get('role', '');

        if (! in_array($role, $roles, true)) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
