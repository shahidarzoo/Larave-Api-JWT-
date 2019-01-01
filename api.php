<?php

use Illuminate\Http\Request;

/* Register and Login Routes */
Route::post('password/reset', 'Auth/ResetPasswordController@reset');
Route::group(['namespace' => 'Api'], function()
{
	Route::post('register', 'AuthController@register');
	Route::post('login', 'AuthController@authenticate');
	Route::post('/login-google', 'AuthController@loginWithGoogleAndFacebook');
	Route::post('/login-facebook', 'AuthController@loginWithGoogleAndFacebook');
	Route::post('/forget-password', 'AuthController@forget_password');
});


Route::group(['middleware' => 'jwt.verify', 'namespace' => 'Api'], function() {
	/* Logout, refresh and invalid*/
	Route::post('logout', 'AuthController@logout');
	Route::post('password-update', 'AuthController@update_password');
	Route::post('refresh', 'AuthController@refresh');
    Route::post('invalid', 'AuthController@invalid');

	/* Get Current Logedin User */
	Route::get('user_info', 'AuthController@getAuthenticatedUser');

	/* Subscription Newlatter */
	Route::get('subscribe', 'ContactController@subscribe');

	/* Contact Information */
	Route::group(['prefix' => 'contact'], function(){
		Route::get('/', 'ContactController@index');
		Route::get('edit/{id}', 'ContactController@edit');
		Route::post('update', 'ContactController@update');
	});
	
	/* Billing Address */
	Route::group(['prefix' => 'billing'], function(){
		Route::get('/', 'BillingController@index');
		Route::post('store', 'BillingController@store');
		Route::get('edit/{id?}', 'BillingController@edit');
		Route::post('update/', 'BillingController@update');
	});

	/* Shipping Address*/
	Route::group(['prefix' => 'delivery'], function(){
		Route::get('/', 'DeliveryInfoController@index');
		Route::post('store', 'DeliveryInfoController@store');
		Route::get('edit/{id?}', 'DeliveryInfoController@edit');
		Route::post('update', 'DeliveryInfoController@update');
	});

	/* Orders Details */
	Route::group(['prefix' => 'orders'], function(){
		Route::get('/', 'OrderController@index');
		Route::get('/verify/{id}', 'OrderController@edit');
		Route::get('/search/{order}', 'OrderController@search');

	});

	/* Bank Slip Details */
	Route::group(['prefix' => 'verify-bank-transfer'], function(){
		Route::get('/{id}', 'OrderController@edit');
		Route::post('/update', 'OrderController@update');
	});

	/* Messages Deatils */
	Route::group(['prefix' => 'messages'], function(){
		Route::get('/', 'MessageController@index');
		Route::get('/show/{id}', 'MessageController@show');
		Route::post('/store', 'MessageController@store');
	});
	
	/* Messages Deatils */
	Route::group(['prefix' => 'shipment-return'], function(){
		Route::get('/', 'ShipmentController@index');
		Route::post('/store', 'ShipmentController@store');
	});

	/* Custom Measurement Details */
	Route::group(['prefix' => 'custom-measurement'], function(){
		Route::get('/', 'CustomMeasurementController@index');
		Route::post('store', 'CustomMeasurementController@store');
		Route::get('edit/{id}', 'CustomMeasurementController@edit');
		Route::post('update', 'CustomMeasurementController@update');
		Route::get('destroy/{id}', 'CustomMeasurementController@destroy');
	});
});