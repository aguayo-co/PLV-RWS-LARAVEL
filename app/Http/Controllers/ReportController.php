<?php

namespace App\Http\Controllers;

use App\Order;
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

        $payedStatus = Order::STATUS_PAYED;

        $subQuery = DB::table('orders')
            ->join('sales', 'orders.id', '=', 'sales.order_id')
            ->join('product_sale', 'sales.id', '=', 'product_sale.sale_id')
            ->join('products', 'product_sale.product_id', '=', 'products.id')
            ->where('orders.status', $payedStatus)
            ->select(DB::raw('orders.id as id'))
            // Cash In = Toda la plata que entra - envío - comisión plataforma
            ->addSelect(DB::raw('SUM(product_sale.price) as products_total'))
            // Gross Revenue: Total de comisiones con las que se queda Prilov.
            // Es decir la plata que efectivamente le quedó a Prilov
            // quitando cualquier descuento que esté asumiendo Prilov.
            ->addSelect(DB::raw('CAST(SUM(products.price * products.commission / 100) AS SIGNED) as grossRevenue'))
            ->groupBy('orders.id');

        $payedJsonPath = "`status_history`->'$.\"{$payedStatus}\".date'";
        $payedDate = "CAST(JSON_UNQUOTE({$payedJsonPath}) as DATETIME)";
        $formatedDate = "DATE_FORMAT(CONVERT_TZ({$payedDate}, 'UTC', '{$request->tz}'), '{$this->dateGroupByFormat}')";
        $query = DB::table('orders')
            // We have to group using the request timezone to avoid splitting days in 2
            // For the requesting user.
            // We still return data in UTC times.
            ->select(DB::raw("{$formatedDate} as date_range"))
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
            $query = $query->where('orders.updated_at', '<', $request->until);
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
