import { PaginatedResponse } from '@/types';
import { Link } from '@inertiajs/react';

interface Props {
    links: PaginatedResponse<unknown>['links'];
}

export default function Pagination({ links }: Props) {
    if (links.length <= 3) return null;

    return (
        <nav
            className="flex flex-wrap items-center justify-center gap-1"
            aria-label="Pagination"
        >
            {links.map((link, i) => {
                if (!link.url) {
                    return (
                        <span
                            key={i}
                            className="rounded px-3 py-1.5 text-sm text-gray-400"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    );
                }

                return (
                    <Link
                        key={i}
                        href={link.url}
                        className={`rounded px-3 py-1.5 text-sm transition ${
                            link.active
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                        }`}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                );
            })}
        </nav>
    );
}
