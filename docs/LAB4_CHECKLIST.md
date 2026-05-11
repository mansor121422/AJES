# Laboratory Exercise 4 — Checklist: User Management & RBAC

This document checks your AJES system against the **Laboratory Exercise 4** requirements.

---

## 1. Develop the User Management Module

| Requirement | Status | Where in system |
|-------------|--------|------------------|
| User management interface | ✅ Done | `app/Views/Admin/Users/` — index, create, edit; routes under `admin/users` |
| User registration (account creation) | ✅ Done | Admin creates users via **Create user**; `Users::store()` |
| Profile updates | ✅ Done | **Edit user** form and `Users::update()` |
| Account deletion | ✅ Done | `Users::delete()` — soft delete with restore; `Users::restore()` |
| Validate input data | ✅ Done | Required fields, min password length, username/email format, unique email/username in `Users::store()` and `Users::update()` |
| Handle error messages | ✅ Done | Flash messages and `redirect()->back()->withInput()->with('error', ...)` |
| Password security (hashing) | ✅ Done | `password_hash(PASSWORD_DEFAULT)` on create/update/reset; `password_verify()` on login |

---

## 2. Implement Role-Based Access Control (RBAC)

| Requirement | Status | Where in system |
|-------------|--------|------------------|
| RBAC functionality | ✅ Done | `app/Filters/RoleFilter.php` + `PrivilegeFilter.php` — checks session role and feature privileges |
| Role hierarchies | ✅ Done | Roles: SUPER_ADMIN, ADMIN, PRINCIPAL, VICE_PRINCIPAL, HEAD_TEACHER, ANNOUNCER, TEACHER, GUIDANCE, STUDENT |
| Assign permissions by role | ✅ Done | `AdminPrivilege::roleMap()` defines per-role features; admin can customize per user via privilege checkboxes |
| Secure endpoints | ✅ Done | Routes use `auth` + `role:...` + `privilege:...` filters in `app/Config/Routes.php` |
| Restrict access by role | ✅ Done | RoleFilter + PrivilegeFilter redirect with error; sidebar in `app/Views/template/index.php` shows menu per role/privilege |

---

## 3. Database Management and Security

| Requirement | Status | Where in system |
|-------------|--------|------------------|
| Structured database schema | ✅ Done | `app/Database/Migrations/2026-02-19-000001_CreateCoreTables.php` — users, sections, teacher_sections, announcements, messages, records, logs, notifications |
| Relationships users ↔ roles | ✅ Done | `users.role` column; foreign keys: users→sections, teacher_sections→users/sections, announcements→users/sections, messages→users, records→users, logs→users, notifications→users |
| Data encryption / security | ✅ Done | Passwords hashed (`password_hash()`); **column-level AES-256-CBC encryption** on `guardian_name`, `guardian_contact`, `address`, `contact_number` via `DataEncryptor` library. Transparent encrypt/decrypt in `UserModel` events. Key in `.env`. |
| Database backup and restoration scripts | ✅ Done | `scripts/backup-db.ps1` / `backup-db.sh` (mysqldump + validation); `scripts/restore-db.ps1` / `restore-db.sh` (mysql restore + confirmation + pre-backup). Helper: `scripts/_ReadCiEnv.ps1`. |

---

## 4. Integration and Testing

| Requirement | Status | Where in system |
|-------------|--------|------------------|
| Modules merged | ✅ Done | Single app with Users, Auth, Sections, Records, Announcements, Chat, Dashboards, SystemAdmin |
| Unit and functional testing | ✅ Done | `tests/unit/AdminPrivilegeTest.php` — RBAC logic tests + DataEncryptor encryption round-trip tests. Run: `vendor/bin/phpunit tests/unit/` |
| Unauthorized users cannot access restricted sections | ✅ Done | AuthFilter + RoleFilter + PrivilegeFilter on routes; wrong role/privilege → redirect with error |
| Audit logging / document issues | ✅ Done | `AuditLogger` library writes login/logout/CRUD/role-change events to `logs` table; viewable at **System Admin → Security Logs** |

---

## 5. Version Control and Documentation

| Requirement | Status | Where in system |
|-------------|--------|------------------|
| Repository (GitHub/GitLab) | ✅ Done | Git repo initialized; push to your remote |
| README with setup instructions | ✅ Done | `README.md` — requirements, clone, .env, database, migrate, seed, run, default logins |
| System workflow diagram | ✅ Done | `docs/ERD_User_Role_Permission.md` — ERD for users, roles, permissions |

---

## 6. Expected Outputs / Results

| Output | Status |
|--------|--------|
| Working user management with secure authentication | ✅ |
| RBAC with assigned permissions | ✅ |
| Structured schema with secure storage (hashed passwords, encrypted fields, FKs) | ✅ |
| Documented project with version control | ✅ |
| Presentation of system functionality | N/A (your deliverable) |

---

## Summary

All Lab 4 code requirements are now implemented:

- **User Management** — full CRUD with validation, password hashing, soft delete/restore
- **RBAC** — granular feature-based privileges per role, three-layer filter enforcement (Auth → Role → Privilege)
- **Data Encryption** — AES-256-CBC column-level encryption for sensitive fields (`DataEncryptor`)
- **Database Backup/Restore** — cross-platform scripts (PowerShell + Bash) with dump validation
- **Audit Logging** — all logins, logouts, user CRUD, role changes logged to `logs` table (`AuditLogger`)
- **Unit Tests** — RBAC logic + encryption round-trip tests in `tests/unit/AdminPrivilegeTest.php`
