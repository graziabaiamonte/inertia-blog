import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export function useTranslations() {
    const { translations } = usePage<PageProps>().props;

    return function t(key: string): string {
        return translations?.[key] ?? key;
    };
}
