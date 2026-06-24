<?php

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['post_id', 'author_name', 'author_email', 'body', 'approved'])]
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static> approved()
 * @property int $id
 * @property int $post_id
 * @property string $author_name
 * @property string $author_email
 * @property string $body
 * @property bool $approved
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Post $post
 * @method static \Database\Factories\CommentFactory factory($count = null, $state = [])
 * @method static Builder<static>|Comment newModelQuery()
 * @method static Builder<static>|Comment newQuery()
 * @method static Builder<static>|Comment query()
 * @method static Builder<static>|Comment whereApproved($value)
 * @method static Builder<static>|Comment whereAuthorEmail($value)
 * @method static Builder<static>|Comment whereAuthorName($value)
 * @method static Builder<static>|Comment whereBody($value)
 * @method static Builder<static>|Comment whereCreatedAt($value)
 * @method static Builder<static>|Comment whereId($value)
 * @method static Builder<static>|Comment wherePostId($value)
 * @method static Builder<static>|Comment whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperComment
 */
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved' => 'boolean',
        ];
    }

    /**
     * The post this comment belongs to.
     *
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Scope to only approved comments.
     *
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approved', true);
    }
}
