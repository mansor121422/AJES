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
| RBAC functionality | ✅ Done | `app/Filters/RoleFilter.php` — checks session role, blocks unauthorized access |
| Role hierarchies | ✅ Done | Roles: ADMIN, PRINCIPAL, ANNOUNCER, TEACHER, GUIDANCE, STUDENT (different dashboards and permissions) |
| Assign permissions by role | ✅ Done | Admin: users, sections, records; Teacher: teacher sections, announcements; Guidance+Admin: records; etc. |
| Secure endpoints | ✅ Done | Routes use `auth` + `role:ADMIN`, `role:TEACHER`, `role:GUIDANCE,ADMIN`, etc. in `app/Config/Routes.php` |
| Restrict access by role | ✅ Done | RoleFilter redirects with error; sidebar in `app/Views/template/index.php` shows menu per role |

---

## 3. Database Management and Security

| Requirement | Status | Where in system |
|-------------|--------|------------------|
| Structured database schema | ✅ Done | `app/Database/Migrations/2026-02-19-000001_CreateCoreTables.php` — users, sections, teacher_sections, announcements, messages, records, logs, notifications |
| Relationships users ↔ roles | ✅ Done | `users.role` column (role stored on user); foreign keys in same migration: users→sections, teacher_sections→users/sections, announcements→users/sections, messages→users, records→users, logs→users, notifications→users |
| Data encryption / security | ⚠️ Partial | Passwords hashed; no column-level encryption. App uses HTTPS recommendation and secure session. |
| Database backup and restoration scripts | ❌ Not done | No custom backup/restore scripts (e.g. mysqldump or CI backup) in repo. You could add a script or use MySQL tools. |

---

## 4. Integration and Testing

| Requirement | Status | Where in system |
|-------------|--------|------------------|
| Modules merged | ✅ Done | Single app with Users, Auth, Sections, Records, Announcements, Chat, Dashboards |
| Unauthorized users cannot access restricted sections | ✅ Done | AuthFilter + RoleFilter on routes; wrong role → redirect with “You are not allowed to access that page.” |
| Document issues and solutions | ⚠️ Partial | Lab 3 doc exists (`docs/LAB3_SYSTEM_DEVELOPMENT.md`); no dedicated Lab 4 “issues and solutions” section yet |

---

## 5. Version Control and Documentation

| Requirement | Status | Where in system |
|-------------|--------|------------------|
| Repository (GitHub/GitLab) | ⚠️ You confirm | Git repo present; you maintain remote (GitHub/GitLab). |
| README with setup instructions | ✅ Done | `README.md` — requirements, clone, .env, database, migrate, seed, run, default logins |
| System workflow diagram (user role interactions) | ❌ Not done | No diagram file in repo (e.g. ERD or workflow for login → role → dashboards/actions). You can add one (e.g. in `docs/`). |

---

## 6. Expected Outputs / Results

| Output | Status |
|--------|--------|
| Working user management with secure authentication | ✅ |
| RBAC with assigned permissions | ✅ |
| Structured schema with secure storage (hashed passwords, FKs) | ✅ |
| Documented project with version control | ⚠️ README yes; Lab 4–specific doc and diagram to add |
| Presentation of system functionality | N/A (your deliverable) |

---

## Summary: What You Have vs What’s Missing

**You already have:**
- Full user management (create, update, soft delete, restore).
- RBAC (roles, filters, role-based menu and routes).
- Structured schema with foreign keys and password hashing.
- README and setup instructions.
- Integration and access control so unauthorized users cannot open restricted sections.

**To fully align with Lab 4, consider adding:**
1. **Database backup/restore** — e.g. a short script (batch/shell) that runs `mysqldump` for backup and instructions for restore, or use CodeIgniter’s DB Utils backup if you use it.
2. **Lab 4 documentation** — e.g. `docs/LAB4_SYSTEM_DEVELOPMENT.md` with: answers to the lab questions (RBAC importance, user management and security), short “issues and solutions,” and output/result notes.
3. **System workflow diagram** — e.g. ERD or flowchart for users/roles/permissions and login → role → screens (add as image or Mermaid in `docs/`).

Use this checklist when writing your **Output/Results** and **Conclusion** for Lab 4.
