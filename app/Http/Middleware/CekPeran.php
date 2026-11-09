<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pemakaian di route: ->middleware('peran:admin,pelayanan') */
class CekPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        abort_unless($request->user()?->punyaPeran(...$peran), 403, 'Menu ini bukan untuk akun Anda.');

        return $next($request);
    }
}
