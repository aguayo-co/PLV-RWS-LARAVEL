<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
        switch ($request->groupby) {
            case 'day':
                $this->dateGroupByFormat = '%Y-%m-%d';
                break;

            case 'week':
                $this->dateGroupByFormat = 'Semana %u: %Y-%m';
                break;

            case 'month':
                $this->dateGroupByFormat = '%Y-%m';
                break;

            default:
                $this->dateGroupByFormat = '%Y';
                break;
        }

        $query = DB::table('orders')
            ->join('sales', 'orders.id', '=', 'sales.order_id')
            ->join('product_sale', 'sales.id', '=', 'product_sale.sale_id')
            ->join('products', 'product_sale.product_id', '=', 'products.id')
            ->where('orders.status', 30)
            ->select(DB::raw("DATE_FORMAT(orders.updated_at, '{$this->dateGroupByFormat}') as date_range"))
            // Cash In = Toda la plata que entra - envío - comisión plataforma
            ->addSelect(DB::raw('SUM(product_sale.price) - SUM(orders.applied_coupon->"$.discount") as cashIn'))
            // Gross Revenue: Total de comisiones con las que se queda Prilov.
            // Es decir la plata que efectivamente le quedó a Prilov
            // quitando cualquier descuento que esté asumiendo Prilov.
            ->addSelect(DB::raw('CAST(SUM(products.price * products.commission / 100) AS SIGNED) as grossRevenue'))
            ->groupBy('date_range');

        if ($request->since) {
            $query = $query->where('orders.updated_at', '>=', $request->since);
        }

        if ($request->until) {
            $query = $query->where('orders.updated_at', '<=', $request->until);
        }

        $result = $query->get();

        $rows = [
            'ranges' => [],
            'cashIn' => [],
            'grossRevenue' => [],
        ];
        foreach ($result as $range) {
            $rows['ranges'][] = $range->date_range;
            $rows['cashIn'][$range->date_range] = $range->cashIn;
            $rows['grossRevenue'][$range->date_range] = $range->grossRevenue;
        }
        return $rows;
    }
}
