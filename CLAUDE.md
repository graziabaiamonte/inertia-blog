# CLAUDE.md — inertia_blog

Project conventions for this blog (Laravel + React + Inertia, internship/tirocinio demo).
See `PLAN.md` for the full phased implementation plan.

## Stack

- **Laravel 13** (PHP ≥ 8.3) + **Inertia 2** + **React 19** + **TypeScript** + **Tailwind CSS v4**.
- **Authentication via Laravel Breeze** — React/Inertia/TS stack (`breeze:install react --typescript`). Do **not** use the official React starter kit.
- **Dockerized via Laravel Sail** (app + MySQL containers). Run **every** `artisan`/`composer`/`npm` command through `./vendor/bin/sail`.
- **Database:** MySQL.
- Key packages: `spatie/laravel-permission`, `spatie/laravel-medialibrary`, `spatie/laravel-query-builder`, `league/commonmark`, `laravel-lang/common`; frontend uses **Axios**.

## Mandatory conventions

- **Roles & permissions:** use **`spatie/laravel-permission`**. Roles: `admin`, `author`.
  - Public **registration is enabled**; every newly registered user is assigned the **`author`** role by default (customize Breeze's `RegisteredUserController@store`).
  - `admin` manages everything; `author` manages only their own posts.
  - Enforce authorization via **spatie permissions** (no Laravel Policies): `permission:`/`role:` route middleware and `$user->can(...)` inside each Form Request's `authorize()` (ownership checked inline); the `admin` role holds **all** permissions, which replaces a policy `before()` override.
- **Media/uploads:** use **`spatie/laravel-medialibrary`** for all images (post featured image + inline content). No manual `featured_image` column or disk handling.
- **Validation:** **always** use dedicated custom **Form Request** classes (one per write action). Never inline `$request->validate()`. Put authorization in each request's `authorize()`.
- **Enums:** use **PHP backed enums** for all fixed value sets, in `app/Enums/`:
  - `PostStatus` (Draft/Published) — cast on the model, validate with `Rule::enum(...)`.
  - `RoleName`, `PermissionName` — used in seeders, `role`/`permission` middleware, Form Request `authorize()`.
  - `MediaCollection` (Featured/Content) — used for medialibrary collections.
  - Mirror these on the frontend as TS union types in `resources/js/types/enums.ts`.
- **Email verification:** managed by **Breeze** — `User implements MustVerifyEmail`; protected routes gated behind the `verified` middleware. The seeded admin is pre-verified.
- **Filtering:** blog list filters/sorts use **`spatie/laravel-query-builder`** with explicit `allowedFilters`/`allowedSorts` (category, tag, search; `published_at`, `title`).
- **i18n:** use the **Laravel Lang** package (`laravel-lang/common`); store translations in **JSON** files (`lang/{locale}.json`, e.g. `en`, `it`). Share `locale` + `translations` to the frontend via an Inertia prop and resolve with a `t()` helper.
- **HTTP (frontend):** use **Axios** for direct HTTP/JSON calls (e.g. the media upload endpoint) via a configured instance at `resources/js/lib/axios.ts`. Inertia `useForm` for normal form posts.
- **Code style:** keep **Pint** (`pint.json`) and **Prettier** (`.prettierrc`, with `prettier-plugin-tailwindcss`) clean — run `./vendor/bin/sail pint` and the Prettier format script.
- **Tests:** write **feature/unit tests for every feature** (**Pest** — install Breeze with `--pest`). Keep `./vendor/bin/sail artisan test` green. Run the suite after each feature. Cluster tests into per-phase dirs (`tests/Feature/Phase{2..5}/`, `tests/Feature/Setup/`, `tests/Unit/Enums/`).
- **Content:** post body is Markdown, rendered server-side to **sanitized** HTML via `league/commonmark` (avoid raw `dangerouslySetInnerHTML`).

## Seed data

- `DatabaseSeeder` must create a specific admin: **`grazia@gmail.com` / `passw`** (`Hash::make('passw')`), assigned the **`admin`** role — plus a couple of `author` users and sample posts/categories/tags/comments.

## Common commands

```bash
./vendor/bin/sail up -d                       # start containers
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm run dev                 # Vite dev server
./vendor/bin/sail artisan test                # run the test suite
```
