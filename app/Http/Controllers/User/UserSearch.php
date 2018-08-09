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

        $matches = [
            'nameLike' => 'CONCAT_WS(" ", users.first_name, users.last_name) = ?',
            'name' => 'MATCH (users.first_name,users.last_name) AGAINST(? IN BOOLEAN MODE)',
        ];

        // Make sure we load all products columns.
        if (!$query->getQuery()->columns) {
            $query->addSelect('users.*');
        }

        $wordCount = count(explode(' ', $search));

        if ($wordCount > 1) {
            // Exact title or owner go first.
            $query = $query->selectRaw('IF(' . $matches['nameLike'] . ', 1, 0) as nameLikeScore', [$search]);
        }

        // Then just do FullText search.
        $query = $query->selectRaw($matches['name'] . ' as nameScore', [$search]);

        $query = $query->where(function ($query) use ($matches, $search, $wordCount) {
            $query = $query->whereRaw($matches['name'], $search);
            if ($wordCount > 1) {
                $query = $query->orWhereRaw($matches['nameLike'], $search);
            }
        });
        if ($wordCount > 1) {
            $query = $query->orderByRaw('nameLikeScore DESC');
        }
        $query = $query->orderByRaw("nameScore DESC");

        return $query;
    }
}
