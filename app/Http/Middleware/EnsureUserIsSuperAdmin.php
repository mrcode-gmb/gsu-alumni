<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (! $request->user()?->isSuperAdmin()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Only super admin users can manage charge settings.');
        }

        return $next($request);
    }
}
