import { PostStatus } from './enums';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    posts_count?: number;
}

export interface Tag {
    id: number;
    name: string;
    slug: string;
    posts_count?: number;
}

export interface Comment {
    id: number;
    author_name: string;
    author_email?: string;
    body: string;
    approved: boolean;
    created_at: string;
    post?: { title: string; slug: string } | null;
}

export interface PostListItem {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at: string | null;
    author: string | null;
    category?: { name: string; slug: string } | null;
    tags: Array<{ name: string; slug: string }>;
    featured_image?: string | null;
}

export interface PostDetail extends PostListItem {
    body_html: string;
    comments: Array<Pick<Comment, 'id' | 'author_name' | 'body' | 'created_at'>>;
}

export interface AdminPost {
    id: number;
    title: string;
    slug: string;
    status: PostStatus;
    author: string | null;
    category?: string | null;
    published_at?: string | null;
}

export interface AdminPostForm {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    body: string;
    category_id?: number | null;
    status: PostStatus;
    published_at?: string | null;
    tags: number[];
    featured_image?: string | null;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number | null;
    to?: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
        roles: string[];
    };
    locale: string;
    translations: Record<string, string>;
};
