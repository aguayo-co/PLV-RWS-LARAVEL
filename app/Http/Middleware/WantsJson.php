<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class WantsJson
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->wantsJson()) {
            abort(Response::HTTP_NOT_ACCEPTABLE, "Must accept Json responses.");
        }
        return $next($request);
    }
}
