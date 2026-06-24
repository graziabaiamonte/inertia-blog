<?php

namespace App\Support;

use League\CommonMark\CommonMarkConverter;

class Markdown
{
    /**
     * Render Markdown to sanitized HTML.
     *
     * HTML embedded in the Markdown source is stripped and unsafe links are
     * disabled, so the output is safe to render on the frontend.
     */
    public static function toHtml(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($markdown)->getContent();
    }
}
