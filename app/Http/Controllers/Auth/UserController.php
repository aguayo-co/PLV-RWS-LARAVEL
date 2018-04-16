<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\AccountClosed;
use App\Notifications\EmailChanged;
use App\Notifications\Welcome;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Passport\Token;
use Spatie\Permission\Exceptions\UnauthorizedException;

class UserController extends Controller
{
    protected $modelClass = User::class;

    public static $allowedWhereIn = ['id', 'email'];
    public static $allowedWhereHas = ['group_ids' => 'groups'];

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
        $ignore = $user ? ',' . $user->id : '';
        return [
            # Por requerimiento de front, el error de correo existente debe ser enviado por aparte.
            'exists' => 'unique:users,email' . $ignore,
            'email' => $required . 'string|email',
            'password' => $required . 'string|min:6',
            'first_name' => $required . 'string',
            'last_name' => $required . 'string',
            'phone' => 'string',
            'about' => 'string|max:10000',
            'picture' => 'image',
            'cover' => 'image',
            'vacation_mode' => 'boolean',
            'favorite_address_id' => [
                'integer',
                Rule::exists('addresses', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user ? $user->id : null);
                }),
            ],
            'group_ids' => 'array',
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
     * Reset all tokens after password change.
     */
    public function postUpdate(Request $request, Model $user)
    {
        $apiToken = null;
        if ($request->password) {
            Token::destroy($user->tokens->pluck('id')->all());
            $apiToken = $user->createToken('PrilovChangePassword')->accessToken;
        }

        if ($request->following_add) {
            $user->following()->syncWithoutDetaching($request->following_add);
        }
        if ($request->following_remove) {
            $user->following()->detach($request->following_remove);
        }

        if ($request->favorites_add) {
            $user->favorites()->syncWithoutDetaching($request->favorites_add);
        }
        if ($request->favorites_remove) {
            $user->favorites()->detach($request->favorites_remove);
        }
        if (array_get($user->getChanges(), 'email')) {
            $user->notify(new EmailChanged);
        }
        $user = $this->setVisibility(parent::postUpdate($request, $user));

        // Last, set api_token so it gets sent with the response.
        // DO NOT do this before parent call, as it refreshes the model
        // and it gets lost.
        if ($apiToken) {
            $user->api_token = $apiToken;
        }

        return $user;
    }

    public function postStore(Request $request, Model $user)
    {
        event(new Registered($user));
        $user = parent::postStore($request, $user);
        $user->notify(new Welcome);
        $user->api_token = $user->createToken('PrilovRegister')->accessToken;
        return $this->setVisibility($user);
    }

    public function show(Request $request, Model $user)
    {
        return $this->setVisibility(parent::show($request, $user));
    }

    protected function setVisibility($data)
    {
        $loggedUser = auth()->user();
        switch (true) {
            // Show email for admins and for same user.
            case $data instanceof Model && $data->is($loggedUser):
            case $loggedUser && $loggedUser->hasRole('admin'):
                $data = $data->makeVisible('email');
        }
        return $data->load(['followers:id', 'following:id'])
            ->makeVisible(['followers_ids', 'following_ids', 'following_count', 'followers_count']);
    }

    public function delete(Request $request, Model $user)
    {
        $deleted = parent::delete($request, $user);
        $user->notify(new AccountClosed);
        return $deleted;
    }

    /**
     * Apply visibility settings to Index query.
     */
    public function index(Request $request)
    {
        // Quick email existence validation.
        if ($email = $request->query('email')) {
            if (User::where('email', $email)->count()) {
                return;
            }
            throw (new ModelNotFoundException)->setModel(User::class);
        }

        if (auth()->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        if (!auth()->user()->hasRole('admin')) {
            throw UnauthorizedException::forRoles(['admin']);
        }

        $pagination = parent::index($request);
        $users = $pagination->getCollection();
        $users = $this->setVisibility($users);
        $pagination->setCollection($users);
        return $pagination;
    }
}
