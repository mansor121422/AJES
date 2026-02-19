<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Authentication
$routes->post('auth/login', 'Auth::login');
$routes->get('auth/logout', 'Auth::logout');
$routes->get('auth/forgot-password', 'Auth::showForgotPassword');
$routes->post('auth/forgot-password', 'Auth::sendResetLink');
$routes->get('auth/reset-password/(:segment)', 'Auth::showResetForm/$1');
$routes->post('auth/reset-password/(:segment)', 'Auth::resetPassword/$1');

// Dashboards (protected with filters)
$routes->group('dashboard', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Dashboard::index');
    $routes->get('admin', 'Dashboard::admin', ['filter' => 'role:ADMIN']);
    $routes->get('principal', 'Dashboard::principal', ['filter' => 'role:PRINCIPAL']);
    $routes->get('announcer', 'Dashboard::announcer', ['filter' => 'role:ANNOUNCER']);
    $routes->get('teacher', 'Dashboard::teacher', ['filter' => 'role:TEACHER']);
    $routes->get('guidance', 'Dashboard::guidance', ['filter' => 'role:GUIDANCE']);
    $routes->get('student', 'Dashboard::student', ['filter' => 'role:STUDENT']);
});

// Records module (restricted to GUIDANCE and ADMIN for now)
$routes->group('records', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Records::index', ['filter' => 'role:GUIDANCE,ADMIN']);
    $routes->get('create', 'Records::create', ['filter' => 'role:GUIDANCE,ADMIN']);
    $routes->post('store', 'Records::store', ['filter' => 'role:GUIDANCE,ADMIN']);
    $routes->get('edit/(:num)', 'Records::edit/$1', ['filter' => 'role:GUIDANCE,ADMIN']);
    $routes->post('update/(:num)', 'Records::update/$1', ['filter' => 'role:GUIDANCE,ADMIN']);
    $routes->get('delete/(:num)', 'Records::delete/$1', ['filter' => 'role:GUIDANCE,ADMIN']);
});
