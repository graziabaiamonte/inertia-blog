<?php

/**
 * IDE-only stub for Intelephense.
 *
 * PHP 8.6 introduces the native global `SortDirection` enum, which Laravel 13's
 * query builder uses as the default for `orderBy()`'s `$direction` argument.
 * The Symfony php86 polyfill declares it inside a `if (PHP_VERSION_ID < 80600)`
 * block, and Intelephense does not index symbols declared inside conditionals,
 * so it cannot resolve `SortDirection::Ascending` and wrongly reports
 * "Not enough arguments. Expected 2. Found 1." on every `orderBy('col')` call.
 *
 * This unconditional declaration is never autoloaded or required at runtime
 * (it lives outside Composer's autoload paths), so it only feeds Intelephense.
 *
 * @see vendor/symfony/polyfill-php86/Resources/stubs/SortDirection.php
 */

enum SortDirection
{
    case Ascending;
    case Descending;
}
