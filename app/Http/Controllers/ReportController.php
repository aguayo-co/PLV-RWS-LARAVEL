<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseController
{
    use AuthorizesRequests;

    protected $dateGroupByFormat;
    protected $baseQuery;

    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function show(Request $request)
    {
        switch ($request->groupBy) {
            case 'day':
                $this->dateGroupByFormat = '%Y-%m-%d';
                break;

            case 'week':
                $this->dateGroupByFormat = '%u (%Y)';
                break;

            case 'month':
                $this->dateGroupByFormat = '%Y-%m';
                break;

            case 'year':
                $this->dateGroupByFormat = '%Y';
                break;

            default:
                abort(Response::HTTP_BAD_REQUEST, __('Missing or invalid groupBy.'));
        }

        $subQuery = DB::table('orders')
            ->join('sales', 'orders.id', '=', 'sales.order_id')
            ->join('product_sale', 'sales.id', '=', 'product_sale.sale_id')
            ->join('products', 'product_sale.product_id', '=', 'products.id')
            ->where('orders.status', 30)
            ->select(DB::raw('orders.id as id'))
            // Cash In = Toda la plata que entra - envío - comisión plataforma
            ->addSelect(DB::raw('SUM(product_sale.price) as products_total'))
            // Gross Revenue: Total de comisiones con las que se queda Prilov.
            // Es decir la plata que efectivamente le quedó a Prilov
            // quitando cualquier descuento que esté asumiendo Prilov.
            ->addSelect(DB::raw('CAST(SUM(products.price * products.commission / 100) AS SIGNED) as grossRevenue'))
            ->groupBy('orders.id');

        $query = DB::table('orders')
            ->select(DB::raw("DATE_FORMAT(orders.updated_at, '{$this->dateGroupByFormat}') as date_range"))
            ->addSelect(DB::raw('MIN(orders.updated_at) as since'))
            ->addSelect(DB::raw('MAX(orders.updated_at) as until'))
            ->addSelect(DB::raw('SUM(products_total - orders.applied_coupon->"$.discount") as cashIn'))
            ->addSelect(DB::raw('CAST(SUM(grossRevenue) AS SIGNED) as grossRevenue'))
            ->joinSub($subQuery, 'totaled_orders', 'totaled_orders.id', '=', 'orders.id')
            ->groupBy('date_range');

        if ($request->from) {
            $query = $query->where('orders.updated_at', '>=', $request->from);
        }

        if ($request->until) {
            $query = $query->where('orders.updated_at', '<=', $request->until);
        }

        $result = $query->get();

        $rows = [
            'groupBy' => $request->groupBy,
            'ranges' => [],
            'cashIn' => [],
            'grossRevenue' => [],
        ];
        foreach ($result as $range) {
            $rows['ranges'][$range->date_range] = [new Carbon($range->since), new Carbon($range->until)];
            $rows['cashIn'][$range->date_range] = $range->cashIn;
            $rows['grossRevenue'][$range->date_range] = $range->grossRevenue;
        }
        return $rows;
    }
}
