import CommentForm from '@/Components/CommentForm';
import CommentList from '@/Components/CommentList';
import PublicLayout from '@/Layouts/PublicLayout';
import { PageProps, PostDetail } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Props {
    post: PostDetail;
}

export default function BlogShow({ post }: Props & PageProps) {
    const publishedAt = post.published_at
        ? new Date(post.published_at).toLocaleDateString(undefined, {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
          })
        : null;

    return (
        <PublicLayout>
            <Head title={post.title} />

            <article className="mx-auto max-w-3xl px-4 py-10 sm:px-6">
                {/* Breadcrumb */}
                <nav className="mb-6 text-sm text-gray-500">
                    <Link href={route('blog.index')} className="hover:text-indigo-600">
                        Blog
                    </Link>
                    <span className="mx-2">/</span>
                    <span className="text-gray-700">{post.title}</span>
                </nav>

                {/* Category */}
                {post.category && (
                    <Link
                        href={route('blog.index', {
                            'filter[category]': post.category.slug,
                        })}
                        className="mb-3 inline-block text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:text-indigo-800"
                    >
                        {post.category.name}
                    </Link>
                )}

                <h1 className="mb-4 text-3xl font-bold leading-tight text-gray-900 sm:text-4xl">
                    {post.title}
                </h1>

                <div className="mb-6 flex items-center gap-3 text-sm text-gray-500">
                    {post.author && <span>{post.author}</span>}
                    {publishedAt && (
                        <>
                            <span>·</span>
                            <time dateTime={post.published_at ?? ''}>{publishedAt}</time>
                        </>
                    )}
                </div>

                {/* Featured image */}
                {post.featured_image && (
                    <img
                        src={post.featured_image}
                        alt={post.title}
                        className="mb-8 w-full rounded-xl object-cover shadow-sm"
                        style={{ maxHeight: '480px' }}
                    />
                )}

                {/* Post body — server-sanitized HTML */}
                <div
                    className="prose prose-indigo max-w-none"
                    dangerouslySetInnerHTML={{ __html: post.body_html }}
                />

                {/* Tags */}
                {post.tags.length > 0 && (
                    <div className="mt-8 flex flex-wrap gap-2">
                        {post.tags.map((tag) => (
                            <Link
                                key={tag.slug}
                                href={route('blog.index', {
                                    'filter[tag]': tag.slug,
                                })}
                                className="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600 hover:bg-gray-200"
                            >
                                #{tag.name}
                            </Link>
                        ))}
                    </div>
                )}

                <hr className="my-12 border-gray-200" />

                {/* Comments */}
                <section>
                    <h2 className="mb-6 text-xl font-semibold text-gray-900">
                        Comments ({post.comments.length})
                    </h2>
                    <CommentList comments={post.comments} />

                    <div className="mt-10">
                        <CommentForm postSlug={post.slug} />
                    </div>
                </section>
            </article>
        </PublicLayout>
    );
}
