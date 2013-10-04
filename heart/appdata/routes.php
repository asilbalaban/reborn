<?php

/*Route::add('/note/{int:id}/{alpha:slug}?', 'Note\Note::index')
			->defaults(array('slug' => 'test-page'))
			->host('http://localhost/rb-dev')
			->method(array('POST', 'PUT'));*/
$adminUrl = \Config::get('app.adminpanel');

// This is default route for Reborn
// Don't delete this.
$defaultModule = \Setting::get('default_module');
if ('pages' == strtolower($defaultModule)) {
	Route::add('/', 'Pages\Pages::index', 'default');
} else {
	Route::add('/', ucfirst($defaultModule).'\\'.ucfirst($defaultModule).'::index', 'default');
}

Route::add('login', 'User\User::login', 'login');
Route::add('register', 'User\User::register', 'register');

// Admin Panel Login, Logout, Dashboard Route
Route::add($adminUrl.'/login', 'Admin\Admin\Admin::login', 'admin_login');
Route::add($adminUrl, 'Admin\Admin\Admin::index', 'admin_dashboard');
Route::add($adminUrl.'/language', 'Admin\Admin\Admin::language', 'admin_language');
