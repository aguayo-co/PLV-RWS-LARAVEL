<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

trait CanOrderBy
{
    public static $allowedOrderBy = ['id', 'created_at', 'updated_at'];
    public static $defaultOrderBy = []; # Example: ['id' => 'asc']
    public static $orderByAliases = [];

    /**
     * Generates an array off [columns => direction] to by used with orderBy
     * clauses on a query, filtered with acceptable columns and merged with
     * the controller default orderBy.
     *
     * @param  array $orderBy
     * @param  array $allowed
     * @param  array $default
     * @param  array $aliases
     * @return array
     */
    protected function getOrderBy(array $orderBy, array $allowed, array $default, array $aliases)
    {
        $orderBy = $orderBy ?: [];
        $readyOrderBy = [];
        foreach ($orderBy as $column) {
            $direction = 'asc';
            if (substr($column, 0, 1) === '-') {
                $direction = 'desc';
                $column = substr($column, 1);
            }
            $column = array_get($aliases, $column, $column);
            $readyOrderBy[$column] = $direction;
        }
        $allowedOrderBy = array_only($readyOrderBy, $allowed);
        return $allowedOrderBy + $default;
    }

    /**
     * Apply orderBy clauses to the given query.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  $controllerClass
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyOrderBy(Request $request, Builder $query, $controllerClass)
    {
        $default = $controllerClass::$defaultOrderBy;
        $allowed = $controllerClass::$allowedOrderBy;
        $aliases = $controllerClass::$orderByAliases;

        $orderBy = explode(',', $request->query('orderby'));
        foreach ($this->getOrderBy($orderBy, $allowed, $default, $aliases) as $column => $direction) {
            $query = $query->orderBy($column, $direction);
        }
        return $query;
    }
}
