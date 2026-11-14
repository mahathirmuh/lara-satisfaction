<?php

namespace App\Http\Controllers;

use App\Models\Content\LandingContent;
use App\Models\Organization\Department;
use App\Models\Survey\Survey;
use Illuminate\View\View;

/**
 * Public introduction page of the satisfaction survey. Texts and the hero
 * image come from Master Data > Landing Page, departments and their sections
 * from Master Data > Departments.
 */
class LandingController extends Controller
{
    /**
     * Show the landing page with the currently open survey.
     */
    public function __invoke(): View
    {
        $survey = Survey::query()
            ->open()
            ->withCount('questions')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        $departments = $survey?->departments() ?? Department::query()->active()->ordered()->get();

        return view('landing.index', [
            'content' => LandingContent::current(),
            'survey' => $survey,
            'departments' => $departments->load(['sections' => fn ($query) => $query->active()]),
        ]);
    }
}
