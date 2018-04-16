<?php

namespace App\Http\Controllers;

use App\Http\Traits\CanFilter;
use App\Http\Traits\CanOrderBy;
use App\Http\Traits\CanSearch;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs;
    use CanOrderBy;
    use CanFilter;
    use CanSearch;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        /**
         * Only an admin or the owner can update models.
         */
        $this->middleware('owner_or_admin')->only(['update', 'ownerDelete']);

        /**
         * Only admin can assign ownership to a different user.
         */
        $this->middleware('self_or_admin')->only(['update', 'store']);

        /**
         * Only admin can delete a model.
         */
        $this->middleware('role:admin')->only('delete');
    }

    /**
     * Return an array of validations rules to apply to the request data.
     *
     * @return array
     */
    protected function validationRules(array $data, ?Model $model)
    {
        throw new Exception('Not implemented');
    }

    /**
     * Return an array of validation messages to use
     * with the controller validation rules.
     *
     * @return array
     */
    protected function validationMessages()
    {
        return [];
    }

    /**

     * Validate given data.
     *
     * @param  array  $data
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validate(array $data, Model $model = null)
    {
        # This is a hack to ensure we don't pass a string we can't put in the DB.
        if (!$rules = $this->validationRules($data, $model)) {
            return;
        }

        foreach ($rules as &$rule) {
            if (is_string($rule) && strpos($rule, 'string') !== false && strpos($rule, 'max:') === false) {
                $rule = $rule . '|max:' . Schema::getFacadeRoot()::$defaultStringLength;
            }
        }

        Validator::make(
            $data,
            $rules,
            $this->validationMessages()
        )->validate();
    }


    /**
     * Return a Closure that modifies the index query.
     * The closure receives the $query as a parameter.
     *
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        // return function ($query) {
        //     return $query->where('column', 'value')->with(['association']);
        // };
        return;
    }

    /**
     * Process url query parameters and apply the to the query.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  $controllerClass
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyParamsToQuery($request, $query, $controllerClass = null)
    {
        $controllerClass = $controllerClass ?: $this;
        $query = $this->applyFilters($request, $query, $controllerClass);
        $query = $this->applyOrderBy($request, $query, $controllerClass);
        $query = $this->doSearch($request, $query, $controllerClass);
        return $query;
    }

    /**
     * Display all the resources.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $alter = $this->alterIndexQuery();
        $query = call_user_func($this->modelClass . '::when', $alter, $alter);
        $query = $this->applyParamsToQuery($request, $query);
        return $query->Paginate($request->items);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Model $model)
    {
        return $model;
    }

    /**
     * After update actions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function postUpdate(Request $request, Model $model)
    {
        return $model->fresh();
    }

    /**
     * After store actions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function postStore(Request $request, Model $model)
    {
        return $model->fresh();
    }

    /**
     * Alter data before validation.
     *
     * @param  array  $data
     * @return array
     */
    protected function alterValidateData($data, Model $model = null)
    {
        return $data;
    }

    /**
     * Alter data to be passed to fill method.
     *
     * @param  array  $data
     * @return array
     */
    protected function alterFillData($data, Model $model = null)
    {
        return $data;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $this->validate($this->alterValidateData($data));
        $model = DB::transaction(function () use ($data) {
            return call_user_func($this->modelClass . '::create', $this->alterFillData($data));
        });
        $model = $this->postStore($request, $model);
        return $model;
    }

    /**
     * Handle an update request for a model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Model $model)
    {
        $data = $request->all();
        if ($data) {
            $this->validate($this->alterValidateData($data, $model), $model);
            $model = DB::transaction(function () use ($data, $model) {
                // Force timestamp to be updated.
                $model->fill($this->alterFillData($data, $model))->touch();
                return $model;
            });
        }
        $model = $this->postUpdate($request, $model);
        return $model;
    }

    /**
     * Handle a delete request for a model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, Model $model)
    {
        try {
            $model->delete();
        } catch (QueryException $exception) {
            abort(Response::HTTP_CONFLICT, 'Model has related data associated.');
        }
        return ['message' => 'Object deleted'];
    }

    /**
     * Alias for delete method that does not require admin role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Http\JsonResponse
     */
    public function ownerDelete(Request $request, Model $model)
    {
        return $this->delete($request, $model);
    }
}
