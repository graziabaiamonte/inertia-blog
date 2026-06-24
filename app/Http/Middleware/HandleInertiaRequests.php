<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $translationFile = lang_path("{$locale}.json");
        $translations = file_exists($translationFile)
            ? json_decode(file_get_contents($translationFile), true, 512, JSON_THROW_ON_ERROR)
            : [];

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'roles' => $request->user()?->getRoleNames()->toArray() ?? [],
            ],
            'locale' => $locale,
            'translations' => $translations,
        ];
    }
}
