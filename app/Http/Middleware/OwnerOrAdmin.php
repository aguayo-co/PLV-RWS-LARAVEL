<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class OwnerOrAdmin
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
        $user = auth()->user();
        if (!$user) {
            abort(Response::HTTP_FORBIDDEN, 'Must be someone.');
        }

        $object = array_get(array_values($request->route()->parameters), 0);
        switch (true) {
            case !$object:
            // Case for nested paths and for same user:
            // /users/{user}/model/{model}
            // /users/{user}
            case $user->is($object):
            case $user->id === $object->user_id:
            case $object->owners_ids && $object->owners_ids->contains($user->id):
            case $user->hasRole('admin'):
                return $next($request);

            default:
                abort(Response::HTTP_FORBIDDEN, 'User must be owner or admin.');
        }
    }
}
