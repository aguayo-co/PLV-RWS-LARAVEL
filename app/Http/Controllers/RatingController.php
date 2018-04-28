<?php

namespace App\Http\Controllers;

use App\Rating;
use App\Sale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Ratings are created automatically, there is no POST method.
 * They share the primary key with Sale.
 * Creation is handled in the RouteServiceProvider.
 */
class RatingController extends Controller
{
    protected $modelClass = Rating::class;
    # sale_id is the primary key, no id column.
    public static $allowedWhereIn = ['sale_id'];
    public static $allowedWhereHas = ['sellers_ids' => 'sale,user_id', 'buyers_ids' => 'sale.order,user_id'];
    public static $allowedWhereBetween = ['status'];

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin')->only('show');
        $this->middleware(self::class . '::validateUserCanRate')->only('update');
        $this->middleware(self::class . '::validateCanBeRated')->only('update');
    }

    /**
     * Middleware that validates permissions to set ratings.
     */
    public static function validateUserCanRate($request, $next)
    {
        $user = auth()->user();
        $rating = $request->route()->parameters['rating'];

        $seller = $rating->sale->user;
        $buyer = $rating->sale->order->user;

        if ($request->only(['seller_rating', 'seller_comment']) && $user->is($buyer)) {
            abort(Response::HTTP_FORBIDDEN, 'Only seller or admin can set seller rating.');
        }

        if ($request->only(['buyer_rating', 'buyer_comment']) && $user->is($seller)) {
            abort(Response::HTTP_FORBIDDEN, 'Only buyer or admin can set buyer rating.');
        }

        return $next($request);
    }

    /**
     * Middleware that validates that a Sale can be rated.
     */
    public static function validateCanBeRated($request, $next)
    {
        $rating = $request->route()->parameters['rating'];

        if ($rating->sale->status < Sale::STATUS_PAYED) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Sale not ready to be rated.');
        }

        if ($rating->status === Rating::STATUS_PUBLISHED && !auth()->user()->hasRole('admin')) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Can not modify a published rating.');
        }

        return $next($request);
    }

    protected function alterValidateData($data, Model $rating = null)
    {
        return $data;
    }

    protected function validationRules(array $data, ?Model $rating)
    {
        return [
            'seller_rating' => 'required_with:seller_comment|integer|between:-1,1',
            'seller_comment' => 'required_with:seller_rating|string|max:10000',
            'buyer_rating' => 'required_with:buyer_comment|integer|between:-1,1',
            'buyer_comment' => 'required_with:buyer_rating|string|max:10000',
        ];
    }

    protected function setVisibility(Collection $collection)
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return;
        }

        // For unpublished ratings, hide rating and comment that were
        // not set by the current user.
        $unpublished = $collection->where('status', Rating::STATUS_UNPUBLISHED);
        $unpublished->load(['sale:id,user_id,order_id', 'sale.order:id,user_id'])->makeHidden(['sale']);
        $unpublished->each(function ($rating) use ($user) {
            // Rating not published, hide ratings not made by current user.
            if ($rating->sale->user_id !== $user->id) {
                $rating->makeHidden(['seller_rating', 'seller_comment']);
            }
            if ($rating->sale->order->user_id !== $user->id) {
                $rating->makeHidden(['buyer_rating', 'buyer_comment']);
            }
        });
    }
}
