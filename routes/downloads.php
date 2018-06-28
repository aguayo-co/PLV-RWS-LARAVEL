<?php

use Illuminate\Http\Request;

Route::name('downloads.')->middleware('signed')->group(function () {
    Route::get('/payrolls/{payroll}/download', 'PayrollController@download')->name('payroll');
});
