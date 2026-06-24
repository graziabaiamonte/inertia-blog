# Plan: Blog with Laravel 13 + React + Inertia (Dockerized)

## Context

The folder `inertia_blog/` is empty; we are building a blog from scratch. Authentication uses **Laravel Breeze**, with a specific seeded admin account,
project-wide **PHP enums**, and **feature tests written alongside every feature** — plus the
cross-cutting tooling listed below.

**Confirmed requirements**

- **Features:** Posts CRUD + admin, Categories & Tags, Comments (with moderation), Rich-text/Markdown content with image uploads.
- **Authentication:** Use **Laravel Breeze** (React + Inertia + TypeScript stack) — provides register / login / password-reset / email-verification / profile.
- **Auth & roles:** Public **registration is enabled**. Every newly registered user receives the **`author`** role by default. Roles/permissions via **`spatie/laravel-permission`**.
- **Media:** All image uploads (post featured image + inline content images) via **`spatie/laravel-medialibrary`** — no manual `featured_image` column or disk handling.
- **Validation:** **Always** validate input through dedicated custom **Form Request** classes — never inline `$request->validate()`.
- **Enums:** Use **PHP backed enums** for all fixed value sets (post status, role names, permission names, media collections) for type-safety and organization; mirror them as TS types on the frontend.
- **Email verification:** Managed by **Breeze** — `User implements MustVerifyEmail`; protected areas gated behind the `verified` middleware.
- **Filtering:** Blog list filters/sorts/includes use **`spatie/laravel-query-builder`** (allowed filters/sorts only).
- **i18n:** Use the **Laravel Lang** package (`laravel-lang/common`) and store translations in **JSON** files (`lang/{locale}.json`, e.g. `en`, `it`); strings shared to the frontend via an Inertia prop.
- **HTTP (frontend):** Use **Axios** for any direct HTTP/JSON requests (e.g. media upload endpoint) — a configured instance in `resources/js/lib/axios.ts`.
- **Code style:** Configure **Laravel Pint** (PHP) and **Prettier** (JS/TS/CSS, with `prettier-plugin-tailwindcss`); keep both clean.
- **Testing:** Every added feature ships with its own **feature/unit tests** (**Pest**); run `./vendor/bin/sail artisan test` after each feature. Tests are clustered into per-phase directories (`tests/Feature/Phase{2..5}/`, `tests/Feature/Setup/`, `tests/Unit/Enums/`).
- **Database:** MySQL.
- **Frontend language:** TypeScript.
- **Environment:** Dockerized via **Laravel Sail** (app + MySQL containers); every `artisan`/`composer`/`npm` command runs through `./vendor/bin/sail`.
- **Laravel version:** **Laravel 13** (released 2026-03-17, stable; requires PHP ≥ 8.3).

**Environment verified:** PHP 8.5.0, Composer 2.8.12, Node 24.11.1, npm 11.6.2, Docker 29.5.3 + Compose v5.1.4 (daemon running).

**Target stack:** Laravel 13 + **Breeze (React + Inertia 2 + TypeScript)** + Tailwind CSS v4, on
**Laravel Sail (Docker)** with MySQL, plus **spatie/laravel-permission** (roles),
**spatie/laravel-medialibrary** (uploads), **spatie/laravel-query-builder** (filters),
**laravel-lang/common** (JSON i18n), **Axios** (frontend HTTP), and **Pint + Prettier** (code style).

### Roles & permissions model (spatie/laravel-permission)

- **Roles:** `admin`, `author` (defined via the `RoleName` enum).
- **Permissions (via `PermissionName` enum):** `create posts`, `edit own posts`, `delete own posts`, `publish posts`, `manage all posts`, `moderate comments`, `manage taxonomy`, `manage users`.
- **`author`** (default on registration): create / edit / delete / publish **their own** posts.
- **`admin`**: everything + manage all posts, moderate comments, manage taxonomy and users.
- Authorization enforced via **`spatie/laravel-permission`** — `permission:`/`role:` route middleware and `$user->can(...)` checks inside each Form Request's `authorize()`; ownership ("own posts") checked inline. The `admin` role is granted **all** permissions in `RolePermissionSeeder` (replaces the policy `before()` override). **No Laravel Policies.**

### Enums (cross-cutting — `app/Enums/`)

- `PostStatus` (string-backed: `Draft = 'draft'`, `Published = 'published'`) — cast on `Post::$casts`, validated via `Rule::enum(PostStatus::class)`.
- `RoleName` (`Admin = 'admin'`, `Author = 'author'`) — used in seeders, `role`/`permission` middleware, Form Request `authorize()`.
- `PermissionName` (one case per permission above) — used in `RolePermissionSeeder` and `$user->can(...)` checks.
- `MediaCollection` (`Featured = 'featured'`, `Content = 'content'`) — used in medialibrary collection registration and uploads.
- Frontend mirrors these as string-literal union types / const objects in `resources/js/types/enums.ts`.

---

> **Workflow rule:** after completing each task and marking it `[x]`, immediately invoke the **`auto-commit`** agent (via `subagent_type: "auto-commit"`). The agent stages all changes and creates a git commit whose message includes the phase and task number.

---

## Phase 0 — Prerequisites check

**Goal:** Confirm the host is ready before scaffolding.

- [x] Verify Docker daemon is running (`docker info`).
- [x] Verify the Laravel installer is available (`laravel --version`); if missing, `composer global require laravel/installer`.
- [x] Confirm `inertia_blog/` is empty apart from `PLAN.md`/`CLAUDE.md`; decide install strategy (scaffold to a temp dir, then move all files incl. dotfiles into `inertia_blog/`, preserving `PLAN.md`/`CLAUDE.md`).

---

## Phase 1 — Scaffold the project (Sail + Laravel 13 + Breeze React)

**Goal:** A running Laravel 13 app inside Docker with Breeze (React/TS/Inertia) auth + Spatie packages + tooling.

- [x] Scaffold a **plain Laravel 13** skeleton (no starter kit), then move files (incl. dotfiles `.env`, `.gitignore`) into `inertia_blog/`, preserving `PLAN.md`/`CLAUDE.md`:
  ```bash
  composer create-project laravel/laravel inertia_blog   # or: laravel new inertia_blog  (choose "no starter kit")
  ```
- [x] Add Docker via Sail with MySQL: `php artisan sail:install --with=mysql`, then `./vendor/bin/sail up -d` (verify `./vendor/bin/sail ps`).
- [x] Confirm `.env` is Sail-wired: `DB_CONNECTION=mysql`, `DB_HOST=mysql`, `DB_DATABASE=inertia_blog`, `DB_USERNAME=sail`, `DB_PASSWORD=password`.
- [x] **Install Breeze with the React + TypeScript (Inertia) stack:**
  ```bash
  ./vendor/bin/sail composer require laravel/breeze --dev
  ./vendor/bin/sail artisan breeze:install react --typescript --pest
  ```
  (Breeze scaffolds Inertia + React + Tailwind + Vite + auth pages + its own **Pest** auth feature tests.)
- [x] Add PHP libraries: `./vendor/bin/sail composer require league/commonmark spatie/laravel-permission spatie/laravel-medialibrary spatie/laravel-query-builder` and dev i18n: `./vendor/bin/sail composer require laravel-lang/common --dev`.
- [x] Publish Spatie config & migrations:
  ```bash
  ./vendor/bin/sail artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
  ./vendor/bin/sail artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
  ```
- [x] **i18n (Laravel Lang → JSON):** `./vendor/bin/sail artisan lang:add en it` then `./vendor/bin/sail artisan lang:update`, configured to output **JSON** files (`lang/en.json`, `lang/it.json`); set `APP_LOCALE`. App-specific strings added to the same JSON files.
- [x] **Front-end deps:** ensure **Axios** is installed (bundled with Breeze; add if missing) and create a configured instance at `resources/js/lib/axios.ts`; add **Prettier** + `prettier-plugin-tailwindcss`: `./vendor/bin/sail npm install -D prettier prettier-plugin-tailwindcss`.
- [x] **Code-style configs:** add `pint.json` (Laravel preset) and `.prettierrc` + `.prettierignore`; add `composer`/`npm` scripts: `pint`, `format`, `format:check`.
- [x] `./vendor/bin/sail artisan storage:link`; install/build front-end: `./vendor/bin/sail npm install`, `./vendor/bin/sail npm run dev`.
- [x] `./vendor/bin/sail artisan migrate` and verify the Breeze app + auth pages load at `http://localhost`.
- **Tests (Phase 1):** Run the Breeze-provided suite — `./vendor/bin/sail artisan test` — confirming auth/registration/profile tests pass; confirm `./vendor/bin/sail pint --test` and Prettier check are clean on the fresh scaffold (baseline green before customizing).

---

## Phase 2 — Data model (enums, migrations, models, factories, seeders)

**Goal:** Schema + Eloquent models with relationships, enums, media, roles, and seed data.

### 2.1 Enums (`app/Enums/`)

- [x] Create `PostStatus`, `RoleName`, `PermissionName`, `MediaCollection` backed enums (see Context). Add a `label()` helper where useful for the UI.

### 2.2 Migrations (`database/migrations/`)

- [x] `categories`: `id, name, slug (unique), description (nullable), timestamps`.
- [x] `tags`: `id, name, slug (unique), timestamps`.
- [x] `posts`: `id, user_id (FK), category_id (FK nullable), title, slug (unique), excerpt (nullable), body (longText), status (string, default PostStatus::Draft->value), published_at (nullable), timestamps`. **No `featured_image` column — medialibrary handles it.**
- [x] `post_tag` pivot: `post_id, tag_id` (composite unique, cascade on delete).
- [x] `comments`: `id, post_id (FK), author_name, author_email, body, approved (bool default false), timestamps`.
- [x] Spatie permission tables + medialibrary `media` table (from published migrations).

### 2.3 Models (`app/Models/`)

- [ ] `User`: `use HasRoles`; `hasMany Post`; implements `MustVerifyEmail`.
- [ ] `Post`: implements `HasMedia`, `use InteractsWithMedia`; `belongsTo User`, `belongsTo Category`, `belongsToMany Tag`, `hasMany Comment`; `published()` scope; `casts: ['status' => PostStatus::class, 'published_at' => 'datetime']`; slug auto-gen in a `saving` hook; route-model binding by `slug`. Register media collections `MediaCollection::Featured` (single file) + `MediaCollection::Content`, with a thumb conversion.
- [ ] `Category`: `hasMany Post`; slug auto-gen. `Tag`: `belongsToMany Post`; slug auto-gen. `Comment`: `belongsTo Post`; `approved()` scope.

### 2.4 Factories & seeders (`database/factories/`, `database/seeders/`)

- [ ] Factories for `Post`, `Category`, `Tag`, `Comment` (Post factory uses `PostStatus` cases).
- [ ] `RolePermissionSeeder`: create roles/permissions from the `RoleName` / `PermissionName` enums; assign permissions to each role.
- [ ] `DatabaseSeeder`: seed users, taxonomy, ~10 published posts (each with a featured image via medialibrary) + comments. **Must include the specific admin account:**
  - email **`grazia@gmail.com`**, password **`passw`** (`Hash::make('passw')` — bypasses the 8-char rule), `email_verified_at` set, assigned the **`admin`** role (`RoleName::Admin`).
  - plus a couple of `author`-role users for realistic data.
- [ ] Verify: `./vendor/bin/sail artisan migrate:fresh --seed` succeeds.
- **Tests (Phase 2):**
  - `PostStatusEnumTest` / casts: a saved post returns a `PostStatus` instance; default is `Draft`.
  - `RolePermissionSeederTest`: expected roles + permissions exist and are linked.
  - `DatabaseSeederTest`: `grazia@gmail.com` exists, has the `admin` role, and `Hash::check('passw', ...)` passes.
  - Model relationship/scope tests (`published()`, `approved()`, slug auto-gen).

---

## Phase 3 — Backend (controllers, requests, authorization, routes)

**Goal:** Public read endpoints + protected role-based CRUD, validated by custom Form Requests.

### 3.1 Public controllers (`app/Http/Controllers/`)

- [ ] `BlogController@index`: paginated published posts, with filters/sorts built via **`spatie/laravel-query-builder`** — `allowedFilters` (category slug, tag slug, search term), `allowedSorts` (e.g. `published_at`, `title`), scoped to `published()`.
- [ ] `BlogController@show` (post with media URLs + approved comments), `CommentController@store` (guest comment → `approved=false`).

### 3.2 Admin/author controllers (`app/Http/Controllers/Admin/`, auth-protected)

- [ ] `PostController` resource (authors manage own, admins all; attaches images via medialibrary, uses `PostStatus`).
- [ ] `CategoryController`, `TagController` resource CRUD (`manage taxonomy`). `CommentController` moderation (`moderate comments`). `MediaController@store` inline upload. (Optional) `UserController` (`manage users`).

### 3.3 Support layers

- [ ] **A custom Form Request per write action** (never inline): `StorePostRequest`, `UpdatePostRequest`, `StoreCommentRequest`, `StoreCategoryRequest`, `UpdateCategoryRequest`, `StoreTagRequest`, `UpdateTagRequest`, `StoreMediaRequest`, … Each `authorize()` performs the authorization — `$user->can(PermissionName::...->value)` plus inline **ownership** checks for own-post actions (`$post->user_id === $user->id || $user->can('manage all posts')`); rules use `Rule::enum(PostStatus::class)` for status and image mime/size rules for uploads.
- [ ] Markdown → safe HTML via `league/commonmark` (sanitizing config), rendered server-side and passed to Inertia.
- [ ] Image handling via medialibrary collections (`MediaCollection::Featured` / `Content`); expose URLs/conversions to the frontend.

### 3.4 Routes (`routes/web.php`)

- [ ] Public: `/`, `/blog`, `/blog/{post:slug}`, `POST /blog/{post:slug}/comments`, `/category/{slug}`, `/tag/{slug}`.
- [ ] Admin under `Route::middleware(['auth','verified'])->prefix('admin')`: `dashboard`, `resource('posts')`, `resource('categories')`, `resource('tags')`, comment moderation, media upload, (optional) users. Per-action authorization via spatie `role`/`permission` middleware (and the Form Request `authorize()` checks).

- **Tests (Phase 3):**
  - `PostManagementTest`: author can CRUD **own** posts, is forbidden on others'; admin can manage all; store/update honor `PostStatus`.
  - `CommentTest`: guest store creates unapproved; only approved render publicly; admin approve/delete works.
  - `TaxonomyTest`: admin manages categories/tags; author is forbidden.
  - `MediaUploadTest`: `Storage::fake()` — featured image attaches to the post's `Featured` collection; invalid mime/oversize rejected by the Form Request.
  - `FormRequestValidationTest`: each request rejects invalid payloads (missing title, bad status enum, etc.).
  - `AuthorizationTest`: spatie permission + ownership (via Form Request `authorize()` and `role`/`permission` middleware) allow/deny by role & ownership.
  - `BlogFilterTest`: query-builder `filter[category]` / `filter[tag]` / `filter[search]` and `sort` return the expected published posts and ignore disallowed params.

---

## Phase 4 — Auth & roles (Breeze, registration → author)

**Goal:** Breeze auth with open registration + email verification; every new user defaults to the `author` role.

- [ ] Keep Breeze's register / login / password-reset / profile flows (registration stays enabled).
- [ ] **Email verification (Breeze):** make `User implements MustVerifyEmail`; keep Breeze's verification routes/notifications; gate the dashboard and all admin/author routes behind the `verified` middleware.
- [ ] On successful registration, **assign `RoleName::Author`** — customize Breeze's `RegisteredUserController@store` (or a `Registered` event listener).
- [ ] Apply spatie `HasRoles` on `User`; register middleware aliases (`role`, `permission`, `role_or_permission`).
- [ ] Gate admin-only areas behind `admin`/permissions; authors get a dashboard scoped to their own posts.
- **Tests (Phase 4):**
  - `RegistrationAssignsAuthorRoleTest`: registering a new user yields a user with the `author` role.
  - `EmailVerificationTest`: unverified users are redirected from protected routes; verifying grants access (keep/adapt Breeze's verification test).
  - Keep/adapt Breeze's `RegistrationTest` / `AuthenticationTest` / `PasswordConfirmationTest` so they stay green.
  - `AdminLoginTest`: `grazia@gmail.com` / `passw` (seeded verified) logs in and reaches admin areas; an author cannot.

---

## Phase 5 — Frontend (React + Inertia + TypeScript)

**Goal:** Public blog UI + role-aware dashboard, fully typed (enums mirrored).

### 5.1 Types, axios, i18n & layouts (`resources/js/`)

- [ ] `types/enums.ts` mirroring `PostStatus` / `RoleName` / `MediaCollection`; types for `User` (roles/permissions), `Post` (media URLs), `Category`, `Tag`, `Comment`, paginated responses, shared page props (incl. `locale` + `translations`).
- [ ] `lib/axios.ts`: configured **Axios** instance (base URL, JSON + `X-Requested-With` headers, XSRF cookie) — used for direct HTTP calls (e.g. media upload).
- [ ] **i18n bridge:** share `locale` + the JSON `translations` dictionary as an Inertia shared prop; a small `useTranslations()`/`t()` helper resolves keys client-side (mirrors the server JSON files).
- [ ] `PublicLayout` (visitor header/nav/footer); reuse Breeze's `AuthenticatedLayout` for the dashboard, conditionally rendering admin-only nav from the shared auth/permissions prop.

### 5.2 Public pages (`resources/js/pages/blog/`)

- [ ] `Index.tsx` (grid + pagination + filters), `Show.tsx` (post HTML + featured image + comments + comment form).

### 5.3 Dashboard pages (`resources/js/pages/admin/`)

- [ ] `Dashboard.tsx` (role-scoped), `posts/Index.tsx` (own for authors, all for admin), `posts/Form.tsx` (Markdown editor, featured-image upload, status select from `PostStatus`, category/tag pickers), taxonomy pages (admin), comment moderation (admin), optional user management (admin).

### 5.4 Components (`resources/js/components/`)

- [ ] `PostCard`, `CommentList`, `CommentForm`, `MarkdownEditor` (e.g. `@uiw/react-md-editor`), `ImageUpload`/featured-image picker (uploads to medialibrary endpoint **via the Axios instance**), `CategoryTagSelect`, `Pagination` (drives query-builder filter params). Forms via Inertia `useForm`; errors surface from the custom Form Requests; user-facing strings via the `t()` i18n helper.
- **Tests (Phase 5):** Inertia server-side page assertions (`assertInertia` → component + props) for blog index/show and the admin post form; optional component smoke tests.

---

## Phase 6 — Verification & full test suite

**Goal:** Prove the whole flow works end to end and the suite is green.

- [ ] `./vendor/bin/sail up -d` → containers healthy; `./vendor/bin/sail artisan migrate:fresh --seed` → roles/permissions + `grazia@gmail.com` admin + author users + sample posts (with media).
- [ ] `./vendor/bin/sail npm run dev` → app at `http://localhost`.
- [ ] **Registration + verification:** a new registration receives the `author` role and is blocked from protected routes until email is verified.
- [ ] **Admin login:** `grazia@gmail.com` / `passw` (seeded verified) works and reaches all admin areas.
- [ ] **Public:** `/blog` lists posts with query-builder filters (`filter[category]`, `filter[tag]`, `filter[search]`) + sorting + pagination; a post renders sanitized Markdown HTML + featured image + approved comments; a submitted comment is stored unapproved.
- [ ] **Author vs admin:** author manages only own posts; admin manages everything + taxonomy + users + comment moderation.
- [ ] **i18n:** switching `APP_LOCALE` (en/it) changes UI strings sourced from the JSON translation files.
- [ ] No XSS: post body is server-sanitized HTML.
- [ ] **Code style clean:** `./vendor/bin/sail pint --test` and the Prettier check pass.
- [ ] `./vendor/bin/sail npm run build` succeeds.
- [ ] **`./vendor/bin/sail artisan test` is fully green** (Breeze auth + verification + enums + seeder + posts/comments/taxonomy/media + filters + authorization + registration-role + Inertia page tests).

---

## Target project structure (key paths)

```
inertia_blog/
├── app/
│   ├── Enums/{PostStatus,RoleName,PermissionName,MediaCollection}.php
│   ├── Models/{Post,Category,Tag,Comment,User}.php        # User: HasRoles + MustVerifyEmail; Post: HasMedia/InteractsWithMedia
│   ├── Http/Controllers/{BlogController,CommentController}.php
│   ├── Http/Controllers/Admin/{PostController,CategoryController,TagController,CommentController,MediaController,UserController}.php
│   ├── Http/Controllers/Auth/*.php                        # Breeze (RegisteredUserController customized to assign author)
│   └── Http/Requests/*.php                                # one custom Form Request per write action (authorize() = spatie permission + ownership)
├── config/{permission.php,media-library.php,query-builder.php}
├── database/{migrations,factories,seeders}/               # RolePermissionSeeder, DatabaseSeeder (grazia admin, verified), spatie + media migrations
├── lang/{en.json,it.json}                                 # Laravel Lang — JSON translations
├── routes/{web.php,auth.php}                              # auth.php from Breeze (incl. email verification)
├── resources/js/
│   ├── lib/axios.ts  layouts/  pages/{blog,admin,auth}/  components/  types/{...,enums.ts}
│   └── app.tsx
├── tests/Feature/{PostManagementTest,CommentTest,TaxonomyTest,MediaUploadTest,BlogFilterTest,EmailVerificationTest,RegistrationAssignsAuthorRoleTest,...}.php
├── tests/Unit/{PostStatusEnumTest,...}.php
├── pint.json  .prettierrc  .prettierignore                # code-style configs
├── storage/app/public/   (medialibrary uploads, symlinked to public/storage)
├── docker-compose.yml    (Sail: app + mysql services)
└── .env                  (MySQL config, Sail-wired: DB_HOST=mysql; APP_LOCALE)
```
