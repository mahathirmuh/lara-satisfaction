<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The user guide that the user menu opens (User Guide): the PDF in docs/ in the language of the page (Bahasa
 * Indonesia, English or Simplified Chinese), English for any other. Open to everyone signed in.
 */
class UserGuideController extends Controller
{
    /**
     * The guide of each interface language that has one, relative to the project root.
     *
     * @var array<string, string>
     */
    public const GUIDES = [
        'id' => 'docs/User-Guide-Employee-Satisfaction-Survey-ID.pdf',
        'en' => 'docs/User-Guide-Employee-Satisfaction-Survey-EN.pdf',
        'zh_CN' => 'docs/User-Guide-Employee-Satisfaction-Survey-ZH.pdf',
    ];

    /**
     * The guide of the language in the address (the menus link to the one of the page), else of the page. Each
     * language has its own address, so a browser never shows the guide of another language from its cache; and it
     * checks with the server every time, so a new version of the guide shows at once.
     */
    public function __invoke(?string $language = null): BinaryFileResponse
    {
        $path = base_path(self::GUIDES[$language ?? app()->getLocale()] ?? self::GUIDES['en']);

        abort_unless(is_file($path), 404);

        $response = response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');

        return $response;
    }
}
