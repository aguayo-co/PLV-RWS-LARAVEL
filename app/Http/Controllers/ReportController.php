<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Report\CreditsTransactionsReport;
use App\Http\Controllers\Report\OrdersReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReportController extends BaseController
{
    use AuthorizesRequests;
    use OrdersReport;
    use CreditsTransactionsReport;

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

    /**
     * Every report (query) should be passed to this method to set the date ranges
     * and conditions.
     * It receives the request, the date field where to group by, and the query.
     *
     * The conditions are set using Raw expressions, which means a JSON path/expression
     * can be passed as the field.
     */
    protected function setDateRanges(Request $request, $dateField, $query)
    {
        $formattedDate = "DATE_FORMAT(CONVERT_TZ({$dateField}, 'UTC', '{$request->tz}'), '{$this->dateGroupByFormat}')";

        // We have to group using the request's timezone to avoid splitting days in 2
        // For the requesting user.
        // We still return data in UTC times.
        $query->select(DB::raw("{$formattedDate} as date_range"))
            ->whereRaw("{$dateField} >= ?", $request->from)
            ->whereRaw("{$dateField} < ?", $request->until)
            ->addSelect(DB::raw("MIN({$dateField}) as since"))
            ->addSelect(DB::raw("MAX({$dateField}) as until"))
            ->groupBy('date_range');
    }

    public function show(Request $request)
    {
        $this->validate($request);

        $this->setDateGroupByFormat($request);

        $ordersReport = $this->getOrdersReport($request);
        $creditsReport = $this->getCreditsTransactionsReport($request);

        $ordersReportKeys = [
            'cashIn',
            'productsTotal',
            'discountPrilov',
            'discountSeller',
            'shippingCostsTotal',
            'grossRevenue',
            'payedAndCanceledCount',
            'returnsCount',
            'creditsForSalesTotal',
            'creditsForOrdersTotal',
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

        $runningCredits = $this->getInitialCredits($request);
        foreach ($creditsReport as $range) {
            // Each query can have different initial and ending dates.
            // We use the larges range possible, which means the earliest date of each query
            // for the since (initial) date, and the latest date of each query for the until (to) date.
            $existingSinceDate = data_get($rows['ranges'], $range->date_range . '.0', new Carbon($range->since));
            $existingUntilDate = data_get($rows['ranges'], $range->date_range . '.1', new Carbon($range->until));

            // To do so we get the dates from the existing range data, if one exists, and compare it
            // to the dates of this query's ranges.
            $rows['ranges'][$range->date_range] = [
                min(new Carbon($range->since), $existingSinceDate),
                max(new Carbon($range->until), $existingUntilDate)
            ];
            $runningCredits += $range->credits;
            $rows['creditsDebt'][$range->date_range] = $runningCredits;
        }

        // Sort ranges, any other just let whoever consumes the API use 'ranges' as the
        // guide.
        ksort($rows['ranges']);

        return $rows;
    }
}
