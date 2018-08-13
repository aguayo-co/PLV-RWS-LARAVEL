<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Product\ProductDelete;
use App\Http\Controllers\Product\ProductSearch;
use App\Notifications\NewProduct;
use App\Notifications\ProductApproved;
use App\Notifications\ProductDeleted;
use App\Notifications\ProductDeletedAdmin;
use App\Notifications\ProductHidden;
use App\Notifications\ProductRejected;
use App\Product;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use ProductDelete;
    use ProductSearch;

    protected $modelClass = Product::class;

    public static $allowedOrderBy = ['id', 'created_at', 'updated_at', 'price', 'commission'];
    public static $orderByAliases = ['prilov' => 'commission'];

    public static $allowedWhereIn = [
        'id',
        'brand_id',
        'category_id',
        'condition_id',
        'size_id',
        'user_id',
    ];
    public static $allowedWhereHas = [
        'color_ids' => 'colors',
        'campaign_ids' => 'campaigns',
        'users_emails' => 'user,email',
        'users_groups_ids' => 'user.groups',
    ];
    public static $allowedWhereBetween = ['price', 'status'];
    public static $allowedWhereLike = ['slug'];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('role:seller|admin')->only(['store']);
        $this->middleware(self::class . '::validateIsPublished')->only(['show']);
        $this->middleware(self::class . '::validateCanBeDeleted')->only(['delete', 'ownerDelete']);
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

    /**
     * Middleware that validates permissions to delete a product.
     */
    public static function validateCanBeDeleted($request, $next)
    {
        $product = $request->route()->parameters['product'];
        if ($product->status < Product::STATUS_PAYMENT) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN, 'Product can not be deleted.');
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
            'dimensions' => 'nullable|string|max:10000',
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
                'nullable',
                'integer',
                Rule::exists('sizes', 'id')->where(function ($query) {
                    $query->whereNotNull('parent_id');
                }),
            ],
            'color_ids' => $required . 'array|max:2',
            'color_ids.*' => 'integer|exists:colors,id',
            'campaign_ids' => 'nullable|array',
            'campaign_ids.*' => 'integer|exists:campaigns,id',
            'condition_id' => $required . 'integer|exists:conditions,id',
            'status' => ['integer', Rule::in(Product::getStatuses())],
            'images' => $required . 'array',
            'images.*' => 'image',
            'image_instagram' => 'nullable|image',
            'images_remove' => 'array',
            'images_remove.*' => 'string',
            'admin_notes' => 'string|max:10000',
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

        $adminNotes = array_get($data, 'admin_notes');
        if ($adminNotes) {
            $this->validateAdminNotes();
        }
    }

    protected function validateStatus($product, $status)
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return;
        }

        if (!$product) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Only admin can set status for new products.'
            );
        }

        // Statuses that non admin users can set.
        if (!in_array($status, [Product::STATUS_AVAILABLE, Product::STATUS_UNAVAILABLE, Product::STATUS_CHANGED_FOR_APPROVAL])) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Only an admin can set the given status.'
            );
        }

        // A product can be set to revision if it is currently rejected.
        if ((int)$status === Product::STATUS_CHANGED_FOR_APPROVAL && $product->status > Product::STATUS_CHANGED_FOR_APPROVAL) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Only admin can change status.'
            );
        }

        // New products can't have a status set manually by non admins.
        if ((int)$status !== Product::STATUS_CHANGED_FOR_APPROVAL && !$product->editable) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Only admin can change status.'
            );
        }
    }

    protected function validateAdminNotes()
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return;
        }

        abort(
            Response::HTTP_FORBIDDEN,
            'Only admin can add notes.'
        );
    }

    protected function alterFillData($data, Model $product = null)
    {
        if (!$product && !array_get($data, 'user_id')) {
            $user = auth()->user();
            $data['user_id'] = $user->id;
        }

        if (!$product) {
            $seller = User::find($data['user_id']);
            $approvedProductsCount = $seller->products->where('status', '>=', Product::STATUS_APPROVED)->count();
            $data['status'] = $approvedProductsCount > 0 ? Product::STATUS_APPROVED : Product::STATUS_UNPUBLISHED;
        }

        return $data;
    }

    /**
     * Filter unpublished products on collections.
     */
    protected function alterIndexQuery()
    {
        return function ($query) {
            $orderBy = explode(',', request()->query('orderby'));

            if (in_array('image_instagram_date', $orderBy)) {
                $query = $query->orderByImageInstagramDate('asc');
            }
            if (in_array('-image_instagram_date', $orderBy)) {
                $query = $query->orderByImageInstagramDate('desc');
            }

            $user = auth()->user();
            if ($user && $user->hasRole('admin')) {
                return $query;
            }

            // If not admin, filter hidden products.
            $query = $query->where(function ($query) use ($user) {
                $query = $query->where('status', '>=', Product::STATUS_APPROVED);
                // But, allow products owned by the user.
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

        switch ($product->status) {
            case Product::STATUS_UNPUBLISHED:
                $product->user->notify(new NewProduct(['product' => $product]));
                break;
            case Product::STATUS_APPROVED:
                $product->user->notify(new ProductApproved(['product' => $product]));
                break;
        }

        return $product;
    }

    public function postUpdate(Request $request, Model $product)
    {
        $statusChanged = array_get($product->getChanges(), 'status');
        $product = parent::postUpdate($request, $product);

        switch ($statusChanged) {
            case Product::STATUS_APPROVED:
                $product->user->notify(new ProductApproved(['product' => $product]));
                break;

            case Product::STATUS_REJECTED:
                $product->user->notify(new ProductRejected(['product' => $product]));
                break;

            case Product::STATUS_HIDDEN:
                $product->user->notify(new ProductHidden(['product' => $product]));
                break;
        }

        return $product;
    }

    protected function setVisibility(Collection $collection)
    {
        $collection->load([
            'brand',
            'campaigns',
            'category.parent',
            'colors',
            'condition',

            'size.parent',

            'user.followers:id',
            'user.following:id',
            'user.groups',
            'user.shippingMethods',
            'user.ratings',
            'user.ratingArchives',
        ]);

        $loggedUser = auth()->user();
        if ($loggedUser && $loggedUser->hasRole('admin')) {
            // Show admin notes only to admin
            $collection->makeVisible(['admin_notes']);
        }

        $collection->each(function ($product) use ($collection, $loggedUser) {
            if ($loggedUser && $loggedUser->hasRole('admin')) {
                $product->user->makeVisible(['email', 'phone']);
            }

            $product->append([
                'color_ids', 'campaign_ids'
            ]);
            $product->user->makeHidden([
                'followers', 'following', 'ratings', 'ratingArchives'
            ]);
            $product->user->append([
                'following_count',
                'followers_count',
                'ratings_negative_count',
                'ratings_positive_count',
                'ratings_neutral_count',
                'shipping_method_ids',
            ]);
        });
    }

    /**
     * Product deleted by the owner.
     */
    public function ownerDelete(Request $request, Model $product)
    {
        $product->ownerDelete = true;
        $response = parent::ownerDelete($request, $product);
        $product->user->notify(new ProductDeleted(['product' => $product]));
        return $response;
    }

    /**
     * Delete the given product when not sold, and its sales if needed.
     */
    public function delete(Request $request, Model $product)
    {
        $response = null;
        DB::transaction(function () use ($request, $product, $response) {
            $this->productsCleanup([$product]);
            $response = parent::delete($request, $product);
        });

        if (!$product->ownerDelete) {
            $product->user->notify(new ProductDeletedAdmin(['product' => $product]));
        }
        return $response;
    }
}
