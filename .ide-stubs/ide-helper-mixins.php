<?php

/**
 * IDE-only stubs — never autoloaded at runtime.
 *
 * Intelephense cannot resolve the IdeHelper* mixin classes referenced via
 * @mixin IdeHelperXxx in _ide_helper_models.php / model docblocks because
 * barryvdh/laravel-ide-helper generates them only in some configurations.
 * Without these stubs Intelephense 1.18.x misreports Eloquent instance
 * methods (e.g. P1119 "Too many arguments" on ->update($array)).
 *
 * Each class here mirrors the Eloquent Model instance method signatures that
 * are otherwise missed; the @mixin chain then picks them up correctly.
 */

namespace App\Models;

class IdeHelperCategory
{
    /** @param array<string, mixed> $attributes */
    public function update(array $attributes = [], array $options = []): bool
    {
    }
}

class IdeHelperComment
{
    /** @param array<string, mixed> $attributes */
    public function update(array $attributes = [], array $options = []): bool
    {
    }
}

class IdeHelperPost
{
    /** @param array<string, mixed> $attributes */
    public function update(array $attributes = [], array $options = []): bool
    {
    }
}

class IdeHelperTag
{
    /** @param array<string, mixed> $attributes */
    public function update(array $attributes = [], array $options = []): bool
    {
    }
}

class IdeHelperUser
{
    /** @param array<string, mixed> $attributes */
    public function update(array $attributes = [], array $options = []): bool
    {
    }
}
