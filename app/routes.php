<?php

/*
|--------------------------------------------------------------------------
| Checks
|--------------------------------------------------------------------------
*/

App::before(function($request)
{
    // HTTPS
    if(!Request::secure()) {
        return Redirect::secure(Request::path());
    }
});

/*
|--------------------------------------------------------------------------
| Site Routes
|--------------------------------------------------------------------------
*/

// homepage
Route::get('/', 'HomeController@homePage');

// sign in
Route::get('/sign-in', 'HomeController@signIn');
Route::post('/sign-in', 'HomeController@doSignIn');

// sign out
Route::get('/sign-out', 'HomeController@signOut');

// create account
Route::get('/create-account', 'HomeController@createAccount');
Route::post('/create-account', 'HomeController@doCreateAccount');

// my account
Route::get('/my-account', 'HomeController@myAccount');

// jobs
Route::post('/jobs/rate-job', 'JobsController@rateJob');
Route::post('/jobs/report-comment', 'JobsController@reportComment');
Route::post('/jobs/rate-comment', 'JobsController@rateComment');
Route::get('/jobs/sort-by/{mode}', 'JobsController@sortJobs');

Route::get('/jobs/clear-search', 'JobsController@clearSearch');

Route::get('/jobs/{page?}', 'JobsController@jobsList')->where('page', '[0-9]+');
Route::post('/jobs', 'JobsController@jobsList');

Route::get('/jobs/back-to-list', 'JobsController@backToJobsList');

Route::get('/jobs/{job}', 'JobsController@viewJob');
Route::post('/jobs/{job}', 'JobsController@postOnJob');


// social media accounts connection
Route::get('connect/linkedin', 'HomeController@socialLinkedIn');
Route::get('connect/facebook', 'HomeController@socialFacebook');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/