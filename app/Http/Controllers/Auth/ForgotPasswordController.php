<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | token validation.
    |
    */

    protected function validationRules(array $data, ?Model $model)
    {
        $rules = ['email' => 'required|email'];
        if ($model) {
            $rules['token'] = 'required';
        }
        return $rules;
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $email
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request, $email)
    {
        $request->merge(['email' => $email]);
        $this->validate($request->all());

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink(
            $request->only('email')
        );

        return $response === Password::RESET_LINK_SENT
                    ? $this->sendResponse($response)
                    : abort(Response::HTTP_NOT_FOUND, __($response));
    }

    /**
     * Validate a reset token for the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $email
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateResetToken(Request $request, $email)
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

        return $this->sendResponse('passwords.token.valid');
    }

    /**
     * Get the response for a successful password reset link.
     *
     * @param  string  $response
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendResponse($response)
    {
        return Response(['message' => __($response)]);
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
