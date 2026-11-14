<?php

namespace Modules\MasterData\Http\Controllers;

use App\Helpers\Alert;
use App\Http\Controllers\Controller;
use App\Models\Content\LandingContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\MasterData\Http\Requests\LandingContentRequest;
use Modules\MasterData\Repositories\LandingContentInterface;

/**
 * Texts and hero image of the public landing page.
 */
class LandingContentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected LandingContentInterface $landingContent) {}

    /**
     * Landing page content form.
     */
    public function index(Request $request): View
    {
        return view('MasterData::landing-content.index', [
            'content' => $this->landingContent->current(),
            'placeholder' => LandingContent::SURVEY_PLACEHOLDER,
            'can' => $request->user()->menuAbilities('master-data.landing-content'),
        ]);
    }

    /**
     * Save the landing page content.
     */
    public function update(LandingContentRequest $request): RedirectResponse
    {
        $this->landingContent->update(
            $request->contentAttributes(),
            $request->file('hero_image'),
            $request->boolean('remove_hero_image'),
            $request->user(),
        );

        Alert::success('Landing page updated', 'The public landing page now shows the new content.');

        return redirect()->route('master-data.landing-content.index');
    }
}
