import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Category, PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Props {
    categories: Category[];
}

export default function AdminCategoriesIndex({ categories }: Props & PageProps) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Categories</h2>
                    <Link
                        href={route('admin.categories.create')}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
                    >
                        New Category
                    </Link>
                </div>
            }
        >
            <Head title="Categories" />

            <div className="py-8">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    {categories.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center text-gray-500">
                            No categories yet.
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Name
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Slug
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Posts
                                        </th>
                                        <th className="relative px-6 py-3">
                                            <span className="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {categories.map((cat) => (
                                        <tr key={cat.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm font-medium text-gray-900">{cat.name}</td>
                                            <td className="px-6 py-4 font-mono text-sm text-gray-500">{cat.slug}</td>
                                            <td className="px-6 py-4 text-sm text-gray-500">{cat.posts_count ?? 0}</td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                <Link
                                                    href={route('admin.categories.edit', cat.slug)}
                                                    className="mr-3 text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Edit
                                                </Link>
                                                <Link
                                                    href={route('admin.categories.destroy', cat.slug)}
                                                    method="delete"
                                                    as="button"
                                                    className="text-red-600 hover:text-red-900"
                                                    onClick={(e) => {
                                                        if (!confirm('Delete this category?')) e.preventDefault();
                                                    }}
                                                >
                                                    Delete
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
