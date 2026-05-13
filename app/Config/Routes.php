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

// MFA (Two-Factor Authentication)
$routes->get('auth/mfa', 'Auth::showMfa');
$routes->post('auth/mfa/verify', 'Auth::verifyMfa');
$routes->get('auth/mfa/resend', 'Auth::resendMfa');

// JWT API login (stateless token-based auth)
$routes->post('auth/api-login', 'Auth::apiLogin');

// REST API for Android / mobile (token-based; web login unchanged)
$routes->post('api/login', 'Api\Auth::login');
$routes->post('api/logout', 'Api\Auth::logout');
$routes->get('api/announcements', 'Api\Announcements::index', ['filter' => 'auth']);
$routes->post('api/announcements', 'Api\Announcements::store', ['filter' => 'auth']);
$routes->get('api/profile', 'Api\Profile::show', ['filter' => 'auth']);
$routes->post('api/profile', 'Api\Profile::update', ['filter' => 'auth']);
$routes->get('api/chat/users', 'Chat::getChatUsersApi', ['filter' => 'auth']);
$routes->post('api/chat/typing', 'Chat::setTypingApi', ['filter' => 'auth']);
$routes->get('api/chat/typing', 'Chat::getTypingApi', ['filter' => 'auth']);

// Dashboards (protected with filters)
$routes->group('dashboard', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Dashboard::index');
    $routes->get('admin', 'Dashboard::admin', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:dashboard']]);
    $routes->get('principal', 'Dashboard::principal', ['filter' => ['role:PRINCIPAL', 'privilege:dashboard']]);
    $routes->get('vice-principal', 'Dashboard::vicePrincipal', ['filter' => ['role:VICE_PRINCIPAL,HEAD_TEACHER', 'privilege:dashboard']]);
    $routes->get('announcer', 'Dashboard::announcer', ['filter' => ['role:ANNOUNCER', 'privilege:dashboard']]);
    $routes->get('teacher', 'Dashboard::teacher', ['filter' => ['role:TEACHER', 'privilege:dashboard']]);
    $routes->get('guidance', 'Dashboard::guidance', ['filter' => ['role:GUIDANCE', 'privilege:dashboard']]);
    $routes->get('student', 'Dashboard::student', ['filter' => ['role:STUDENT', 'privilege:dashboard']]);
});

// Announcements
$routes->get('announcements', 'Announcements::index', ['filter' => ['auth', 'privilege:announcements']]);
$routes->get('announcements/create', 'Announcements::create', ['filter' => ['auth', 'privilege:announcements']]);
$routes->post('announcements/store', 'Announcements::store', ['filter' => ['auth', 'privilege:announcements']]);
$routes->get('announcements/edit/(:num)', 'Announcements::edit/$1', ['filter' => ['auth', 'privilege:announcements']]);
$routes->post('announcements/update/(:num)', 'Announcements::update/$1', ['filter' => ['auth', 'privilege:announcements']]);
$routes->get('announcements/delete/(:num)', 'Announcements::delete/$1', ['filter' => ['auth', 'privilege:announcements']]);

// Chat (all authenticated roles)
$routes->get('chat', 'Chat::index', ['filter' => 'auth']);
$routes->post('chat/send', 'Chat::send', ['filter' => 'auth']);
$routes->post('chat/unsend', 'Chat::unsend', ['filter' => 'auth']);
$routes->get('chat/messages', 'Chat::getMessages', ['filter' => 'auth']);
$routes->get('chat/media/(:segment)', 'Chat::media/$1', ['filter' => 'auth']);
$routes->get('chatlogs', 'Chat::logs', ['filter' => ['auth', 'role:ADMIN,SUPER_ADMIN,PRINCIPAL,VICE_PRINCIPAL,HEAD_TEACHER', 'privilege:chat_logs']]);

// Notifications (bell – count, list, mark read)
$routes->get('notifications', 'Notifications::index', ['filter' => 'auth']);
$routes->get('notifications/count', 'Notifications::count', ['filter' => 'auth']);
$routes->get('notifications/recent', 'Notifications::recent', ['filter' => 'auth']);
$routes->get('notifications/mark-read/(:num)', 'Notifications::markReadGet/$1', ['filter' => 'auth']);
$routes->post('notifications/mark-read', 'Notifications::markRead', ['filter' => 'auth']);
$routes->get('notifications/mark-all-read', 'Notifications::markAllRead', ['filter' => 'auth']);

// Profile settings (all authenticated users)
$routes->get('profile', 'Profile::index', ['filter' => 'auth']);
$routes->post('profile', 'Profile::update', ['filter' => 'auth']);

// Admin: Sections CRUD and invite teachers
$routes->group('admin', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('sections', 'Sections::index', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:sections']]);
    $routes->get('sections/create', 'Sections::create', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:sections']]);
    $routes->post('sections/store', 'Sections::store', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:sections']]);
    $routes->get('sections/edit/(:num)', 'Sections::edit/$1', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:sections']]);
    $routes->post('sections/update/(:num)', 'Sections::update/$1', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:sections']]);
    $routes->get('sections/delete/(:num)', 'Sections::delete/$1', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:sections']]);
    $routes->get('sections/(:num)/teachers', 'Sections::sectionTeachers/$1', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:sections']]);
    $routes->post('sections/invite', 'Sections::invite', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:sections']]);
    $routes->post('sections/assignment/update/(:num)', 'Sections::updateTeacherAssignment/$1', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:sections']]);
    $routes->get('sections/assignment/delete/(:num)', 'Sections::deleteTeacherAssignment/$1', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:sections']]);
    $routes->get('users', 'Users::index', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:user_management,read']]);
    $routes->get('users/create', 'Users::create', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:user_management,create']]);
    $routes->post('users/store', 'Users::store', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:user_management,create']]);
    $routes->get('users/edit/(:num)', 'Users::edit/$1', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:user_management,update']]);
    $routes->post('users/update/(:num)', 'Users::update/$1', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:user_management,update']]);
    $routes->get('users/delete/(:num)', 'Users::delete/$1', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:user_management,delete']]);
    $routes->get('users/restore/(:num)', 'Users::restore/$1', ['filter' => ['role:ADMIN,SUPER_ADMIN', 'privilege:user_management,update']]);
    // Backward-compatible legacy URL.
    $routes->get('chat-logs', 'Chat::logs', ['filter' => ['role:ADMIN,SUPER_ADMIN,PRINCIPAL,VICE_PRINCIPAL,HEAD_TEACHER', 'privilege:chat_logs']]);
});

// Teacher: accept section invite, my sections, add students
$routes->group('teacher', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('sections', 'TeacherSections::index', ['filter' => ['role:TEACHER', 'privilege:teacher_sections']]);
    $routes->get('sections/accept/(:num)', 'TeacherSections::accept/$1', ['filter' => ['role:TEACHER', 'privilege:teacher_sections']]);
    $routes->get('sections/leave/(:num)', 'TeacherSections::removeAssignment/$1', ['filter' => ['role:TEACHER', 'privilege:teacher_sections']]);
    $routes->get('sections/(:num)/schedule', 'TeacherSections::sectionSchedule/$1', ['filter' => ['role:TEACHER', 'privilege:teacher_sections']]);
    $routes->get('sections/(:num)/students', 'TeacherSections::sectionStudents/$1', ['filter' => ['role:TEACHER', 'privilege:teacher_sections']]);
    $routes->post('sections/add-student', 'TeacherSections::addStudent', ['filter' => ['role:TEACHER', 'privilege:teacher_sections']]);
});

// Records module (restricted to GUIDANCE and ADMIN for now)
$routes->group('records', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Records::index', ['filter' => ['role:GUIDANCE,ADMIN,SUPER_ADMIN,PRINCIPAL,VICE_PRINCIPAL,HEAD_TEACHER', 'privilege:records']]);
    $routes->get('create', 'Records::create', ['filter' => ['role:GUIDANCE,ADMIN,SUPER_ADMIN,PRINCIPAL,VICE_PRINCIPAL,HEAD_TEACHER', 'privilege:records']]);
    $routes->post('store', 'Records::store', ['filter' => ['role:GUIDANCE,ADMIN,SUPER_ADMIN,PRINCIPAL,VICE_PRINCIPAL,HEAD_TEACHER', 'privilege:records']]);
    $routes->get('edit/(:num)', 'Records::edit/$1', ['filter' => ['role:GUIDANCE,ADMIN,SUPER_ADMIN,PRINCIPAL,VICE_PRINCIPAL,HEAD_TEACHER', 'privilege:records']]);
    $routes->post('update/(:num)', 'Records::update/$1', ['filter' => ['role:GUIDANCE,ADMIN,SUPER_ADMIN,PRINCIPAL,VICE_PRINCIPAL,HEAD_TEACHER', 'privilege:records']]);
    $routes->get('delete/(:num)', 'Records::delete/$1', ['filter' => ['role:GUIDANCE,ADMIN,SUPER_ADMIN,PRINCIPAL,VICE_PRINCIPAL,HEAD_TEACHER', 'privilege:records']]);
});

// Technical/system operations (Admin and Super Admin)
// Use "sysadmin" prefix to avoid collision with project "/system" directory.
$routes->group('sysadmin', ['filter' => ['auth', 'role:ADMIN,SUPER_ADMIN']], static function (RouteCollection $routes): void {
    $routes->get('settings', 'SystemAdmin::settings', ['filter' => 'privilege:system_settings']);
    $routes->get('chatbot', 'SystemAdmin::chatbot', ['filter' => 'privilege:chatbot_management']);
    $routes->get('backup', 'SystemAdmin::backup', ['filter' => 'privilege:backup_restore']);
    $routes->get('security-logs', 'SystemAdmin::securityLogs', ['filter' => 'privilege:security_logs']);
    $routes->get('active-sessions', 'SystemAdmin::activeSessions', ['filter' => 'privilege:security_logs']);
    $routes->get('audit-report', 'SystemAdmin::auditReport', ['filter' => 'privilege:security_logs']);
    $routes->get('activity-logs', 'SystemAdmin::activityLogs', ['filter' => 'privilege:security_logs']);
    $routes->get('transaction-logs', 'SystemAdmin::transactionLogs', ['filter' => 'privilege:security_logs']);
});
