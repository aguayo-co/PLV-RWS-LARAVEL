<?php

namespace App\Http\Controllers;

use App\Rating;
use Illuminate\Database\Eloquent\Model;
use App\Sale;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RatingController extends Controller
{
    protected $modelClass = Rating::class;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(self::class . '::validateUserCanRate')->only(['rate']);
        $this->middleware(self::class . '::validateSaleCanBeRated')->only(['rate']);
    }

    protected static function boot()
    {
        parent::boot();
        // Create a rating for each created sale.
        Sale::created(function ($sale) {
            $rating = new self();
            $rating->id = $sale->id;
            $rating->save();
        });
    }

    /**
     * Middleware that validates permissions to set ratings.
     */
    public static function validateUserCanRate($request, $next)
    {
        $user = auth()->user();
        $rating = $request->rating;

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        $seller = $rating->sale->user;
        if ($request->only(['seller_rating', 'seller_comment']) && !$user->is($seller)) {
            abort(Response::HTTP_FORBIDDEN, 'Only seller or admin can set seller rating.');
        }

        $buyer = $rating->sale->order->user;
        if ($request->only(['buyer_rating', 'buyer_comment']) && !$user->is($buyer)) {
            abort(Response::HTTP_FORBIDDEN, 'Only buyer or admin can set buyer rating.');
        }

        return $next($request);
    }

    /**
     * Middleware that validates that a Sale can be rated.
     */
    public static function validateSaleCanBeRated($request, $next)
    {
        $rating = $request->rating;

        if ($rating->sale->status < Sale::STATUS_PAYED) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Sale not ready to be rated.');
        }

        return $next($request);
    }

    protected function alterValidateData($data, Model $rating = null)
    {
        return $data;
    }

    protected function validationRules(?Model $rating)
    {
        return [
            'seller_rating' => 'required_with:seller_comment|integer|between:-1,1',
            'seller_comment' => 'required_with:seller_rating',
            'buyer_rating' => 'required_with:buyer_comment|integer|between:-1,1',
            'buyer_comment' => 'required_with:buyer_rating',
        ];
    }

    /**
     * Alias for update method, without its middleware.
     */
    public function rate(Request $request, Model $rating)
    {
        return $this->update($request, $rating);
    }
}