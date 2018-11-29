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

Route::get('/', function () {
    return redirect(route('login'));
});

Auth::routes();

route::group(['middleware' => 'auth'], function(){

	//Route yang berada dalam grup ini hanya bisa dakses oleh user
	//yang memiliki role admin

	route::group(['middleware' => ['role:admin']], function(){
		
		Route::resource('/role', 'RoleController')->except([
			'create', 'show', 'edit', 'update'
		]);
		Route::resource('/users', 'UserController')->except([
			'show'
		]);
		Route::get('/users/roles/{id}', 'UserController@roles')->name('users.roles');
        Route::put('/users/roles/{id}', 'UserController@setRole')->name('users.set_role');
        Route::post('/users/permission', 'UserController@addPermission')->name('users.add_permission');
        Route::get('/users/role-permission', 'UserController@rolePermission')->name('users.roles_permission');
        Route::put('/users/permission/{role}', 'UserController@setRolePermission')->name('users.setRolePermission');


	});

	//Route yang berada dalam grup ini hanya bisa dakses oleh user
	//yang memiliki permission yg telah disebutkan dibawah
	Route::group(['middleware' => ['permission:show products|create products|delete products']], function(){
			
			Route::resource('/kategori', 'CategoryController')->except([
				'create', 'show'
			]);

			Route::resource('/produk', 'ProductController');
	});

	//route group untuk kasir
	Route::group(['middleware' => ['role:kasir']], function(){

	});
	
	//home ditaruh diluar group karena semua jenis user yg lain login bisa mengaksesnya
	Route::get('/home', 'HomeController@index')->name('home');
	
});



