<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\PasswordChanged;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Laravel\Passport\Token;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    protected function validationRules(array $data, ?Model $model)
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6',
        ];
    }


    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $email
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request, $email)
    {
        $user = $this->getUser($request, $email);

        $this->resetPassword($user, $request->password);

        return $user;
    }

    protected function getUser(Request $request, $email)
    {
        $request->merge(['email' => $email]);
        $this->validate($request->all());

        $user = $this->broker()->getUser(['email' => $email]);
        if (is_null($user)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, __(Password::INVALID_USER));
        }

        if (!$this->broker()->tokenExists($user, $request->token)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, __(Password::INVALID_TOKEN));
        }

        return $user;
    }

    protected function resetPassword($user, $password)
    {
        $user->password = $password;
        $user->save();
        $this->broker()->deleteToken($user);
        $user->api_token = $user->createToken('PrilovResetPassword')->accessToken;
        Token::destroy($user->tokens->pluck('id')->all());
        event(new PasswordReset($user));
        $user->notify(new PasswordChanged);
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }
}
