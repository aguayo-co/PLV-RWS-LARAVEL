<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

trait CanFilter
{
    public static $allowedWhereIn = ['id'];
    public static $allowedWhereHas = []; # Example: ['query_name' => 'relation' , 'color_ids' => 'colors']
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
            $readyFilters[$column] = array_filter(explode(',', $values));
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
            $readyFilters[$allowed[$filter]] = array_filter(explode(',', $values));
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
            $readyFilters[$column] = array_slice(array_filter(explode(',', $values)), 0, 2);
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

        foreach ($this->getWhereIn($filters, $allowedWhereIn) as $column => $in) {
            $query = $query->wherein($column, $in);
        }
        foreach ($this->getWhereHas($filters, $allowedWhereHas) as $relation => $in) {
            $query = $query->whereHas($relation, function ($q) use ($in) {
                $q->whereIn('id', $in);
            });
        }
        foreach ($this->getWhereBetween($filters, $allowedWhereBetween) as $column => $between) {
            $query = $query->whereBetween($column, $between);
        }
        foreach ($this->getWhereDates($filters, $allowedWhereDates) as $column => $between) {
            $query = $query->whereBetween($column, $between);
        }
        foreach ($this->getWhereLike($filters, $allowedWhereLike) as $column => $like) {
            $query = $query->where($column, 'like', $like);
        }
        return $query;
    }
}
