# ImprovementPlan

Further development plan for the blog (Laravel 13 + Inertia + React + TypeScript + Tailwind).
This document is written to be executed task-by-task by an implementation agent. Read the **Working rules** first, then execute the phases **in order**.

---

## Working rules (read before doing anything)

- **Run every command through Sail**: prefix `artisan` / `composer` / `npm` with `./vendor/bin/sail`. Example: `./vendor/bin/sail artisan route:list`.
- **Containers must be up**: if commands fail to connect, run `./vendor/bin/sail up -d` first.
- **Tests**: NEVER run `./vendor/bin/sail artisan test` directly. Always delegate test execution to the **`test-runner`** agent (Agent tool, `subagent_type: "test-runner"`).
- **Translations**: when adding/removing translation keys, delegate sync checks to the **`translation-sync`** agent.
- **Tailwind/styling**: for non-trivial styling work, delegate to the **`tailwind-css-stylist`** agent.
- **Commit after every task**: immediately after a task is finished and verified, invoke the **`auto-commit`** agent (Agent tool, `subagent_type: "auto-commit"`). One commit per completed task, message referencing the phase/task number.
- **Code style**: keep Pint and Prettier clean. After PHP changes run `./vendor/bin/sail pint`. After JS/TS changes run the Prettier format script (e.g. `./vendor/bin/sail npm run format` if defined, otherwise `npx prettier --write`).
- **Type-check / build the frontend** after frontend changes: `./vendor/bin/sail npm run build` (must succeed with no TypeScript errors).
- **Conventions** (from CLAUDE.md): Form Requests for all writes (no inline `$request->validate()`); authorization via spatie permissions; PHP backed enums mirrored as TS unions in `resources/js/types/enums.ts`; markdown rendered server-side.
- **Definition of done for each task**: code changed + style clean + frontend builds + relevant tests green (via `test-runner`) + committed (via `auto-commit`).

### Recommended execution order
Execute **Phase 1 (Inertia v3 upgrade) first**, because it changes the framework foundation; validating later features against v3 avoids rework. Phases 2–4 are largely independent of each other and can follow in order.

### Current state (verified)
- `inertiajs/inertia-laravel` **v2.0.24**, `@inertiajs/react` **^2.0.0**.
- `react` / `react-dom` **^18.2.0** (Inertia v3 requires **React 19**).
- Laravel **v13.16.1**, PHP **^8.3** (both satisfy Inertia v3 minimums).
- No usage of `Inertia::lazy()`, `router.on(...)`, the `future` config block, `router.cancel()`, progress exports, persistent `.layout =`, or `<Deferred>` (so those v3 breaking changes do **not** apply here).
- Axios **is** used (`resources/js/bootstrap.ts`, `resources/js/lib/axios.ts`, `resources/js/Components/ImageUpload.tsx`, `resources/js/types/global.d.ts`) — v3 drops Axios from the bundle, so it must be installed explicitly.

---

## Phase 1 — Upgrade Inertia v2 → v3 (and React 18 → 19)

**Why:** The project targets the latest stack. Inertia v3 requires React 19 and a few mechanical changes. For this codebase the upgrade is small (no use of the removed/renamed APIs), but it touches dependencies and the build, so do it carefully and verify the app still renders end-to-end.

> Tip: do this work on a clean git tree so it can be reverted easily if the build breaks.

### [ ] Task 1.1 — Upgrade server-side adapter (Composer)
- Run:
  ```bash
  ./vendor/bin/sail composer require "inertiajs/inertia-laravel:^3.0" -W
  ```
  (`-W` lets dependencies adjust if needed.)
- **Verify:** `./vendor/bin/sail composer show inertiajs/inertia-laravel` reports a `3.x` version. No composer errors.

### [ ] Task 1.2 — Upgrade React to 19 + Inertia React adapter (npm)
- Run (single install is fine):
  ```bash
  ./vendor/bin/sail npm install react@^19 react-dom@^19 @inertiajs/react@^3.0
  ./vendor/bin/sail npm install -D @types/react@^19 @types/react-dom@^19 @vitejs/plugin-react@^4.3.0
  ```
- **Keep Axios** (v3 no longer bundles it, but this project uses it):
  ```bash
  ./vendor/bin/sail npm install axios
  ```
- **Verify:** `package.json` shows `react`/`react-dom` at `^19`, `@inertiajs/react` at `^3`, `axios` present. `./vendor/bin/sail npm install` completes with no peer-dependency errors.

### [ ] Task 1.3 — Republish Inertia config and clear views
- Run:
  ```bash
  ./vendor/bin/sail artisan vendor:publish --provider="Inertia\\ServiceProvider" --force
  ./vendor/bin/sail artisan view:clear
  ./vendor/bin/sail artisan optimize:clear
  ```
- This creates/updates `config/inertia.php` with the new v3 structure (a `pages` namespace + a `testing` section). There were no custom Inertia config values before, so no customizations need re-applying.
- **Verify:** `config/inertia.php` exists and contains a `'pages'` key.

### [ ] Task 1.4 — Update the root Blade template title attribute
- **File:** `resources/views/app.blade.php`, line 7.
- **Before:**
  ```blade
  <title inertia>{{ config('app.name', 'Laravel') }}</title>
  ```
- **After:**
  ```blade
  <title data-inertia>{{ config('app.name', 'Laravel') }}</title>
  ```
- Leave `@routes`, `@viteReactRefresh`, `@vite(...)`, `@inertiaHead`, and `@inertia` unchanged (the directive form is still supported in v3).
- **Verify:** the file compiles; the homepage still renders a `<title>`.

### [ ] Task 1.5 — Keep the existing page resolver (verify it still works under v3)
- **File:** `resources/js/app.tsx`. The current setup uses `resolvePageComponent` from `laravel-vite-plugin/inertia-helpers` and `createInertiaApp({ resolve, setup, ... })`. This pattern is still valid in v3 — **do not rewrite it unless the build fails**.
- There is **no** `future` block, no `router.on('invalid'|'exception')`, no `router.cancel()`, no progress imports in this project, so nothing to migrate here.
- Adopting the new optional `@inertiajs/vite` plugin and SSR is **out of scope** for this phase (the app currently has no SSR). Do not add it.
- **Verify (build):**
  ```bash
  ./vendor/bin/sail npm run build
  ```
  Must complete with **zero** TypeScript and bundler errors. If — and only if — the build fails on page resolution, switch `app.tsx` to the eager-glob resolver:
  ```tsx
  resolve: (name) => {
      const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true });
      return pages[`./Pages/${name}.tsx`] as never;
  },
  ```

### [ ] Task 1.6 — Runtime smoke test
- Ensure dev server runs: `./vendor/bin/sail npm run dev` (and `./vendor/bin/sail up -d`).
- Manually load `/` and `/login`; both must render without a JS console error and without an Inertia error modal.
- **Verify:** pages render; navigation between pages works (Inertia client is functioning).

### [ ] Task 1.7 — Tests
- Delegate the **full suite** to the **`test-runner`** agent. All tests must pass. Inertia assertion helpers (`assertInertia`, `Inertia\Testing\AssertableInertia`) are compatible with v3; if any test references a removed v2 API, fix it minimally.

### [ ] Task 1.8 — Style + commit
- Run Pint and Prettier; then invoke **`auto-commit`**.

---

## Phase 2 — Show the blog on the homepage (remove the `/blog` route)

**Why:** Currently `/` renders the default Laravel landing page (`Welcome.tsx`) and the blog list lives at `/blog`. The blog list should appear directly on the homepage. Decision: the **list moves to `/`** and **single posts move to `/{slug}`**; the `/blog` route is removed.

**Key reuse note:** keep the existing **route names** (`blog.index`, `blog.show`, `comments.store`) and only change their **URIs**. Because all frontend `route('blog.index'|'blog.show'|'comments.store')` calls reference names (not paths), they keep working with no frontend changes.

### [ ] Task 2.1 — Point `/` to the blog index
- **File:** `routes/web.php`.
- **Before (lines 18–25):**
  ```php
  Route::get('/', function () {
      return Inertia::render('Welcome', [
          'canLogin' => Route::has('login'),
          'canRegister' => Route::has('register'),
          'laravelVersion' => Application::VERSION,
          'phpVersion' => PHP_VERSION,
      ]);
  });
  ```
- **After:**
  ```php
  Route::get('/', [BlogController::class, 'index'])->name('blog.index');
  ```
- Remove now-unused imports at the top of the file: `use Illuminate\Foundation\Application;` and `use Inertia\Inertia;` (verify `Inertia` is not used elsewhere in this file before removing it).

### [ ] Task 2.2 — Remove the old `/blog` list route
- **File:** `routes/web.php`. Delete this line (line 28):
  ```php
  Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
  ```

### [ ] Task 2.3 — Move single-post + comment routes to root, registered LAST
- **File:** `routes/web.php`. Remove the old lines (29–30):
  ```php
  Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
  Route::post('/blog/{post:slug}/comments', [CommentController::class, 'store'])->name('comments.store');
  ```
- Re-add them at the **very bottom of the file, AFTER `require __DIR__.'/auth.php';`**, keeping the same names:
  ```php
  require __DIR__.'/auth.php';

  // Public single post + comments — wildcard at root, MUST be registered last
  // so literal routes (/login, /dashboard, /admin/*, /up, etc.) take precedence.
  Route::get('/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
  Route::post('/{post:slug}/comments', [CommentController::class, 'store'])->name('comments.store');
  ```
- **Why last:** `/{post:slug}` is a root-level wildcard. It only matches single segments (no slashes), so `/admin/...` and `/profile` are safe, but single-segment literals like `/login` and `/dashboard` must be registered **before** the wildcard to win the match. Laravel matches in registration order; `auth.php` and the admin/dashboard routes are registered above, so placing the wildcard after `require auth.php` guarantees correct precedence.

### [ ] Task 2.4 — Delete the now-unused Welcome page
- Delete `resources/js/Pages/Welcome.tsx`.
- `grep` the codebase for any remaining reference to `Welcome` (e.g. `grep -rn "Welcome" resources/js`) and remove/redirect them. A "Home" nav link, if present in `resources/js/Layouts/PublicLayout.tsx`, should use `route('blog.index')` (which now resolves to `/`).
- **Note:** `resources/js/Pages/Blog/Index.tsx` already calls `route('blog.index')` for filters — it now points to `/` automatically, no change needed.

### [ ] Task 2.5 — Verify routing
- Run `./vendor/bin/sail artisan route:list` and confirm:
  - `/` → `blog.index`, `/{post:slug}` → `blog.show`, `/{post:slug}/comments` → `comments.store`.
  - No `/blog` route remains.
  - `/login`, `/dashboard`, `/admin/*` are still listed (precedence preserved).
- Manually load `/` (blog list with filters/search/pagination), a single post at `/{slug}`, and `/login` + `/dashboard` (must NOT be swallowed by the wildcard).

### [ ] Task 2.6 — Update tests
- Fix any test asserting the literal path `/blog` or the `Welcome` component. Tests using route **names** still pass.
- Delegate to **`test-runner`**; suite must be green.

### [ ] Task 2.7 — Style + commit
- Pint + Prettier; then **`auto-commit`**.

---

## Phase 3 — Fix the 404 on Edit / Delete / Save (pass the slug, not the id)

**Why (root cause, verified):** The `Post`, `Category`, and `Tag` models declare `getRouteKeyName()` returning `'slug'` (`app/Models/Post.php:54`, `app/Models/Category.php:33`, `app/Models/Tag.php:33`). This makes the admin `edit` / `update` / `destroy` routes bind their `{post}` / `{category}` / `{tag}` parameter by **slug**. But the frontend passes the numeric **id** (e.g. `route('admin.posts.edit', post.id)`). Ziggy then builds `/admin/posts/5/edit`, and Laravel looks for a model whose **slug = "5"** → not found → **404**. This affects Edit, Delete, and Save (update) across Dashboard, Posts, Categories, and Tags.

**Fix (decision):** pass the **slug** instead of the **id** in every `route(...)` call that targets these slug-bound admin routes. The slug is already available in all relevant props (admin index controllers already return `slug`; edit Form props include `slug`; the Dashboard serializes the full `Post` model, which includes the `slug` column).

For each file below, change the route parameter from `.id` to `.slug`.

### [ ] Task 3.1 — Dashboard
- **File:** `resources/js/Pages/Dashboard.tsx`.
  - Edit link (~line 108): `route('admin.posts.edit', post.id)` → `route('admin.posts.edit', post.slug)`.
  - Delete link (~line 117): `route('admin.posts.destroy', post.id)` → `route('admin.posts.destroy', post.slug)`.
- Confirm `slug` is not in the `$hidden` array of `app/Models/Post.php` (so it reaches the frontend). If it is hidden, expose it for this payload.

### [ ] Task 3.2 — Admin Posts index
- **File:** `resources/js/Pages/Admin/Posts/Index.tsx`.
  - Edit (~line 98): `post.id` → `post.slug`.
  - Delete (~line 104): `post.id` → `post.slug`.

### [ ] Task 3.3 — Admin Categories index
- **File:** `resources/js/Pages/Admin/Categories/Index.tsx`.
  - Edit (~line 67) and Delete: `cat.id` → `cat.slug`.

### [ ] Task 3.4 — Admin Tags index
- **File:** `resources/js/Pages/Admin/Tags/Index.tsx`.
  - Edit (~line 69) and Delete: `tag.id` → `tag.slug`.

### [ ] Task 3.5 — Edit Forms (update + media upload)
- **File:** `resources/js/Pages/Admin/Posts/Form.tsx`.
  - Update submit (~line 61): `route('admin.posts.update', post!.id)` → `route('admin.posts.update', post!.slug)`.
  - **Also check** the media upload call (`admin.posts.media.store`). This route also binds `{post}` by slug. Locate it (it may live in `Form.tsx` or in `resources/js/Components/ImageUpload.tsx`, which uses Axios). Whatever value identifies the post in that URL must be the **slug**, not the id. Update accordingly.
- **File:** `resources/js/Pages/Admin/Categories/Form.tsx` (~line 25): `route('admin.categories.update', category!.id)` → `category!.slug`.
- **File:** `resources/js/Pages/Admin/Tags/Form.tsx` (~line 24): `route('admin.tags.update', tag!.id)` → `tag!.slug`.

### [ ] Task 3.6 — TypeScript types
- In `resources/js/types/` (e.g. `index.ts`), ensure the types used by the pages above include `slug: string`:
  - `AdminPost` (used by Dashboard + Admin Posts index) must have `slug: string`.
  - The category/tag list-item types used by the admin index/form pages must have `slug: string`.
- This keeps `./vendor/bin/sail npm run build` (TypeScript) green when referencing `.slug`.

### [ ] Task 3.7 — Tests
- Add/adjust feature tests verifying an admin can: open the edit page, delete, and update a post/category/tag through the slug-bound routes (no 404). For an author, verify they can edit/delete **their own** post and are forbidden (403) on others' posts (authorization is unchanged; only the URL key changes).
- Delegate to **`test-runner`**; suite must be green.

### [ ] Task 3.8 — Build + style + commit
- `./vendor/bin/sail npm run build` (no TS errors); Prettier; then **`auto-commit`**.

---

## Phase 4 — Language switcher button in the navbar

**Why:** i18n is already wired (`HandleInertiaRequests` shares `locale` and `translations` from `lang/{locale}.json` at `app/Http/Middleware/HandleInertiaRequests.php:32-45`; the `t()` helper lives in `resources/js/lib/i18n.ts`; locale files `lang/en.json` and `lang/it.json` exist). What is missing is a way for the user to **change** the locale and have the choice **persist**. Currently there is no locale-switch route and no middleware that sets the locale from the user's choice.

Supported locales: **`en`**, **`it`**.

### [ ] Task 4.1 — Persist the chosen locale (middleware)
- Create middleware `app/Http/Middleware/SetLocale.php`:
  - Read the chosen locale from the session: `session('locale')`.
  - If it is one of the supported locales (`en`, `it`), call `app()->setLocale($locale)`.
- Register it in the `web` middleware group **before** `HandleInertiaRequests` (in `bootstrap/app.php` via `->withMiddleware(...)`, appending to the `web` group), so the `locale`/`translations` shared by `HandleInertiaRequests` reflect the active locale on the same request.
- Define the supported-locale list in **one** reusable place (e.g. a `config('app.supported_locales')` entry, or a small constant/enum) so the middleware and the Form Request below share it.

### [ ] Task 4.2 — Locale-switch route + Form Request
- Add a public route in `routes/web.php` (it must be reachable by guests too; place it near the top with the other non-wildcard routes, **not** below the `/{post:slug}` wildcard):
  ```php
  Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
  ```
- Create `app/Http/Controllers/LocaleController.php` with an `update` method.
- Create a Form Request (e.g. `app/Http/Requests/UpdateLocaleRequest.php`) — **no inline `$request->validate()`** (project convention). Rules: `locale` is `required` and `Rule::in(['en','it'])` (reuse the shared supported-locale list). `authorize()` returns `true` (public).
- In `update`: store the validated locale in the session (`$request->session()->put('locale', $validated['locale'])`) and `return back()`.

### [ ] Task 4.3 — LocaleSwitcher component
- Create `resources/js/Components/LocaleSwitcher.tsx`:
  - Read the current locale from Inertia shared props: `usePage().props.locale`.
  - Render the available locales (EN / IT) as buttons (or a small dropdown), highlighting the active one.
  - On selection, submit with Inertia: `router.post(route('locale.update'), { locale }, { preserveScroll: true, preserveState: false })` so the page reloads with the new translations.
  - Use the `t()` helper for any visible label (e.g. an accessible "Language" label).
- For styling, delegate to the **`tailwind-css-stylist`** agent (compact, fits the existing navbar style).

### [ ] Task 4.4 — Add the switcher to both navbars
- Insert `<LocaleSwitcher />` into:
  - `resources/js/Layouts/PublicLayout.tsx` (public navbar, around the existing `<nav>` block).
  - `resources/js/Layouts/AuthenticatedLayout.tsx` (logged-in navbar).
- This makes it available on every page (public and authenticated).

### [ ] Task 4.5 — Translation keys + sync
- Add any new UI keys (e.g. `"Language"`) to **both** `lang/en.json` and `lang/it.json`.
- Delegate a sync check to the **`translation-sync`** agent (it writes a report to `output/translations/sync-report.md`); resolve any missing/empty keys.

### [ ] Task 4.6 — Tests
- Feature test: `POST /locale` with `it` then `en` updates the session and the Inertia-shared `locale` prop reflects the change; an invalid locale (e.g. `de`) is rejected (validation error / 422). Optionally assert that a translated string changes between locales.
- Delegate to **`test-runner`**; suite must be green.

### [ ] Task 4.7 — Build + style + commit
- `./vendor/bin/sail npm run build`; Pint + Prettier; then **`auto-commit`**.

---

## End-to-end verification (after all phases)

1. **Inertia v3:** `./vendor/bin/sail composer show inertiajs/inertia-laravel` shows `3.x`; `package.json` shows React `^19` and `@inertiajs/react` `^3`. `./vendor/bin/sail npm run build` succeeds; `/` and `/login` render with no console/modal errors.
2. **Homepage = blog:** `./vendor/bin/sail artisan route:list` shows `/` = `blog.index`, `/{post:slug}` = `blog.show`, no `/blog`; `/login` `/dashboard` `/admin/*` still present and matched correctly. The homepage shows the article list with working filters/search/pagination; a single post opens at `/{slug}`; posting a comment works.
3. **Wildcard safety:** loading `/login` and `/dashboard` does NOT hit the single-post route.
4. **404 fix:** log in as admin (`grazia@gmail.com` / `passw`); in Dashboard, Posts, Categories, and Tags, **Edit** opens the form, **Delete** removes the item, and **Save** (update) persists — no 404. Featured-image upload still works.
5. **Language switcher:** the navbar (public and authenticated) has an EN/IT switch; toggling changes the translated UI text and the choice **persists** across navigation and refresh; `lang/en.json` and `lang/it.json` are in sync.
6. **Quality gates:** Pint clean, Prettier clean, frontend build clean, full Pest suite green (via **`test-runner`**).
7. **Commits:** one `auto-commit` per completed task.
