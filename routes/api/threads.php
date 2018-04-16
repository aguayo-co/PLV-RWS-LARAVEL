<?php

Route::get('', 'ThreadController@index')->name('threads');
Route::post('', 'ThreadController@store')->name('thread.store');
Route::get('{thread}', 'ThreadController@show')->name('thread.show')->where('thread', ID_REGEX);
Route::delete('{thread}', 'ThreadController@delete')->name('thread.delete')->where('thread', ID_REGEX);
Route::post('{thread}/messages', 'MessageController@store')->name('thread.message.create')->where('thread', ID_REGEX);
Route::delete('{thread}/messages/{message}', 'MessageController@delete')
    ->name('thread.message.delete')->where(['thread' => ID_REGEX, 'message' => ID_REGEX]);
