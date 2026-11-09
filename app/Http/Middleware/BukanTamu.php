<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Menu yang mengubah data induk (template surat, data penduduk) tertutup untuk akun tamu. */
class BukanTamu
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isTamu(), 403, 'Akun tamu hanya untuk mencoba pelayanan & pembuatan surat.');

        return $next($request);
    }
}
