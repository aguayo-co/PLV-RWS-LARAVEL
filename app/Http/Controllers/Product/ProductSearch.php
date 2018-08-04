<?php

namespace App\Http\Controllers\Product;

use App\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait ProductSearch
{
    // Perform search for products.
    // Search in titles, titles + description, and owner.
    protected function doSearch(Request $request, Builder $query, $controllerClass)
    {
        $search = $request->query('q') ?: null;
        if (!$search) {
            return $query;
        }

        $query = $query->join('users', 'products.user_id', '=', 'users.id');
        $query = $query->join('categories', 'products.category_id', '=', 'categories.id');
        $query = $query->join('brands', 'products.brand_id', '=', 'brands.id');

        $matches = [
            'ownerExact' => 'CONCAT_WS(" ", users.first_name, users.last_name) = ?',
            'owner' => 'MATCH (users.first_name,users.last_name) AGAINST(? IN BOOLEAN MODE)',

            'category' => 'MATCH (categories.name) AGAINST(? IN BOOLEAN MODE)',
            'brand' => 'MATCH (brands.name) AGAINST(? IN BOOLEAN MODE)',

            'titleExact' => 'products.title = ?',
            'title' => 'MATCH (products.title) AGAINST(? IN BOOLEAN MODE)',
        ];

        // Make sure we load all products columns.
        if (!$query->getQuery()->columns) {
            $query->addSelect('products.*');
        }

        // Exact title or owner go first.
        $query = $query->selectRaw('IF(' . $matches['ownerExact'] . ', 1, 0) as ownerExactScore', [$search]);
        $query = $query->selectRaw('IF(' . $matches['titleExact'] . ', 1, 0) as titleExactScore', [$search]);

        // Then just do FullText search.
        $query = $query->selectRaw($matches['owner'] . ' as ownerScore', [$search]);
        $query = $query->selectRaw($matches['category'] . ' as categoryScore', [$search]);
        $query = $query->selectRaw($matches['brand'] . ' as brandScore', [$search]);
        $query = $query->selectRaw($matches['title'] . ' as titleScore', [$search]);

        $query = $query->where(function ($query) use ($matches, $search) {
            $query = $query->whereRaw($matches['titleExact'], $search);
            $query = $query->orWhereRaw($matches['ownerExact'], $search);

            $query = $query->orWhereRaw($matches['category'], $search);
            $query = $query->orWhereRaw($matches['brand'], $search);

            $query = $query->orWhereRaw($matches['owner'], $search);
            $query = $query->orWhereRaw($matches['title'], $search);
        });
        $query = $query->orderByRaw('(ownerExactScore + titleExactScore) DESC');
        $searchScore = '(brandScore + categoryScore + titleScore + ownerScore)';
        $query = $query->orderByRaw("{$searchScore} DESC");

        return $query;
    }
}
