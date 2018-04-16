<?php

namespace App\Http\Controllers;

use App\Product;
use App\Notifications\NewProduct;
use App\Notifications\ProductApproved;
use App\Notifications\ProductHidden;
use App\Notifications\ProductRejected;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    protected $modelClass = Product::class;

    public static $allowedOrderBy = ['id', 'created_at', 'updated_at', 'price', 'commission'];
    public static $orderByAliases = ['prilov' => 'commission'];

    public static $allowedWhereIn = [
        'id',
        'brand_id',
        'category_id',
        'size_id',
        'condition_id',
        'user_id',
        'status',
    ];
    public static $allowedWhereHas = ['color_ids' => 'colors', 'campaign_ids' => 'campaigns'];
    public static $allowedWhereBetween = ['price'];
    public static $allowedWhereLike = ['slug'];

    public static $searchIn = ['title', 'description'];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('role:seller|admin')->only(['store', 'update']);
        $this->middleware(self::class . '::validateIsPublished')->only(['show']);
    }

    /**
     * Middleware that validates permissions to access unpublished products.
     */
    public static function validateIsPublished($request, $next)
    {
        $product = $request->route()->parameters['product'];
        if ($product->status >= Product::STATUS_APPROVED) {
            return $next($request);
        }

        $user = auth()->user();
        if ($user && $user->hasRole('admin')) {
            return $next($request);
        }

        if ($user && $user->is($product->user)) {
            return $next($request);
        }
        abort(Response::HTTP_FORBIDDEN, 'Product not available for public view.');
    }

    protected function alterValidateData($data, Model $product = null)
    {
        $data['slug'] = str_slug(array_get($data, 'title'));
        return $data;
    }

    protected function validationRules(array $data, ?Model $product)
    {
        $required = !$product ? 'required|' : '';
        return [
            'user_id' => 'integer|exists:users,id',
            'title' => $required . 'string',
            'slug' => 'string',
            'description' => $required . 'string|max:10000',
            'dimensions' => $required . 'string|max:10000',
            'original_price' => $required . 'integer|between:0,9999999',
            'price' => $required . 'integer|between:0,9999999',
            'commission' => $required . 'numeric|between:0,100',
            'brand_id' => $required . 'integer|exists:brands,id',
            # Sólo permite una categoría que tenga padre.
            'category_id' => [
                trim($required, '|'),
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNotNull('parent_id');
                }),
            ],
            # Sólo permite una talla que tenga padre.
            'size_id' => [
                trim($required, '|'),
                'integer',
                Rule::exists('sizes', 'id')->where(function ($query) {
                    $query->whereNotNull('parent_id');
                }),
            ],
            'color_ids' => $required . 'array|max:2',
            'color_ids.*' => 'integer|exists:colors,id',
            'campaign_ids' => 'array',
            'campaign_ids.*' => 'integer|exists:campaigns,id',
            'condition_id' => $required . 'integer|exists:conditions,id',
            'status' => ['integer', Rule::in(Product::getStatuses())],
            'images' => $required . 'array',
            'images.*' => 'image',
            'delete_images' => 'array',
            'delete_images.*' => 'string',
        ];
    }

    protected function validationMessages()
    {
        return ['category_id.exists' => __('validation.not_in')];
        return ['size_id.exists' => __('validation.not_in')];
    }

    protected function validate(array $data, Model $product = null)
    {
        parent::validate($data, $product);

        $status = array_get($data, 'status');
        if ($status) {
            $this->validateStatus($product, $status);
        }
    }

    protected function validateStatus($product, $status)
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return;
        }

        if (!in_array($status, [Product::STATUS_AVAILABLE, Product::STATUS_UNAVAILABLE])) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Only an admin can set the given status.'
            );
        }

        if (!$product || !$product->editable) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Only admin can change status.'
            );
        }
    }

    protected function alterFillData($data, Model $product = null)
    {
        if (!$product && !array_get($data, 'user_id')) {
            $user = auth()->user();
            $data['user_id'] = $user->id;
        }

        if (!$product) {
            $data['status'] = Product::STATUS_UNPUBLISHED;
        }

        return $data;
    }

    /**
     * Filter unpublished products on collections.
     */
    protected function alterIndexQuery()
    {
        $user = auth()->user();
        if ($user && $user->hasRole('admin')) {
            return;
        }

        return function ($query) use ($user) {
            $query = $query->where(function ($query) use ($user) {
                $query = $query->where('status', '>=', Product::STATUS_APPROVED);
                if ($user) {
                    $query = $query->orWhere('user_id', $user->id);
                }
            });
            return $query;
        };
    }

    public function postStore(Request $request, Model $product)
    {
        $product = parent::postStore($request, $product);
        $product->user->notify(new NewProduct(['product' => $product]));
        return $product;
    }

    public function postUpdate(Request $request, Model $product)
    {
        $statusChanged = array_get($product->getChanges(), 'status');
        $product = parent::postUpdate($request, $product);

        switch ($statusChanged) {
            case Product::STATUS_AVAILABLE:
                $product->user->notify(new ProductApproved(['product' => $product]));
                break;

            case Product::STATUS_HIDDEN:
                $product->user->notify(new ProductHidden(['product' => $product]));
                break;

            case Product::STATUS_REJECTED:
                $product->user->notify(new ProductRejected(['product' => $product]));
        }

        return $product;
    }
}
