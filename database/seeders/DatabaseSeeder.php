<?php

namespace Database\Seeders;

use App\Enums\MediaCollection;
use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // Specific seeded admin (pre-verified, bypasses the 8-char rule via Hash::make).
        $admin = User::factory()->create([
            'name' => 'Grazia',
            'email' => 'grazia@gmail.com',
            'password' => Hash::make('passw'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(RoleName::Admin->value);

        // A couple of authors for realistic data.
        $authors = User::factory(2)->create();
        foreach ($authors as $author) {
            $author->assignRole(RoleName::Author->value);
        }

        $allAuthors = $authors->push($admin);

        $categories = Category::factory(4)->create();
        $tags = Tag::factory(8)->create();

        // ~10 published posts, each with a featured image and a few comments.
        Post::factory(10)
            ->published()
            ->recycle($categories)
            ->recycle($allAuthors)
            ->create()
            ->each(function (Post $post) use ($tags): void {
                $post->tags()->attach($tags->random(rand(1, 3))->pluck('id'));

                $post->addMediaFromString($this->placeholderImage())
                    ->usingFileName($post->slug.'.png')
                    ->toMediaCollection(MediaCollection::Featured->value);

                Comment::factory(rand(2, 5))
                    ->for($post)
                    ->create();
            });
    }

    /**
     * Generate a small solid-colour PNG so seeding works offline.
     */
    private function placeholderImage(): string
    {
        $image = imagecreatetruecolor(400, 300);
        imagefill($image, 0, 0, imagecolorallocate($image, rand(60, 200), rand(60, 200), rand(60, 200)));

        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        return $contents;
    }
}
