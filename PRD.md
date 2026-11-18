# SYSTEM PROMPT / PRD FOR CODE AGENT

**Project Name:** SIPRAS (Sistem Informasi Pengaduan Sarana Sekolah)
**Language:** PHP 8.5
**Framework:** Laravel 13
**Frontend:** Blade Templates, Tailwind CSS, Alpine.js
**Database:** MariaDB 10.4.32 (XAMPP) 

## 1. INSTRUCTIONS FOR THE AI AGENT
- Act as an Expert Laravel Developer.
- Follow Laravel 13 conventions (e.g., use `bootstrap/app.php` for middleware, not `Kernel.php`).
- Strictly adhere to the Database Schema and Route definitions provided below.
- Do NOT use Vue, React, or Livewire. Stick strictly to Blade + Tailwind + Alpine.js.
- Implement proper form validation (FormRequests) and error handling.
- Use Indonesian language (`id`) for all UI texts, labels, and validation messages. Variable and table names must remain in English.

---

## 2. DATABASE SCHEMA (Migrations & Models)
Generate migrations, models, factories, and seeders for the following exact schema. Include `$fillable` arrays and relationships in the models.

**`users` table**
- `id` (bigint, pk)
- `name` (string)
- `email` (string, unique)
- `password` (string)
- `role` (enum: `'admin'`, `'siswa'`) -> default: `'siswa'`
- `timestamps`
- *Relations: HasMany `complaints`*

**`categories` table**
- `id` (bigint, pk)
- `name` (string) -> e.g., "Elektronik", "Furnitur"
- `timestamps`
- *Relations: HasMany `complaints`*

**`rooms` table**
- `id` (bigint, pk)
- `name` (string) -> e.g., "Lab Komputer 1"
- `code` (string, unique) -> e.g., "LAB-01"
- `timestamps`
- *Relations: HasMany `complaints`*

**`complaints` table**
- `id` (bigint, pk)
- `ticket_number` (string, unique) -> format generated on create: `TKT-YYYYMMDD-XXXX` (where XXXX is a daily counter or random string).
- `user_id` (foreignId, cascade on delete)
- `category_id` (foreignId, restrict on delete)
- `room_id` (foreignId, restrict on delete)
- `title` (string)
- `description` (text)
- `evidence_image` (string) -> file path
- `status` (enum: `'pending'`, `'in_progress'`, `'resolved'`, `'rejected'`) -> default: `'pending'`
- `admin_notes` (text, nullable)
- `resolved_image` (string, nullable) -> file path
- `resolved_at` (timestamp, nullable)
- `timestamps`
- *Relations: BelongsTo `user`, `category`, `room`*

---

## 3. ROLE-BASED ACCESS & ROUTES
Implement Authentication using `Laravel Breeze` (Blade stack).
Create a custom middleware `RoleMiddleware` and register it in `bootstrap/app.php`.

### A. Authentication & General (No custom middleware needed, just `auth`)
- `GET /` -> Redirect to `/login`
- `GET /dashboard` -> Controller logic: If `auth()->user()->role === 'admin'`, redirect to `/admin/dashboard`. Else, redirect to `/siswa/dashboard`.

### B. Siswa Routes (Middleware: `auth`, `role:siswa`)
Prefix: `/siswa` | Controller Namespace: `App\Http\Controllers\Siswa`
- `GET /dashboard` -> `DashboardController@index` (Shows stats & recent user's complaints)
- `GET /pengaduan/create` -> `ComplaintController@create` (Form to submit)
- `POST /pengaduan` -> `ComplaintController@store` (Handle upload & insert)
- `GET /pengaduan` -> `ComplaintController@index` (List of user's complaints)
- `GET /pengaduan/{complaint}` -> `ComplaintController@show` (Timeline and details)

### C. Admin Routes (Middleware: `auth`, `role:admin`)
Prefix: `/admin` | Controller Namespace: `App\Http\Controllers\Admin`
- `GET /dashboard` -> `DashboardController@index` (Global stats)
- `Resource /master/categories` -> `CategoryController` (CRUD)
- `Resource /master/rooms` -> `RoomController` (CRUD)
- `GET /pengaduan` -> `ComplaintManagementController@index` (List all complaints with search/filter)
- `GET /pengaduan/{complaint}` -> `ComplaintManagementController@show` (Detail page)
- `PATCH /pengaduan/{complaint}/status` -> `ComplaintManagementController@updateStatus` (Update status, add notes, upload resolved image)

---

## 4. BUSINESS LOGIC & CONSTRAINTS

**File Uploads:**
- Must use Laravel Storage (`public` disk).
- Path: `complaints/evidence/` and `complaints/resolved/`.
- Validation: `image|mimes:jpeg,png,jpg|max:2048`.
- Agent must automatically run `php artisan storage:link`.

**Status Update Rules (Admin `updateStatus` method):**
- If changing to `'rejected'`, `admin_notes` is **required**.
- If changing to `'resolved'`, `resolved_image` is **optional but recommended**, and `resolved_at` must be set to `now()`.

---

## 5. EXECUTION PLAN FOR CODE AGENT
*(Agent: Execute these steps sequentially. Do not move to the next phase until the current one is fully implemented and bug-free.)*

### Phase 1: Foundation
1. Create models, migrations, factories, and database seeders exactly as specified in Section 2.
2. Run migrations and seed the database with dummy data (1 admin user, 3 siswa users, 4 categories, and the 105-room SMK Negeri 5 Surakarta inventory from `database/seeders/data/school_rooms.php`).

### Phase 2: Auth & Middleware
1. Install Laravel Breeze (Blade).
2. Create `RoleMiddleware` that checks `auth()->user()->role`.
3. Modify the login redirection logic to route to `/admin/dashboard` or `/siswa/dashboard` based on role.

### Phase 3: Admin Master Data (CRUD)
1. Build controllers and Blade views using Tailwind CSS for `/admin/master/categories` and `/admin/master/rooms`.
2. Ensure simple, responsive table layouts with Add, Edit, Delete modals or separate pages.

### Phase 4: Siswa Flow (Core Feature)
1. Create `/siswa/pengaduan/create` view. Use Alpine.js to create a live image preview for the `evidence_image` upload field.
2. Implement `ComplaintController@store` with image upload handling and automatic ticket number generation.
3. Create `/siswa/pengaduan` to list complaints in a Tailwind Card grid or table with status badges.
4. Create `/siswa/pengaduan/{id}` showing the complaint details.

### Phase 5: Admin Verification Flow
1. Create `/admin/pengaduan` showing a Data Table of all complaints (order by newest first).
2. Create the Admin detail view with a form to update the status. Include fields for `admin_notes` (textarea) and `resolved_image` (file input). Show/hide these fields dynamically using Alpine.js based on the selected status dropdown.
3. Implement the controller logic to handle status updates.

### Phase 6: UI/UX Polish
1. Apply a consistent color palette (e.g., Indigo/Blue as primary).
2. Ensure all Status Badges use standard colors (Pending = Yellow, In Progress = Blue, Resolved = Green, Rejected = Red).
3. Add session flash message components (Success/Error alerts) on layout pages.

---
**[END OF PRD - AGENT, PLEASE ACKNOWLEDGE AND BEGIN PHASE 1]**