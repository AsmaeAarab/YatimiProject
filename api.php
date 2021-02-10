<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('login', 'Api\AuthController@authenticate');
Route::post('register', 'Api\AuthController@register');
Route::post('reset-pass', 'Api\AuthController@resetpass');
Route::post('update-pass', 'Api\AuthController@updatepass');
Route::get('levels', 'Api\ContentController@getLevels');
Route::get('matieres', 'Api\ContentController@getMatieres');
Route::get('ecoles', 'Api\ContentController@getEcoles');
Route::post('activation', 'Api\AuthController@verify_code');
Route::get('page', 'Api\PageController@getPage');


Route::group([
    'middleware' => ['jwt.auth','JWTauth'],
], function ($router) {
	Route::post('save-device', 'Api\AuthController@saveDevice');

	Route::post('add/exo', 'Api\ExoController@addExo');
	Route::get('exos', 'Api\ExoController@getExos');
	Route::get('exo', 'Api\ExoController@getExo');
	Route::get('call/exo', 'Api\ExoController@getExoByCall');
	Route::get('etudiants', 'Api\ExoController@getEtudiants');

	Route::get('virements', 'Api\ExoController@getVirements');
	
	Route::get('resolved/exos', 'Api\ExoController@getExosResolved');
	Route::post('delete/exo', 'Api\ExoController@deleteExo');
	Route::post('resolved/exo', 'Api\ExoController@resolvedExo');
	Route::post('resend/exo', 'Api\ExoController@resendExo');
	Route::post('inprogress/exo', 'Api\ExoController@inProgressExo');
	Route::post('cancel/call', 'Api\ExoController@cancel_call');
	Route::get('solde/prof', 'Api\ExoController@get_solde_prof');
             
    Route::get('call/exo_inprogress', 'Api\ExoController@getInProgressCall');

	Route::post('save/rate', 'Api\ExoController@saveRate');
	Route::get('rate', 'Api\ExoController@getRate');
	Route::post('contact', 'Api\AuthController@contactUS');
	
	Route::post('edit/profile', 'Api\AuthController@updateprofile');
	Route::post('logout', 'Api\AuthController@logout');
	Route::get('profile', 'Api\AuthController@profile');
    Route::post('ephemeral_keys', 'Api\AuthController@ephemeral_keys');
    Route::post('create_intent', 'Api\AuthController@create_intent');
    
    Route::post('paysafe', 'Api\AuthController@create_payment_paysafe');

	Route::post('edit/picture', 'Api\AuthController@editPicture');
});
