<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

trait CanFilter
{
    public static $allowedWhereIn = ['id'];
    // The allowedWhereHas filter by relations. Can be used to filter on a relation column (id by default),
    // or to just check for the existence of the relation.
    //
    // Column filter examples:
    // ['query_name' => 'relation[,column]', 'color_ids' => 'colors', 'sellers_ids' => 'sale,user_id']
    // Existence filter examples:
    // ['query_name' => 'relation,__has__', 'has_colors' => 'colors,__has__']
    public static $allowedWhereHas = [];
    public static $allowedWhereBetween = [];
    public static $allowedWhereDates = ['created_at', 'updated_at'];
    public static $allowedWhereLike = [];

    /**
     * Generates an array off [columns => [value, value, value, ...]] to by used with whereIn
     * clauses on a query, filtered with acceptable columns.
     *
     * @param  array $filters
     * @param  array $allowed
     * @return array
     */
    protected function getWhereIn(array $filters, array $allowed)
    {
        $filters = $filters ?: [];
        $readyFilters = [];
        foreach (array_only($filters, $allowed) as $column => $values) {
            $readyFilters[$column] = array_filter(explode(',', $values), 'strlen');
        }
        return $readyFilters;
    }

    /**
     * Generates an array off [relation => [value, value, value, ...]] to by used with whereHas
     * clauses on a query, filtered with acceptable columns.
     *
     * @param  array $filters
     * @param  array $allowed
     * @return array
     */
    protected function getWhereHas(array $filters, array $allowed)
    {
        $filters = $filters ?: [];
        $readyFilters = [];
        foreach (array_only($filters, array_keys($allowed)) as $filter => $values) {
            $readyFilters[$allowed[$filter]] = array_filter(explode(',', $values), 'strlen');
        }
        return $readyFilters;
    }

    /**
     * Generates an array off [columns => [value, value]] to by used with whereBetween
     * clauses on a query, filtered with acceptable columns.
     *
     * @param  array $filters
     * @param  array $allowed
     * @return array
     */
    protected function getWhereBetween(array $filters, array $allowed)
    {
        $filters = $filters ?: [];
        $readyFilters = [];
        foreach (array_only($filters, $allowed) as $column => $values) {
            $readyFilters[$column] = array_slice(array_filter(explode(',', $values), 'strlen'), 0, 2);
        }
        return $readyFilters;
    }

    /**
     * Generates an array off [columns => [value, value]] to by used with whereBetween
     * clauses on a query for dates, filtered with acceptable columns.
     * Values will be compared with dates, and no time.
     *
     * @param  array $filters
     * @param  array $allowed
     * @return array
     */
    protected function getWhereDates(array $filters, array $allowed)
    {
        $filters = $filters ?: [];
        $readyFilters = [];
        foreach (array_only($filters, $allowed) as $column => $values) {
            $exploded = explode(',', $values);
            try {
                $readyFilters[$column] = [
                    Carbon::parse($exploded[0])->toDateString(),
                    Carbon::parse($exploded[1])->addDay()->toDateString(),
                ];
            } catch (\Exception $e) {
            }
        }
        return $readyFilters;
    }

    /**
     * Generates an array off [columns => value] to by used with whereBetween
     * clauses on a query, filtered with acceptable columns.
     *
     * @param  array $filters
     * @param  array $allowed
     * @return array
     */
    protected function getWhereLike(array $filters, array $allowed)
    {
        $filters = $filters ?: [];
        $readyFilters = [];
        foreach (array_only($filters, $allowed) as $column => $values) {
            $readyFilters[$column] = $values;
        }
        return $readyFilters;
    }

    /**
     * Apply orderBy clauses to the given query.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  $controllerClass
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters(Request $request, Builder $query, $controllerClass)
    {
        $allowedWhereIn = $controllerClass::$allowedWhereIn;
        $allowedWhereHas = $controllerClass::$allowedWhereHas;
        $allowedWhereBetween = $controllerClass::$allowedWhereBetween;
        $allowedWhereDates = $controllerClass::$allowedWhereDates;
        $allowedWhereLike = $controllerClass::$allowedWhereLike;

        $filters = $request->query('filter') ?: [];

        if (!is_array($filters)) {
            return $query;
        }

        $table = $query->getQuery()->from . '.';

        foreach ($this->getWhereIn($filters, $allowedWhereIn) as $column => $in) {
            $query = $query->wherein($table . $column, $in);
        }
        foreach ($this->getWhereHas($filters, $allowedWhereHas) as $relation => $in) {
            $relation = explode(',', $relation);
            # Check if we have a column to use instead of 'id'.
            // Or if we should just perform a has() filter.
            $column = array_get($relation, 1, 'id');
            if ($column == '__has__') {
                $query = $query->has($relation[0]);
                continue;
            }
            $query = $query->whereHas($relation[0], function ($q) use ($column, $in) {
                $q->whereIn($column, $in);
            });
        }
        foreach ($this->getWhereBetween($filters, $allowedWhereBetween) as $column => $between) {
            switch (count($between)) {
                case 2:
                    $query = $query->whereBetween($table . $column, $between);
                    break;
                case 1:
                    $query = $query->where($table . $column, $between[0]);
                    break;
            }
        }
        foreach ($this->getWhereDates($filters, $allowedWhereDates) as $column => $between) {
            $query = $query->whereBetween($table . $column, $between);
        }
        foreach ($this->getWhereLike($filters, $allowedWhereLike) as $column => $like) {
            $query = $query->where($table . $column, 'like', $like);
        }
        return $query;
    }
}
