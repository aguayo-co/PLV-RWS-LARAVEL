<?php

namespace App\Http\Controllers;

use App\Order;
use App\Sale;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReportController extends BaseController
{
    use AuthorizesRequests;

    protected $dateGroupByFormat;
    protected $baseQuery;

    public function __construct()
    {
        $this->middleware('role:admin');
    }

    protected function validate(Request $request)
    {
        Validator::make($request->all(), [
            'groupBy' => 'required|in:day,week,month,year',
            // Timezone to use when group data.
            'tz' => 'required|timezone',
            // Dates should come in UTC.
            'from' => 'required|date_format:Y-m-d H:i:s',
            'until' => 'required|date_format:Y-m-d H:i:s',
        ])->validate();
    }

    public function show(Request $request)
    {
        $this->validate($request);

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
        }

        $orderPaymentStatus = Order::STATUS_PAYMENT;
        $orderPayedStatus = Order::STATUS_PAYED;
        $saleCanceledStatus = Sale::STATUS_CANCELED;

        // Payment date.
        $paymentJsonPath = "orders.status_history->'$.\"{$orderPaymentStatus}\".date'";
        $paymentDate = "CAST(JSON_UNQUOTE({$paymentJsonPath}) as DATETIME)";
        $formatedDate = "DATE_FORMAT(CONVERT_TZ({$paymentDate}, 'UTC', '{$request->tz}'), '{$this->dateGroupByFormat}')";

        // Commission.
        $commissionFormula = 'CAST(products.price * products.commission / 100 as SIGNED)';
        $commissionCondition = "IF(sales.status < {$saleCanceledStatus}, {$commissionFormula}, 0)";

        // First query, to aggregate by sales.
        // We calculate values aggregated by sales.
        $subQuerySales = DB::table('orders')
            ->join('sales', 'orders.id', '=', 'sales.order_id')
            ->join('product_sale', 'sales.id', '=', 'product_sale.sale_id')
            ->join('products', 'product_sale.product_id', '=', 'products.id')
            ->where('orders.status', $orderPayedStatus)
            ->select(DB::raw('orders.id as id'))
            ->addSelect(DB::raw('SUM(product_sale.price) as productsTotal'))
            ->addSelect(DB::raw('IFNULL(sales.shipment_details->"$.cost", 0) as shippingCost'))
            // Gross Revenue: Total de comisiones con las que se queda Prilov.
            // Es decir la plata que efectivamente le quedó a Prilov
            // quitando cualquier descuento que esté asumiendo Prilov o cancelaciones.
            ->addSelect(DB::raw("SUM({$commissionCondition}) as grossRevenue"))
            ->groupBy('sales.id');

        if ($request->from) {
            $subQuerySales = $subQuerySales->whereRaw("{$paymentDate} >= ?", $request->from);
        }

        if ($request->until) {
            $subQuerySales = $subQuerySales->whereRaw("{$paymentDate} < ?", $request->until);
        }

        // Second query, to aggregate by orders.
        $subQueryOrders = DB::table('orders')
            ->joinSub($subQuerySales, 'totaledSales', 'totaledSales.id', '=', 'orders.id')
            ->select(DB::raw('orders.id as id'))
            ->addSelect(DB::raw('SUM(productsTotal - shippingCost) as salesTotal'))
            ->addSelect(DB::raw('SUM(grossRevenue) as grossRevenue'))
            ->groupBy('orders.id');

        // Third query uses aggregated fields from sub-queries and adds values from orders.
        // 3 queries are needed because there is no way to have DISTINCT values on a column
        // by the table id.
        // If we could SUM a column when DISTINCT on ID, one query would suffice.
        $query = DB::table('orders')
            // We have to group using the request timezone to avoid splitting days in 2
            // For the requesting user.
            // We still return data in UTC times.
            ->select(DB::raw("{$formatedDate} as date_range"))
            ->addSelect(DB::raw("MIN({$paymentDate}) as since"))
            ->addSelect(DB::raw("MAX({$paymentDate}) as until"))
            ->addSelect(DB::raw('SUM(salesTotal - orders.applied_coupon->"$.discount") as cashIn'))
            ->addSelect(DB::raw('CAST(SUM(grossRevenue) as SIGNED) as grossRevenue'))
            ->joinSub($subQueryOrders, 'totaledOrders', 'totaledOrders.id', '=', 'orders.id')
            ->groupBy('date_range');

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
