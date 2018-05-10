<?php

namespace App\Http\Controllers;

use App\RatingArchive;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class RatingArchiveController extends Controller
{
    protected $modelClass = RatingArchive::class;
    public static $allowedWhereIn = ['buyer_rating', 'seller_id', 'buyer_id'];

    protected function alterValidateData($data, Model $rating = null)
    {
        return $data;
    }

    protected function validationRules(array $data, ?Model $rating)
    {
        return [
            'seller_id' => 'integer|required_with:buyer_id|exists:users,id',
            'buyer_id' => 'integer|required_with:seller_id|exists:users,id',
            'buyer_rating' => 'required_with:buyer_comment|integer|between:-1,1',
            'buyer_comment' => 'required_with:buyer_rating|string|max:10000',
        ];
    }
}
