<?php

use App\Enums\PostStatus;

it('has the expected backing values', function () {
    expect(PostStatus::Draft->value)->toBe('draft')
        ->and(PostStatus::Published->value)->toBe('published');
});

it('exposes human-readable labels', function () {
    expect(PostStatus::Draft->label())->toBe('Draft')
        ->and(PostStatus::Published->label())->toBe('Published');
});

it('returns all backing values', function () {
    expect(PostStatus::values())->toBe(['draft', 'published']);
});
