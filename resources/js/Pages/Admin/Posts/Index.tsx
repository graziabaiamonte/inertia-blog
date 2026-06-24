import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { AdminPost, PageProps, PaginatedResponse } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Props {
    posts: PaginatedResponse<AdminPost>;
}

export default function AdminPostsIndex({ posts, auth }: Props & PageProps) {
    const isAdmin = auth.roles.includes('admin');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Posts</h2>
                    <Link
                        href={route('admin.posts.create')}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
                    >
                        New Post
                    </Link>
                </div>
            }
        >
            <Head title="Posts" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {posts.data.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center text-gray-500">
                            No posts yet.
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Title
                                        </th>
                                        {isAdmin && (
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Author
                                            </th>
                                        )}
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Category
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Published
                                        </th>
                                        <th className="relative px-6 py-3">
                                            <span className="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {posts.data.map((post) => (
                                        <tr key={post.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                                {post.title}
                                            </td>
                                            {isAdmin && (
                                                <td className="px-6 py-4 text-sm text-gray-500">
                                                    {post.author ?? '—'}
                                                </td>
                                            )}
                                            <td className="px-6 py-4 text-sm text-gray-500">{post.category ?? '—'}</td>
                                            <td className="px-6 py-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
                                                        post.status === 'published'
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-yellow-100 text-yellow-800'
                                                    }`}
                                                >
                                                    {post.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-500">
                                                {post.published_at
                                                    ? new Date(post.published_at).toLocaleDateString()
                                                    : '—'}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                <Link
                                                    href={route('admin.posts.edit', post.slug)}
                                                    className="mr-3 text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Edit
                                                </Link>
                                                <Link
                                                    href={route('admin.posts.destroy', post.slug)}
                                                    method="delete"
                                                    as="button"
                                                    className="text-red-600 hover:text-red-900"
                                                    onClick={(e) => {
                                                        if (!confirm('Delete this post?')) e.preventDefault();
                                                    }}
                                                >
                                                    Delete
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            <div className="border-t border-gray-200 px-6 py-4">
                                <Pagination links={posts.links} />
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
