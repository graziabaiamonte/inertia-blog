<?php

namespace App\Enums;

enum PermissionName: string
{
    case CreatePosts = 'create posts';
    case EditOwnPosts = 'edit own posts';
    case DeleteOwnPosts = 'delete own posts';
    case PublishPosts = 'publish posts';
    case ManageAllPosts = 'manage all posts';
    case ModerateComments = 'moderate comments';
    case ManageTaxonomy = 'manage taxonomy';
    case ManageUsers = 'manage users';

    /**
     * Human-readable label for the UI.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * All backing values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
