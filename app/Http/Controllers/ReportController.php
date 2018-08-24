<?php

namespace App\Http\Controllers;

use App\Order;
use App\Sale;
use App\SaleReturn;
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

    protected function setDateGroupByFormat(Request $request)
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
        }
    }

    protected function setVariables(Request $request)
    {
        $this->orderPaymentStatus = Order::STATUS_PAYMENT;
        $this->orderPayedStatus = Order::STATUS_PAYED;
        $this->saleCanceledStatus = Sale::STATUS_CANCELED;

        $this->appliedCouponJsonPath = "orders.applied_coupon->'$.discount'";

        // Payment date.
        $this->paymentJsonPath = "orders.status_history->'$.\"{$this->orderPaymentStatus}\".date'";
        $this->paymentDate = "CAST(JSON_UNQUOTE({$this->paymentJsonPath}) as DATETIME)";
        $this->formatedDate = "DATE_FORMAT(CONVERT_TZ({$this->paymentDate}, 'UTC', '{$request->tz}'), '{$this->dateGroupByFormat}')";

        // Commission.
        $this->commissionFormula = 'CAST(products.price * products.commission / 100 as SIGNED)';
        $this->commissionCondition = "IF(sales.status < {$this->saleCanceledStatus}, {$this->commissionFormula}, 0)";
    }

    protected function getSubQuerySales(Request $request)
    {
        // First query, to aggregate by sales.
        // We calculate values aggregated by sales.
        $subQuerySales = DB::table('orders')
            ->join('sales', 'orders.id', '=', 'sales.order_id')
            ->join('product_sale', 'sales.id', '=', 'product_sale.sale_id')
            ->join('products', 'product_sale.product_id', '=', 'products.id')
            ->leftJoin('sale_returns', 'product_sale.sale_return_id', '=', 'sale_returns.id')
            ->where('orders.status', $this->orderPayedStatus)
            // Skip canceled and pending returns.
            ->where(function ($query) {
                $query->whereNull('sale_returns.status')
                    ->orWhere(function ($query) {
                        $query->where('sale_returns.status', '>', SaleReturn::STATUS_PENDING)
                            ->where('sale_returns.status', '<', SaleReturn::STATUS_CANCELED);
                    });
            })
            ->select(DB::raw('orders.id as id'))
            // Initial price of products.
            ->addSelect(DB::raw('SUM(products.price) as productsTotal'))
            // Price at which products were sold.
            ->addSelect(DB::raw('SUM(product_sale.price) as productsSalePriceTotal'))
            ->addSelect(DB::raw('IFNULL(sales.shipment_details->"$.cost", 0) as shippingCost'))
            ->addSelect(DB::raw("SUM({$this->commissionCondition}) as grossRevenue"))
            ->addSelect(DB::raw("IF(sales.status = {$this->saleCanceledStatus}, 1, NULL) as payedAndCanceled"))
            // Count how many valid returns we got.
            ->addSelect(DB::raw("COUNT(DISTINCT sale_returns.id) as returnsCount"))
            ->addSelect(DB::raw("COUNT(DISTINCT products.id) as productsCount"))
            ->groupBy(['sales.id']);

        $subQuerySales = $subQuerySales->whereRaw("{$this->paymentDate} >= ?", $request->from);
        $subQuerySales = $subQuerySales->whereRaw("{$this->paymentDate} < ?", $request->until);

        return $subQuerySales;
    }

    protected function getSubQueryOrders(Request $request)
    {
        $subQuerySales = $this->getSubQuerySales($request);

        // Second query:
        // No new data here, just aggregate by orders what we got from first query.
        return DB::table('orders')
            ->joinSub($subQuerySales, 'totaledSales', 'totaledSales.id', '=', 'orders.id')
            ->select(DB::raw('orders.id as id'))
            ->addSelect(DB::raw('SUM(productsTotal) as productsTotal'))
            ->addSelect(DB::raw('SUM(productsSalePriceTotal) as productsSalePriceTotal'))
            ->addSelect(DB::raw('SUM(shippingCost) as shippingCostsTotal'))
            ->addSelect(DB::raw('SUM(grossRevenue) as grossRevenue'))
            ->addSelect(DB::raw('SUM(payedAndCanceled) as payedAndCanceledCount'))
            ->addSelect(DB::raw('SUM(returnsCount) as returnsCount'))
            ->addSelect(DB::raw('COUNT(totaledSales.id) as salesCount'))
            ->addSelect(DB::raw('SUM(productsCount) as productsCount'))
            ->groupBy('orders.id');
    }

    protected function getOrdersReport(Request $request)
    {
        $subQueryOrders = $this->getSubQueryOrders($request);

        // Third query uses aggregated fields from sub-queries and adds values from orders.
        // 3 queries are needed because there is no way to have DISTINCT values on a column
        // by the table id.
        // If we could SUM a column when DISTINCT on ID, one query would suffice.
        $query = DB::table('orders')
            ->joinSub($subQueryOrders, 'totaledOrders', 'totaledOrders.id', '=', 'orders.id')

            // We have to group using the request timezone to avoid splitting days in 2
            // For the requesting user.
            // We still return data in UTC times.
            ->select(DB::raw("{$this->formatedDate} as date_range"))
            ->addSelect(DB::raw("MIN({$this->paymentDate}) as since"))
            ->addSelect(DB::raw("MAX({$this->paymentDate}) as until"))

            ->addSelect(DB::raw("SUM(productsSalePriceTotal - {$this->appliedCouponJsonPath}) as cashIn"))
            ->addSelect(DB::raw('CAST(SUM(productsTotal) as SIGNED) as productsTotal'))
            ->addSelect(DB::raw("SUM({$this->appliedCouponJsonPath}) as discountPrilov"))
            ->addSelect(DB::raw('CAST(SUM(productsTotal - productsSalePriceTotal) as SIGNED) as discountSeller'))
            ->addSelect(DB::raw('SUM(shippingCostsTotal) as shippingCostsTotal'))
            ->addSelect(DB::raw("SUM(grossRevenue - {$this->appliedCouponJsonPath}) as grossRevenue"))
            ->addSelect(DB::raw('CAST(SUM(payedAndCanceledCount) as SIGNED) as payedAndCanceledCount'))
            ->addSelect(DB::raw('CAST(SUM(returnsCount) as SIGNED) as returnsCount'))
            ->addSelect(DB::raw('CAST(SUM(salesCount) as SIGNED) as salesCount'))
            ->addSelect(DB::raw('CAST(SUM(productsCount) as SIGNED) as productsCount'))

            ->groupBy('date_range');

        return $query->get();
    }

    public function show(Request $request)
    {
        $this->validate($request);

        $this->setDateGroupByFormat($request);
        $this->setVariables($request);

        $ordersReport = $this->getOrdersReport($request);


        $ordersReportKeys = [
            'cashIn',
            'productsTotal',
            'discountPrilov',
            'discountSeller',
            'shippingCostsTotal',
            'grossRevenue',
            'payedAndCanceledCount',
            'returnsCount',
            'salesCount',
            'productsCount',
        ];

        $rows = [
            'groupBy' => $request->groupBy,
            'ranges' => [],
        ];

        foreach ($ordersReportKeys as $key) {
            $rows[$key] = [];
        }

        foreach ($ordersReport as $range) {
            $rows['ranges'][$range->date_range] = [new Carbon($range->since), new Carbon($range->until)];
            foreach ($ordersReportKeys as $key) {
                $rows[$key][$range->date_range] = $range->$key;
            }
        }
        return $rows;
    }
}
