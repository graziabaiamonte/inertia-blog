# Test Run

**Date:** 2026-06-24
**Time:** latest run (Phase 3 focus + full suite)

## Scope
Full suite (`./vendor/bin/sail artisan test`) + Phase 3 directory (`tests/Feature/Phase3/`)

## Summary
- Total: 59 tests, 139 assertions
- Passed: 50
- Failed: 9 (all in Phase 3)
- Time: ~4.8s

## Results

### Failed — BlogIndexTest (6 failures)

- `BlogIndexTest::it_lists_only_published_posts_on_the_blog_index` — BlogIndexTest.php
  **Error**: HTTP 500 — `TypeError: Spatie\QueryBuilder\QueryBuilder::allowedFilters(): Argument #1 must be of type Spatie\QueryBuilder\AllowedFilter|string, array given`
  **Location**: `app/Http/Controllers/BlogController.php:25`
  **Diagnosis**: The installed version of `spatie/laravel-query-builder` does not accept an array as the single argument to `->allowedFilters(...)`. The controller passes one array literal; the package expects the filters to be spread as variadic arguments, e.g. `->allowedFilters(filter1, filter2, filter3)`, or the package version installed requires a different call signature. Check the installed package version (`./vendor/bin/sail composer show spatie/laravel-query-builder`) — versions below 5.x use variadic args, not a single array.
  **Suggested fix**: Change `->allowedFilters([...])` at line 25 to `->allowedFilters(...)` (spread the array) or pass filters as separate arguments.

- `BlogIndexTest::it_filters_posts_by_category_slug` — BlogIndexTest.php
  **Error**: "Not a valid Inertia response." (cascade from the same 500 on the index route)

- `BlogIndexTest::it_filters_posts_by_tag_slug` — BlogIndexTest.php
  **Error**: "Not a valid Inertia response." (cascade)

- `BlogIndexTest::it_filters_posts_by_search_term` — BlogIndexTest.php
  **Error**: "Not a valid Inertia response." (cascade)

- `BlogIndexTest::it_sorts_posts_by_title_when_requested` — BlogIndexTest.php
  **Error**: "Not a valid Inertia response." (cascade)

- `BlogIndexTest::it_ignores_disallowed_sort_and_filter_params` — BlogIndexTest.php
  **Error**: HTTP 500 — same `TypeError` as above

### Failed — BlogShowTest (3 failures)

- `BlogShowTest::it_shows_a_published_post_with_rendered_markdown_body` — BlogShowTest.php
  **Error**: HTTP 500 — `Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest: resources/js/Pages/Blog/Show.tsx`
  **Diagnosis**: The Inertia component `Blog/Show` does not exist. The controller renders `Inertia::render('Blog/Show', ...)` but the file `resources/js/Pages/Blog/Show.tsx` has not been created yet. The `resources/js/Pages/Blog/` directory itself does not exist at all (only `Auth/`, `Dashboard.tsx`, `Profile/`, `Welcome.tsx` are present). The React page for the blog show view needs to be created.
  **Suggested fix**: Create `resources/js/Pages/Blog/Show.tsx` (and also `resources/js/Pages/Blog/Index.tsx` if that does not exist — the index tests pass the Inertia assertion only because `Blog/Index` happens to be resolved or the test skips the Vite manifest check when asserting component name).

- `BlogShowTest::it_strips_embedded_html_from_the_markdown_body` — BlogShowTest.php
  **Error**: "Not a valid Inertia response." (cascade from same 500)

- `BlogShowTest::it_only_includes_approved_comments` — BlogShowTest.php
  **Error**: "Not a valid Inertia response." (cascade)

### Passed (50 tests — all non-Phase-3)
All Setup, Phase 2, Unit/Enums, and CommentStoreTest tests passed (CommentStoreTest: 4 of 4 in Phase 3 also passed).

## Root cause summary

Two independent bugs, both in Phase 3 source code:

1. **BlogController `allowedFilters` call signature** (`app/Http/Controllers/BlogController.php:25`): the array `[...]` is passed as a single argument but the installed `spatie/laravel-query-builder` version expects variadic arguments. Fix: spread the array or check the package version.

2. **Missing React page** (`resources/js/Pages/Blog/Show.tsx`): the `Blog/Show` Inertia component does not exist. The entire `resources/js/Pages/Blog/` directory is absent. The page file must be created before these tests can pass.

## Raw output (Phase 3 only)

```
Tests: 13 total | 4 passed | 9 failed
Assertions: 24

FAIL BlogIndexTest — it_lists_only_published_posts_on_the_blog_index
  TypeError: allowedFilters() — array given, string|AllowedFilter expected
  BlogController.php:25

FAIL BlogIndexTest — it_filters_posts_by_category_slug
  Not a valid Inertia response.

FAIL BlogIndexTest — it_filters_posts_by_tag_slug
  Not a valid Inertia response.

FAIL BlogIndexTest — it_filters_posts_by_search_term
  Not a valid Inertia response.

FAIL BlogIndexTest — it_sorts_posts_by_title_when_requested
  Not a valid Inertia response.

FAIL BlogIndexTest — it_ignores_disallowed_sort_and_filter_params
  TypeError: allowedFilters() — array given, string|AllowedFilter expected
  BlogController.php:25

FAIL BlogShowTest — it_shows_a_published_post_with_rendered_markdown_body
  ViteException: Unable to locate file in Vite manifest: resources/js/Pages/Blog/Show.tsx

FAIL BlogShowTest — it_strips_embedded_html_from_the_markdown_body
  Not a valid Inertia response.

FAIL BlogShowTest — it_only_includes_approved_comments
  Not a valid Inertia response.
```

Full suite: 59 tests | 50 passed | 9 failed | ~4.8s
