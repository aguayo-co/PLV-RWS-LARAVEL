<?php
Route::get('', 'RatingController@index')->name('ratings');

# Auth routes.
# Only authenticated requests here.
Route::middleware('auth:api')->group(function () {
    Route::get('{rating}', 'RatingController@show')->name('rating.get')->where('rating', ID_REGEX);
    Route::patch('{rating}', 'RatingController@update')->name('rating.update')->where('rating', ID_REGEX);
    Route::delete('{rating}', 'RatingController@delete')->name('rating.delete')->where('rating', ID_REGEX);
});
