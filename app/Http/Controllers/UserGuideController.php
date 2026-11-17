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
     * Handle the incoming request.
     */
    public function __invoke(): BinaryFileResponse
    {
        $path = base_path(self::GUIDES[app()->getLocale()] ?? self::GUIDES['en']);

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }
}
