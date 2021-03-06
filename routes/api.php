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

Route::post("contacto","Api\ContactoController@store")->name('contactar');
Route::post("contacto_demo","Api\ContactoController@store_demo")->name('contactar_demo');
Route::get("contacto/all","Api\ContactoController@index")->name('todos');
