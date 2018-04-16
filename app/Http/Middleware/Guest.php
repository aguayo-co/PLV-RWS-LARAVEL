<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class Guest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (auth()->guard($guard)->check()) {
            abort(Response::HTTP_FORBIDDEN, "Can not be someone.");
        }

        return $next($request);
    }
}
