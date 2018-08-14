<?php

namespace App\Http\Controllers\User;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait UserSearch
{
    // Perform search for users.
    protected function doSearch(Request $request, Builder $query, $controllerClass)
    {
        $search = $request->query('q') ?: null;
        if (!$search) {
            return $query;
        }

        $match = 'MATCH (users.full_name) AGAINST(? IN BOOLEAN MODE)';

        // Then just do FullText search.
        $query = $query->whereRaw($match, [$search]);

        if (count(explode(' ', $search)) > 1) {
            $query = $query->orderByRaw('IF(full_name = ?, 1, 0) DESC', [$search]);
        }
        $query = $query->orderByRaw("{$match} DESC", [$search]);

        return $query;
    }
}
