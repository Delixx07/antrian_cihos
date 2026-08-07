<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Blokir halaman bila belum login (sesi 'auth'). */
class RequireLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('auth')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
