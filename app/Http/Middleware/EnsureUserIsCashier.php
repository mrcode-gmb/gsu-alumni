<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCashier
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (! $request->user()?->isCashier()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Only cashier users can access receipt verification.');
        }

        return $next($request);
    }
}
