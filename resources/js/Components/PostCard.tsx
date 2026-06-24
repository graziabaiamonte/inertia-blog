import { PostListItem } from '@/types';
import { Link } from '@inertiajs/react';

interface Props {
    post: PostListItem;
}

export default function PostCard({ post }: Props) {
    const publishedAt = post.published_at
        ? new Date(post.published_at).toLocaleDateString(undefined, {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
          })
        : null;

    return (
        <article className="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
            {post.featured_image && (
                <Link href={route('blog.show', post.slug)}>
                    <img src={post.featured_image} alt={post.title} className="h-48 w-full object-cover" />
                </Link>
            )}

            <div className="flex flex-1 flex-col p-5">
                {post.category && (
                    <Link
                        href={route('blog.index', {
                            filter: { category: post.category.slug },
                        })}
                        className="mb-2 text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:text-indigo-800"
                    >
                        {post.category.name}
                    </Link>
                )}

                <h2 className="mb-2 text-lg font-semibold text-gray-900">
                    <Link href={route('blog.show', post.slug)} className="hover:text-indigo-600">
                        {post.title}
                    </Link>
                </h2>

                {post.excerpt && <p className="mb-4 line-clamp-3 flex-1 text-sm text-gray-600">{post.excerpt}</p>}

                <div className="mt-auto flex items-center justify-between text-xs text-gray-500">
                    <span>{post.author}</span>
                    {publishedAt && <time dateTime={post.published_at ?? ''}>{publishedAt}</time>}
                </div>

                {post.tags.length > 0 && (
                    <div className="mt-3 flex flex-wrap gap-1">
                        {post.tags.map((tag) => (
                            <Link
                                key={tag.slug}
                                href={route('blog.index', {
                                    filter: { tag: tag.slug },
                                })}
                                className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 hover:bg-gray-200"
                            >
                                {tag.name}
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </article>
    );
}
