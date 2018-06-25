<?php
Route::get('', 'RatingArchiveController@index')->name('rating_archives');
Route::get('{rating_archive}', 'RatingArchiveController@show')
    ->name('rating_archive.get')->where('rating_archive', ID_REGEX);

# Auth routes.
# Only authenticated requests here.
Route::middleware('auth:api')->group(function () {
    Route::patch('{rating_archive}', 'RatingArchiveController@update')
        ->name('rating.update')->where('rating_archive', ID_REGEX);
    Route::delete('{rating_archive}', 'RatingArchiveController@delete')
        ->name('rating.delete')->where('rating_archive', ID_REGEX);
});
