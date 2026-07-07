import Pagination from '@/Components/Pagination';
import PostCard from '@/Components/PostCard';
import PublicLayout from '@/Layouts/PublicLayout';
import { Category, PageProps, PaginatedResponse, PostListItem, Tag } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    posts: PaginatedResponse<PostListItem>;
    filters: Record<string, string>;
    sort: string | null;
    categories: Array<Pick<Category, 'name' | 'slug'>>;
    tags: Array<Pick<Tag, 'name' | 'slug'>>;
}

export default function BlogIndex({ posts, filters, sort, categories, tags }: Props & PageProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    function applyFilter(params: Record<string, string | null>) {
        const current: Record<string, string> = {};
        if (filters.category) current['filter[category]'] = filters.category;
        if (filters.tag) current['filter[tag]'] = filters.tag;
        if (filters.search) current['filter[search]'] = filters.search;
        if (sort) current['sort'] = sort;

        const merged: Record<string, string> = { ...current };
        for (const [k, v] of Object.entries(params)) {
            if (v === null) delete merged[k];
            else merged[k] = v;
        }

        router.get(route('blog.index'), merged, { preserveState: true });
    }

    function handleSearch(e: React.SubmitEvent<HTMLFormElement>) {
        e.preventDefault();
        applyFilter({
            'filter[search]': search || null,
            'filter[category]': null,
            'filter[tag]': null,
        });
    }

    const activeCategory = filters.category ?? null;
    const activeTag = filters.tag ?? null;

    return (
        <PublicLayout>
            <Head title="Blog" />

            <div className="mx-auto max-w-5xl px-4 py-10 sm:px-6">
                <h1 className="mb-8 text-3xl font-bold text-gray-900">Articles</h1>

                {/* Filters */}
                <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <input
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search…"
                            className="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <button
                            type="submit"
                            className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-700"
                        >
                            Search
                        </button>
                        {(filters.search || filters.category || filters.tag) && (
                            <button
                                type="button"
                                onClick={() => {
                                    setSearch('');
                                    router.get(route('blog.index'));
                                }}
                                className="text-sm text-gray-500 hover:text-gray-700"
                            >
                                Clear
                            </button>
                        )}
                    </form>

                    <div className="flex items-center gap-2 text-sm">
                        <span className="text-gray-500">Sort:</span>
                        <button
                            onClick={() => applyFilter({ sort: '-published_at' })}
                            className={
                                sort === '-published_at' || !sort
                                    ? 'font-semibold text-indigo-600'
                                    : 'text-gray-600 hover:text-gray-900'
                            }
                        >
                            Newest
                        </button>
                        <span className="text-gray-300">|</span>
                        <button
                            onClick={() => applyFilter({ sort: 'title' })}
                            className={
                                sort === 'title' ? 'font-semibold text-indigo-600' : 'text-gray-600 hover:text-gray-900'
                            }
                        >
                            A–Z
                        </button>
                    </div>
                </div>

                {/* Category chips */}
                {categories.length > 0 && (
                    <div className="mb-4 flex flex-wrap gap-2">
                        <button
                            onClick={() => applyFilter({ 'filter[category]': null })}
                            className={`rounded-full px-3 py-1 text-xs ${
                                !activeCategory
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            }`}
                        >
                            All
                        </button>
                        {categories.map((c) => (
                            <button
                                key={c.slug}
                                onClick={() =>
                                    applyFilter({
                                        'filter[category]': c.slug,
                                        'filter[tag]': null,
                                    })
                                }
                                className={`rounded-full px-3 py-1 text-xs ${
                                    activeCategory === c.slug
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                }`}
                            >
                                {c.name}
                            </button>
                        ))}
                    </div>
                )}

                {/* Tag chips */}
                {tags.length > 0 && (
                    <div className="mb-6 flex flex-wrap gap-2">
                        {tags.map((t) => (
                            <button
                                key={t.slug}
                                onClick={() =>
                                    applyFilter({
                                        'filter[tag]': activeTag === t.slug ? null : t.slug,
                                        'filter[category]': null,
                                    })
                                }
                                className={`rounded-full px-3 py-1 text-xs ${
                                    activeTag === t.slug
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                }`}
                            >
                                #{t.name}
                            </button>
                        ))}
                    </div>
                )}

                {/* Posts grid */}
                {posts.data.length === 0 ? (
                    <p className="py-16 text-center text-gray-500">No articles found.</p>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {posts.data.map((post) => (
                            <PostCard key={post.id} post={post} />
                        ))}
                    </div>
                )}

                {/* Pagination */}
                <div className="mt-10">
                    <Pagination links={posts.links} />
                </div>
            </div>
        </PublicLayout>
    );
}
