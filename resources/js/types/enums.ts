export type PostStatus = 'draft' | 'published';

export type RoleName = 'admin' | 'author';

export type MediaCollection = 'featured' | 'content';

export const POST_STATUS = {
    Draft: 'draft' as PostStatus,
    Published: 'published' as PostStatus,
} as const;

export const ROLE_NAME = {
    Admin: 'admin' as RoleName,
    Author: 'author' as RoleName,
} as const;
