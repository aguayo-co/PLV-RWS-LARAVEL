<?php

namespace App\Http\Controllers\Auth;

use App\CreditsTransaction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Product\UserDelete;
use App\Http\Controllers\User\UserSearch;
use App\Notifications\AccountClosed;
use App\Notifications\BankAccountChanged;
use App\Notifications\EmailChanged;
use App\Notifications\Welcome;
use App\Product;
use App\Sale;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Passport\Token;

class UserController extends Controller
{
    use UserDelete;
    use UserSearch;
    use UserVisibility;
    protected $modelClass = User::class;

    public static $allowedWhereIn = ['id', 'email'];
    public static $allowedWhereHas = [
        'group_ids' => 'groups',
        'roles_ids' => 'roles',
        'roles_names' => 'roles,name',
    ];

    public static $searchIn = ['first_name', 'last_name'];

    public function __construct()
    {
        parent::__construct();
        $this->middleware(self::class . '::validateCanBeDeleted')->only(['delete', 'ownerDelete']);
    }

    protected function alterValidateData($data, Model $user = null)
    {
        # ID needed to validate it is not self-referenced.
        $data['id'] = $user ? $user->id : false;
        if (array_key_exists('email', $data)) {
            $data['exists'] = $data['email'];
        }
        return $data;
    }

    protected function validationRules(array $data, ?Model $user)
    {
        $required = !$user ? 'required|' : '';
        $ignore = $user ? $user->id : 'NULL';
        return [
            # Por requerimiento de front, el error de correo existente debe ser enviado por aparte.
            'exists' => 'unique:users,email,' . $ignore . ',id,deleted_at,NULL',
            'email' => $required . 'string|email',
            'password' => $required . 'string|min:6',
            'first_name' => $required . 'string',
            'last_name' => $required . 'string',
            'phone' => 'string',
            'about' => 'string|max:10000',
            'picture' => 'image',
            'cover' => 'image',
            'vacation_mode' => 'boolean',
            'bank_account' => 'nullable|array',
            'favorite_address_id' => [
                'integer',
                Rule::exists('addresses', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user ? $user->id : null);
                }),
            ],
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'integer|exists:groups,id',
            'shipping_method_ids' => 'array',
            'shipping_method_ids.*' => 'integer|exists:shipping_methods,id',
            'following_add' => 'array',
            'following_add.*' => 'integer|exists:users,id|different:id',
            'following_remove' => 'array',
            'following_remove.*' => 'integer|exists:users,id',
            'favorites_add' => 'array',
            'favorites_add.*' => 'integer|exists:products,id',
            'favorites_remove' => 'array',
            'favorites_remove.*' => 'integer|exists:products,id',
        ];
    }

    protected function validationMessages()
    {
        return [
            'exists.unique' => __('validation.email.exists'),
            'following_add.*.different' => __('validation.different.self'),
        ];
    }

    /**
     * Middleware that validates a user can be deleted.
     */
    public static function validateCanBeDeleted($request, $next)
    {
        $user = $request->route()->parameters['user_scoped'];

        // Can not delete user if has Sales that have not been completed or canceled.
        $pendingSales = $user->sales()->where('status', '>=', Sale::STATUS_PAYMENT)
            ->where('status', '<', Sale::STATUS_COMPLETED)->count();
        if ($pendingSales) {
            abort(Response::HTTP_FORBIDDEN, __('prilov.users.hasPendingSales'));
        }

        // Can not delete user if has Orders that have not been completed or canceled.
        $pendingOrders = $user->orders()->whereHas('sales', function ($query) use ($user) {
            $query->where('status', '>=', Sale::STATUS_PAYMENT)
            ->where('status', '<', Sale::STATUS_COMPLETED);
        })->count();
        if ($pendingOrders) {
            abort(Response::HTTP_FORBIDDEN, __('prilov.users.hasPendingOrders'));
        }

        // Can not delete user if has Credits (positive or negative).
        if ($user->credits) {
            abort(Response::HTTP_FORBIDDEN, __('prilov.users.hasCredits'));
        }

        // Can not delete user if has pending transfers.
        $pendingTransfers = $user->creditsTransactions()
            ->where('transfer_status', CreditsTransaction::STATUS_PENDING)->count();
        if ($pendingTransfers) {
            abort(Response::HTTP_FORBIDDEN, __('prilov.users.hasPendingTransfers'));
        }

        return $next($request);
    }

    /**
     * Apply custom scopes.
     */
    protected function alterIndexQuery()
    {
        return function ($query) {
            $orderBy = explode(',', request()->query('orderby'));

            if (in_array('group_id', $orderBy)) {
                $query = $query->OrderedByGroup('asc');
            }
            if (in_array('-group_id', $orderBy)) {
                $query = $query->OrderedByGroup('desc');
            }

            if (in_array('latest_product', $orderBy)) {
                $query = $query->OrderedByLatestProduct('asc');
            }
            if (in_array('-latest_product', $orderBy)) {
                $query = $query->OrderedByLatestProduct('desc');
            }

            $filters = request()->query('filter');
            if (array_get($filters, 'with_products')) {
                $query = $query->whereHas('products', function ($subQuery) {
                    $subQuery->whereBetween('status', [Product::STATUS_APPROVED, Product::STATUS_AVAILABLE]);
                });
            }

            $user = auth()->user();
            if (array_get($filters, 'with_trashed') && $user && $user->hasRole('admin')) {
                $query = $query->withTrashed();
            }

            return $query->withPurchasedProductsCount()
                ->withCredits();
        };
    }

    protected function processFollowing(Request $request, Model $user)
    {
        if ($request->following_add) {
            $user->following()->syncWithoutDetaching($request->following_add);
        }
        if ($request->following_remove) {
            $user->following()->detach($request->following_remove);
        }
    }

    protected function processFavorites(Request $request, Model $user)
    {
        if ($request->favorites_add) {
            $user->favorites()->syncWithoutDetaching($request->favorites_add);
        }
        if ($request->favorites_remove) {
            $user->favorites()->detach($request->favorites_remove);
        }
    }

    protected function processVacationMode(Request $request, Model $user)
    {
        $vacationMode = $request->vacation_mode;
        if ($vacationMode === null) {
            return;
        }

        switch ($vacationMode) {
            case true:
                $this->setProductsToVacationMode($user);
                break;

            case false:
                $this->removeProductsFromVacationMode($user);
                break;
        }
    }

    /**
     * Set the given user's available products status to ON_VACATION.
     */
    protected function setProductsToVacationMode(Model $user)
    {
        $products = $user->products()
            ->whereBetween('status', [Product::STATUS_APPROVED, Product::STATUS_AVAILABLE])
            ->get();
        foreach ($products as $product) {
            $product->status = Product::STATUS_ON_VACATION;
            $product->save();
        }
    }

    /**
     * Remove the given user's products status from ON_VACATION to AVAILABLE.
     */
    protected function removeProductsFromVacationMode(Model $user)
    {
        $products = $user->products()->where('status', Product::STATUS_ON_VACATION)->get();
        foreach ($products as $product) {
            $product->status = Product::STATUS_AVAILABLE;
            $product->save();
        }
    }

    /**
     * Reset all tokens after password change.
     */
    public function postUpdate(Request $request, Model $user)
    {
        $apiToken = null;
        if ($request->password) {
            Token::destroy($user->tokens->pluck('id')->all());
            $apiToken = $user->createToken('PrilovChangePassword')->accessToken;
        }

        $this->processFollowing($request, $user);
        $this->processFavorites($request, $user);

        $this->processVacationMode($request, $user);

        if (array_has($user->getChanges(), 'email')) {
            $user->notify(new EmailChanged);
        }

        if (array_has($user->getChanges(), 'bank_account')) {
            $user->notify(new BankAccountChanged);
        }

        $user = User::WithPurchasedProductsCount()
                ->withCredits()->findOrFail($user->id);

        // Last, set api_token so it gets sent with the response.
        if ($apiToken) {
            $user->api_token = $apiToken;
        }

        return $user;
    }

    public function postStore(Request $request, Model $user)
    {
        event(new Registered($user));

        $user = User::WithPurchasedProductsCount()
            ->withCredits()->findOrFail($user->id);

        auth()->setUser($user);

        $user->notify(new Welcome);
        $user->api_token = $user->createToken('PrilovRegister')->accessToken;
        return $user;
    }

    public function delete(Request $request, Model $user)
    {
        $response = null;

        DB::transaction(function () use ($request, $user, $response) {
            $this->usersCleanup(collect([$user]));
            $response = parent::delete($request, $user);
        });

        // We still have the old email in this object.
        $user->notify(new AccountClosed);

        return $response;
    }

    public function index(Request $request)
    {
        // Quick email existence validation.
        $email = $request->query('email');
        if ($email) {
            if (User::where('email', $email)->count()) {
                return;
            }
            throw (new ModelNotFoundException)->setModel(User::class);
        }

        return parent::index($request);
    }
}
