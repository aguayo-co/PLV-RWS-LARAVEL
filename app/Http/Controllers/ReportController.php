<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Report\CreditsTransactionsReport;
use App\Http\Controllers\Report\NewDataReport;
use App\Http\Controllers\Report\OrdersReport;
use App\Http\Controllers\Report\ProductsReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReportController extends BaseController
{
    use NewDataReport;
    use ProductsReport;
    use AuthorizesRequests;
    use OrdersReport;
    use CreditsTransactionsReport;

    protected $dateGroupByFormat;
    protected $baseQuery;
    protected $rowsResponse;

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
                $this->dateGroupByFormat = '(%Y) %u ';
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

    /**
     * Set or update the global dates for each range.
     *
     * Each query can have different initial and ending dates.
     * We use the largest range possible, which means the earliest date of each query
     * for the since (initial) date, and the latest date of each query for the until (to) date.
     *
     * To do so we get the dates from the existing range data, if one exists, and compare it
     * to the dates of this query's ranges.
     */
    protected function setRangeDates(&$rows, $range)
    {
        // Get dates for the range if ones exist already.
        $existingSinceDate = data_get($rows['ranges'], $range->date_range . '.0', new Carbon($range->since));
        $existingUntilDate = data_get($rows['ranges'], $range->date_range . '.1', new Carbon($range->until));

        // Set the earliest and latest dates.
        $rows['ranges'][$range->date_range] = [
            min(new Carbon($range->since), $existingSinceDate),
            max(new Carbon($range->until), $existingUntilDate)
        ];
    }

    public function showInparts(Request $request, $nextPart)
    {
        $this->validate($request);

        $this->setDateGroupByFormat($request);

        $this->rowsResponse = $request->json('nexResponse') ?? [];

        $response = [
            'nextPart' => null,
            'nexResponse' => []
        ];

        switch ($nextPart) {
            case '1':
                $response['nextPart'] = '2';
                $response['nexResponse'] = $this->showFirst($request);
                break;
            case '2':
                $response['nextPart'] = '3';
                $response['nexResponse'] = $this->showSecond($request);
                break;
            case '3':
                $response['nextPart'] = '4';
                $response['nexResponse'] = $this->showRest($request, 'newUsers');
                break;
            case '4':
                $response['nextPart'] = '5';
                $response['nexResponse'] = $this->showRest($request, 'newUsersWithPicture');
                break;
            case '5':
                $response['nextPart'] = '6';
                $response['nexResponse'] = $this->showRest($request, 'newRatings');
                break;
            case '6':
                $response['nextPart'] = '7';
                $response['nexResponse'] = $this->showRest($request, 'newMessages');
                break;
            case '7':
                // $response['nextPart'] = '8';
                $response['nexResponse'] = $this->showRest($request, 'newComments');
                break;
        }

        return $response;
    }

    public function showFirst(Request $request)
    {

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
            'creditsForSalesTotal',
            'creditsForOrdersTotal',
            'salesCount',
            'soldProductsCount',
        ];

        $this->rowsResponse = [
            'groupBy' => $request->groupBy,
            'ranges' => [],
        ];

        foreach ($ordersReportKeys as $key) {
            $this->rowsResponse[$key] = [];
        }

        foreach ($ordersReport as $range) {
            $this->setRangeDates($this->rowsResponse, $range);

            foreach ($ordersReportKeys as $key) {
                $this->rowsResponse[$key][$range->date_range] = $range->$key;
            }
        }


        return $this->rowsResponse;
    }

    public function showSecond(Request $request)
    {

        $runningCredits = $this->getInitialCredits($request);
        $creditsReport = $this->getCreditsTransactionsReport($request);
        foreach ($creditsReport as $range) {
            $this->setRangeDates($this->rowsResponse, $range);

            $runningCredits += $range->credits;
            $this->rowsResponse['creditsDebt'][$range->date_range] = $runningCredits;
        }


        $initialProductsData = $this->getInitialProductsData($request);
        $runningProducts = $initialProductsData->productsCount;
        $runningPriceTotal = $initialProductsData->productsPriceTotal;
        $productsReport = $this->getProductsReport($request);
        foreach ($productsReport as $range) {
            $this->setRangeDates($this->rowsResponse, $range);

            $runningProducts += $range->newProductsCount;
            $runningPriceTotal += $range->newProductsPriceTotal;
            $this->rowsResponse['newProductsCount'][$range->date_range] = $range->newProductsCount;
            $this->rowsResponse['newProductsAveragePrice'][$range->date_range] = (int) ($range->newProductsPriceTotal / $range->newProductsCount);
            $this->rowsResponse['productsAveragePrice'][$range->date_range] = (int) ($runningPriceTotal / $runningProducts);
        }

        return $this->rowsResponse;
    }

    public function showRest(Request $request, $type = 'all')
    {

        $newDataReport = $this->getNewDataReport($request, $type);
        foreach ($newDataReport as $key => $dataReport) {
            foreach ($dataReport as $range) {
                $this->setRangeDates($this->rowsResponse, $range);

                $this->rowsResponse[$key][$range->date_range] = $range->count;
            }
        }

        // Sort ranges. Api consumers can use this array as the starting point.
        // Or can sort themselves.
        ksort($this->rowsResponse['ranges']);

        return $this->rowsResponse;
    }
}
