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
        if (!$controllerClass::$searchIn) {
            return $query;
        }

        $search = $request->query('q') ?: null;
        if (!$search) {
            return $query;
        }

        $table = $query->getQuery()->from . '.';
        $columns = $table . implode(',' . $table, $controllerClass::$searchIn);

        $query = $query->whereRaw('MATCH (' . $columns . ') AGAINST(? IN BOOLEAN MODE)', $search);
        $query = $query->orderByRaw('MATCH (' . $columns . ') AGAINST(? IN BOOLEAN MODE) DESC', $search);
        return $query;
    }
}
