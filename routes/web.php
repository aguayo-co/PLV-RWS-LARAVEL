<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Helper for domain validations:
// Set an ENV variable named DOMAIN_VALIDATION.
// Each path should be separated by 3 underscores,
// and if the response needs some content,
// put it after 3 dashes for each path
//   Example: pathA___pathB---content___pathC---content C
//
// example:
// random_0123456789.html---some-content-that-should-be-in-the-file___random_0123456789

$validations = env('DOMAIN_VALIDATION');
if ($validations) {
    $lines = explode('___', $validations);
    foreach ($lines as $line) {
        $data = explode('---', $line);
        Route::get($data[0], function () use ($data) {
            return array_get($data, 1);
        });
    }
}
