<?php

namespace Modules\MasterData\Repositories;

use App\Models\Content\LandingContent;
use App\Models\UserManagement\User;
use Illuminate\Http\UploadedFile;

interface LandingContentInterface
{
    /**
     * The stored landing content, or the defaults when nothing was saved yet.
     */
    public function current(): LandingContent;

    /**
     * Save the landing content, replacing or removing the hero image.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, ?UploadedFile $heroImage, bool $removeHeroImage, User $editor): LandingContent;
}
