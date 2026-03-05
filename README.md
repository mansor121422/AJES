# AJES Crier — Ano Jay Elementary School Announcement System

School announcement and communication system built with **CodeIgniter 4**.  
**Laboratory Exercise 3** covers: **Login**, **Dashboard**, and **Records** modules.

---

## Quick setup (AJES)

1. **Requirements:** PHP 8.2+, MySQL, Composer.
2. **Clone and install:**
   ```bash
   cd AJES
   composer install
   ```
3. **Environment:** Copy `env` to `.env`, set `CI_ENVIRONMENT = development` and database:
   ```ini
   database.default.hostname = localhost
   database.default.database = AjesDB
   database.default.username = root
   database.default.password =
   ```
4. **Database:** Create MySQL database `AjesDB`, then run migrations and seed:
   ```bash
   php spark migrate
   php spark db:seed
   ```
5. **Run:** Point the document root to the `public` folder (e.g. `http://localhost/AJES/public/` or use `php spark serve` and visit `http://localhost:8080`).

**Default logins (after seeding):**  
`admin` / `123123`, `guidance` / `123123`, `principal` / `123123`, `teacher1` / `123123`, `student1` / `123123`, `announcer` / `123123`.

---

## Module descriptions (Lab 3)

| Module | Description |
|--------|-------------|
| **Login** | Session-based authentication; credentials validated against DB; password hashing; error handling and lockout after 5 failed attempts; forgot password and reset password (email + token). |
| **Dashboard** | Role-based dashboards (Admin, Principal, Teacher, Guidance, Announcer, Student); sidebar navigation; KPIs and recent activity per role; responsive layout; access controlled by role. |
| **Records** | CRUD for guidance/student records; search by keyword; filter by type; pagination; restricted to **GUIDANCE** and **ADMIN**; data validation and CSRF on forms. |

**Lab 3 documentation:** See [docs/LAB3_SYSTEM_DEVELOPMENT.md](docs/LAB3_SYSTEM_DEVELOPMENT.md) for answers to the lab questions, flowchart (login → dashboard → records), output/results, and conclusion.

---

## CodeIgniter 4 Framework

## What is CodeIgniter?

CodeIgniter is a PHP full-stack web framework that is light, fast, flexible and secure.
More information can be found at the [official site](https://codeigniter.com).

This repository holds the distributable version of the framework.
It has been built from the
[development repository](https://github.com/codeigniter4/CodeIgniter4).

More information about the plans for version 4 can be found in [CodeIgniter 4](https://forum.codeigniter.com/forumdisplay.php?fid=28) on the forums.

You can read the [user guide](https://codeigniter.com/user_guide/)
corresponding to the latest version of the framework.

## Important Change with index.php

`index.php` is no longer in the root of the project! It has been moved inside the *public* folder,
for better security and separation of components.

This means that you should configure your web server to "point" to your project's *public* folder, and
not to the project root. A better practice would be to configure a virtual host to point there. A poor practice would be to point your web server to the project root and expect to enter *public/...*, as the rest of your logic and the
framework are exposed.

**Please** read the user guide for a better explanation of how CI4 works!

## Repository Management

We use GitHub issues, in our main repository, to track **BUGS** and to track approved **DEVELOPMENT** work packages.
We use our [forum](http://forum.codeigniter.com) to provide SUPPORT and to discuss
FEATURE REQUESTS.

This repository is a "distribution" one, built by our release preparation script.
Problems with it can be raised on our forum, or as issues in the main repository.

## Contributing

We welcome contributions from the community.

Please read the [*Contributing to CodeIgniter*](https://github.com/codeigniter4/CodeIgniter4/blob/develop/CONTRIBUTING.md) section in the development repository.

## Server Requirements

PHP version 8.2 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

> [!WARNING]
> - The end of life date for PHP 7.4 was November 28, 2022.
> - The end of life date for PHP 8.0 was November 26, 2023.
> - The end of life date for PHP 8.1 was December 31, 2025.
> - If you are still using below PHP 8.2, you should upgrade immediately.
> - The end of life date for PHP 8.2 will be December 31, 2026.

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library
