<?php

namespace App\Http\Controllers;

use App\Models\Organization\Department;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in user's own profile: what the company directory says about
 * them (refreshed at every sign-in), their roles, the survey results they
 * may see and their interface language. Nothing here is edited by hand:
 * there are no passwords, and the profile comes from the directory.
 */
class ProfileController extends Controller
{
    /**
     * Show the profile of the signed-in user.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing(['company:id,code,name', 'roles']);
        $reportDepartmentIds = $user->reportDepartmentIds();

        return view('profile.show', [
            'user' => $user,
            'roles' => $user->roles->sortBy('id')->values(),
            'seesAllResults' => $reportDepartmentIds === null,
            'resultDepartments' => $reportDepartmentIds
                ? Department::query()->whereKey($reportDepartmentIds)->ordered()->pluck('name')
                : collect(),
            'surveysCompleted' => $user->participations()->count(),
        ]);
    }
}
