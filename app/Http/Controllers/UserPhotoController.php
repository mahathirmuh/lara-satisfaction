<?php

namespace App\Http\Controllers;

use App\Models\UserManagement\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves employee photos (thumbnails from the card system, kept on the
 * private disk) to signed-in people: their own, and everyone's to those who
 * manage users or follow the survey progress.
 */
class UserPhotoController extends Controller
{
    /**
     * The photo of a user.
     */
    public function __invoke(Request $request, User $user): BinaryFileResponse
    {
        $viewer = $request->user();

        abort_unless(
            $viewer->is($user)
                || $viewer->canAccessMenu('user-management.user-registered')
                || $viewer->canAccessMenu('survey-progress'),
            Response::HTTP_FORBIDDEN,
        );

        abort_if($user->photo_path === null || ! Storage::disk('local')->exists($user->photo_path), Response::HTTP_NOT_FOUND);

        return response()->file(Storage::disk('local')->path($user->photo_path), [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }
}
