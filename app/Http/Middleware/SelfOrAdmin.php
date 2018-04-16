<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class SelfOrAdmin
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
        # If not trying to set the user_id, we don't care who you are.
        if (!$request->user_id) {
            return $next($request);
        }

        $user = auth()->user();
        if (!$user) {
            abort(Response::HTTP_FORBIDDEN, 'Must be someone.');
        }

        if ($user->id != $request->user_id && !$user->hasRole('admin')) {
            abort(Response::HTTP_FORBIDDEN, 'Only admin can set a different user_id.');
        }

        $object = array_get(array_values($request->route()->parameters), 0);
        if ($object && $object->user_id && $object->user_id != $request->user_id && !$user->hasRole('admin')) {
            abort(Response::HTTP_FORBIDDEN, 'Only admin can change the user_id.');
        }

        return $next($request);
    }
}
