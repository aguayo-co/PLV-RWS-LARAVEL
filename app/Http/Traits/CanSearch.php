<?php

namespace App\Http\Traits;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait CanSearch
{
    public static $searchIn = [];

    /**
     * Apply MATCH clauses to the given query.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  $controllerClass
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function doSearch(Request $request, Builder $query, $controllerClass)
    {
        $table = $query->getQuery()->from . '.';

        $columns = $table . implode(',' . $table, $controllerClass::$searchIn);

        $search = $request->query('q') ?: null;

        if ($search && $columns) {
            $query = $query->whereRaw('MATCH (' . $columns . ') AGAINST(? IN BOOLEAN MODE)', $search);
        }
        return $query;
    }
}
