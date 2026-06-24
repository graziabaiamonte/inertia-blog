import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Comment, PageProps, PaginatedResponse } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface Props {
    comments: PaginatedResponse<Comment>;
}

export default function AdminCommentsIndex({ comments }: Props & PageProps) {
    function approve(id: number) {
        router.patch(route('admin.comments.approve', id));
    }

    function destroy(id: number) {
        if (confirm('Delete this comment?')) {
            router.delete(route('admin.comments.destroy', id));
        }
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Comment Moderation</h2>}
        >
            <Head title="Comments" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {comments.data.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center text-gray-500">
                            No comments to moderate.
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {comments.data.map((comment) => (
                                <div
                                    key={comment.id}
                                    className={`rounded-lg border bg-white p-5 shadow-sm ${
                                        comment.approved ? 'border-green-200' : 'border-yellow-200'
                                    }`}
                                >
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3">
                                                <span className="font-semibold text-gray-900">
                                                    {comment.author_name}
                                                </span>
                                                <span className="text-sm text-gray-400">{comment.author_email}</span>
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-semibold ${
                                                        comment.approved
                                                            ? 'bg-green-100 text-green-700'
                                                            : 'bg-yellow-100 text-yellow-700'
                                                    }`}
                                                >
                                                    {comment.approved ? 'Approved' : 'Pending'}
                                                </span>
                                                <time className="text-xs text-gray-400">
                                                    {new Date(comment.created_at).toLocaleDateString()}
                                                </time>
                                            </div>

                                            {comment.post && (
                                                <div className="mt-1 text-sm text-gray-500">
                                                    On:{' '}
                                                    <Link
                                                        href={route('blog.show', comment.post.slug)}
                                                        className="text-indigo-600 hover:underline"
                                                        target="_blank"
                                                    >
                                                        {comment.post.title}
                                                    </Link>
                                                </div>
                                            )}

                                            <p className="mt-3 whitespace-pre-wrap text-sm text-gray-700">
                                                {comment.body}
                                            </p>
                                        </div>

                                        <div className="ml-4 flex shrink-0 gap-2">
                                            {!comment.approved && (
                                                <button
                                                    onClick={() => approve(comment.id)}
                                                    className="rounded-md bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-700"
                                                >
                                                    Approve
                                                </button>
                                            )}
                                            <button
                                                onClick={() => destroy(comment.id)}
                                                className="rounded-md bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-700"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}

                            <div className="mt-6">
                                <Pagination links={comments.links} />
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
