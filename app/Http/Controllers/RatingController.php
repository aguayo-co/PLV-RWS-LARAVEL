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
        $this->middleware(self::class . '::validateCanBeRated')->only(['rate']);
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
        $rating = $request->route()->parameters['rating'];

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        $seller = $rating->sale->user;
        $buyer = $rating->sale->order->user;

        if ($user->isNot($seller) && $user->isNot($buyer)) {
            abort(Response::HTTP_FORBIDDEN, 'User not allowed to rate this Sale.');
        }

        if ($request->only(['seller_rating', 'seller_comment']) && $user->isNot($seller)) {
            abort(Response::HTTP_FORBIDDEN, 'Only seller or admin can set seller rating.');
        }

        if ($request->only(['buyer_rating', 'buyer_comment']) && $user->isNot($buyer)) {
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

    /**
     * Alias for update method, without its middleware.
     */
    public function rate(Request $request, Model $rating)
    {
        return $this->update($request, $rating);
    }
}
