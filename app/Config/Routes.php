<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Authentication
$routes->get('auth/login', 'Home::index'); // Show login page
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

// Admin: Sections CRUD and invite teachers
$routes->group('admin', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('sections', 'Sections::index', ['filter' => 'role:ADMIN']);
    $routes->get('sections/create', 'Sections::create', ['filter' => 'role:ADMIN']);
    $routes->post('sections/store', 'Sections::store', ['filter' => 'role:ADMIN']);
    $routes->get('sections/edit/(:num)', 'Sections::edit/$1', ['filter' => 'role:ADMIN']);
    $routes->post('sections/update/(:num)', 'Sections::update/$1', ['filter' => 'role:ADMIN']);
    $routes->get('sections/delete/(:num)', 'Sections::delete/$1', ['filter' => 'role:ADMIN']);
    $routes->get('sections/(:num)/teachers', 'Sections::sectionTeachers/$1', ['filter' => 'role:ADMIN']);
    $routes->post('sections/invite', 'Sections::invite', ['filter' => 'role:ADMIN']);
});

// Teacher: accept section invite, my sections, add students
$routes->group('teacher', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('sections', 'TeacherSections::index', ['filter' => 'role:TEACHER']);
    $routes->get('sections/accept/(:num)', 'TeacherSections::accept/$1', ['filter' => 'role:TEACHER']);
    $routes->get('sections/(:num)/students', 'TeacherSections::sectionStudents/$1', ['filter' => 'role:TEACHER']);
    $routes->post('sections/add-student', 'TeacherSections::addStudent', ['filter' => 'role:TEACHER']);
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
