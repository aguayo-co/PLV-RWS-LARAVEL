<?php

/**
 * Regex taken from Str::slug.
 */
const SLUG_REGEX = '[-\pL\pN\s]+';
const ID_REGEX = '[0-9]+';

function expand_names($model)
{
    return [
        'snakes' => snake_case(str_plural($model)),
        'snake' => snake_case($model),
    ];
}

function create_get_routes($model, $regex, $snakes, $snake)
{
    Route::get($snakes, $model . 'Controller@index')->name($snakes);
    Route::get($snakes . '/{' . $snake . '}', $model. 'Controller@show')->name($snake . '.get')->where($snake, $regex);
}

function create_create_routes($model, $snakes, $snake)
{
    Route::post($snakes, $model . 'Controller@store')->name($snake . '.create');
}

function create_ud_routes($model, $regex, $snakes, $snake)
{
    Route::patch($snakes . '/{' . $snake . '}', $model . 'Controller@update')
        ->name($snake . '.update')->where($snake, $regex);
    Route::delete($snakes . '/{' . $snake . '}', $model . 'Controller@delete')
        ->name($snake . '.delete')->where($snake, $regex);
}

/**
 * Create CRUD routes that have:
 * - Public GET access.
 * - Protected POST, PATCH and DELETE access.
 */
function create_crud_routes($model, $regex)
{
    extract(expand_names($model));

    create_get_routes($model, $regex, $snakes, $snake);
    Route::group(['middleware' => ['auth:api']], function () use ($model, $regex, $snakes, $snake) {
        create_create_routes($model, $snakes, $snake);
        create_ud_routes($model, $regex, $snakes, $snake);
    });
}

/**
 * Create CRUD routes that have:
 * - Protected GET.
 * - Protected POST, PATCH and DELETE access.
 */
function create_protected_crud_routes($model, $regex)
{
    extract(expand_names($model));

    Route::group(['middleware' => ['auth:api']], function () use ($model, $regex, $snakes, $snake) {
        create_get_routes($model, $regex, $snakes, $snake);
        create_create_routes($model, $snakes, $snake);
        create_ud_routes($model, $regex, $snakes, $snake);
    });
}

/**
 * Create RUD routes that have:
 * - Protected GET.
 * - Protected PATCH and DELETE access.
 */
function create_protected_rud_routes($model, $regex)
{
    extract(expand_names($model));

    Route::group(['middleware' => ['auth:api']], function () use ($model, $regex, $snakes, $snake) {
        create_get_routes($model, $regex, $snakes, $snake);
        create_ud_routes($model, $regex, $snakes, $snake);
    });
}
