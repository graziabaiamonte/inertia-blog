import { router, usePage } from '@inertiajs/react';

import { PageProps } from '@/types';

const LOCALES = ['en', 'it'] as const;
type Locale = (typeof LOCALES)[number];

const BASE =
    'rounded-md px-2.5 py-1 text-sm font-medium transition-colors duration-200 ' +
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1';

const ACTIVE = 'bg-indigo-600 text-white cursor-default';
const INACTIVE = 'text-gray-600 hover:bg-gray-100 hover:text-gray-900';

export default function LocaleSwitcher() {
    const { locale } = usePage<PageProps>().props;

    function switchLocale(next: Locale) {
        if (next === locale) return;
        router.post(route('locale.update'), { locale: next }, { preserveScroll: true, preserveState: false });
    }

    return (
        <div className="flex items-center gap-1">
            {LOCALES.map((loc) => {
                const isActive = loc === locale;
                return (
                    <button
                        key={loc}
                        type="button"
                        onClick={() => switchLocale(loc)}
                        disabled={isActive}
                        className={`${BASE} ${isActive ? ACTIVE : INACTIVE}`}
                    >
                        {loc.toUpperCase()}
                    </button>
                );
            })}
        </div>
    );
}
