import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import { PageProps } from '@/types';

export default function PublicLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<PageProps>().props;

    return (
        <div className="flex min-h-screen flex-col bg-white">
            <header className="border-b border-gray-200 bg-white">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
                    <Link href={route('blog.index')} className="text-xl font-bold text-gray-900 hover:text-indigo-600">
                        Blog
                    </Link>

                    <nav className="flex items-center gap-4 text-sm">
                        {/* <Link href={route('blog.index')} className="text-gray-600 hover:text-gray-900">
                            Articles
                        </Link> */}

                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="rounded-md bg-indigo-600 px-3 py-1.5 text-white hover:bg-indigo-700"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link href={route('login')} className="text-gray-600 hover:text-gray-900">
                                    Log in
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="rounded-md bg-indigo-600 px-3 py-1.5 text-white hover:bg-indigo-700"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="border-t border-gray-200 py-6 text-center text-sm text-gray-500">
                &copy; {new Date().getFullYear()} Blog. All rights reserved.
            </footer>
        </div>
    );
}
