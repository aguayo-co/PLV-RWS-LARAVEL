<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait ProductsReport
{
    protected function getInitialProductsData(Request $request)
    {
        return DB::table('products')
            ->whereRaw('created_at < ?', $request->from)
            ->addSelect(DB::raw('COUNT(id) as productsCount'))
            ->addSelect(DB::raw('SUM(price) as productsPriceTotal'))
            ->get();
    }

    protected function getProductsReport(Request $request)
    {
        $query = DB::table('products');

        $this->setDateRanges($request, 'created_at', $query);

        $query->addSelect(DB::raw('COUNT(id) as newProductsCount'))
            ->addSelect(DB::raw('SUM(price) as newProductsPriceTotal'));

        return $query->get();
    }
}
