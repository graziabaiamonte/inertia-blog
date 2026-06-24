<?php

use App\Enums\MediaCollection;
use App\Enums\RoleName;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('creates the seeded admin account', function () {
    $admin = User::where('email', 'grazia@gmail.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->hasRole(RoleName::Admin->value))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(Hash::check('passw', $admin->password))->toBeTrue();
});

it('seeds author users', function () {
    expect(User::role(RoleName::Author->value)->count())->toBeGreaterThanOrEqual(2);
});

it('seeds published posts with a featured image', function () {
    expect(Post::count())->toBe(10)
        ->and(Post::published()->count())->toBe(10);

    Post::all()->each(function (Post $post) {
        expect($post->getFirstMedia(MediaCollection::Featured->value))->not->toBeNull();
    });
});

it('seeds comments for posts', function () {
    expect(Post::has('comments')->count())->toBeGreaterThan(0);
});
